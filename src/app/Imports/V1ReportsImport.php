<?php

namespace App\Imports;

use Illuminate\Support\Str;
use App\Models\Reporter;
use App\Models\Report;
use App\Models\Category;
use App\Models\Deputy;
use App\Models\UnitKerja;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Database\QueryException;

class V1ReportsImport implements ToCollection, WithHeadingRow, WithStartRow
{
    private $skipValidation;
    private $adminUserId = 1;
    private $importedCount = 0;
    private $failedTickets = [];

    public function startRow(): int
    {
        return 2;
    }

    public function __construct($skipValidation = false)
    {
        $this->skipValidation = $skipValidation;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // MULAI TRANSAKSI PER BARIS (Allows row skipping)
            DB::beginTransaction();
            
            try {
                $nik = trim((string)$row['nik']);
                $ticketNumber = trim((string)$row['ticket_number']);
                $phoneNumber = trim((string)$row['phone_number']);
                $currentTicket = $ticketNumber;

                if (empty($nik) || empty($ticketNumber)) {
                    // Log: Lewati baris karena data dasar kosong
                    $this->failedTickets[] = ['ticket' => $ticketNumber, 'error' => 'Data NIK atau Tiket kosong.'];
                    DB::rollBack();
                    continue; 
                }
                
                // --- Logika Konversi Tanggal ---
                $createdAt = $this->convertDate($row['created_at']);
                $eventDate = $this->convertDate($row['event_date']);

                // --- 3. CARI/BUAT REPORTER ---
                $reporterData = [
                    'nik' => $nik, 'name' => $row['reporter_name'] ?? 'Anonim', 
                    'phone_number' => $phoneNumber, 'email' => $row['email'],
                    'address' => $row['address'],
                ];
                $reporter = Reporter::firstOrCreate(['nik' => $nik], $reporterData);


                // --- 4. CARI RELASI ---
                $category = Category::where('name', $row['category_name'] ?? '')->first();
                $unitKerja = UnitKerja::where('name', $row['unit_kerja_name'] ?? '')->first();
                $deputy = Deputy::where('name', $row['deputy_name'] ?? '')->first();
                $assignedToUser = User::where('email', $row['analyst_email'] ?? '')->first();
                
                // --- 5. BUAT REPORT ---
                $reportData = [
                    'uuid' => Str::uuid(),
                    'reporter_id' => $reporter->id,
                    'category_id' => $category->id ?? null,
                    'ticket_number' => $ticketNumber,
                    'subject' => $row['subject'] ?? 'Laporan Lama',
                    'details' => $row['description'],
                    'location' => $row['location'] ?? null,
                    'event_date' => $eventDate,
                    'source' => $row['source'] ?? 'Import V1',
                    'status' => $row['status'] ?? 'submitted',
                    'response' => $row['response'] ?? null,
                    'unit_kerja_id' => $unitKerja->id ?? null,
                    'deputy_id' => $deputy->id ?? null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt, 
                ];

                // Cek duplikasi TICKET NUMBER (hanya jika skipValidation FALSE)
                if (!$this->skipValidation && Report::where('ticket_number', $ticketNumber)->exists()) {
                    throw new \Exception("Tiket sudah ada.");
                }

                $report = Report::create($reportData);
                
                // --- 6. BUAT ENTRI ASSIGNMENT (Jika Analis ditemukan) ---
                if ($assignedToUser) {
                    Assignment::create([
                        'report_id' => $report->id,
                        'assigned_by_id' => $this->adminUserId, 
                        'assigned_to_id' => $assignedToUser->id,
                        'status' => $row['assignment_status'] ?? 'completed',
                        'notes' => $row['assignment_notes'] ?? 'Migrasi dari V1',
                        'created_at' => $report->created_at, 
                        'updated_at' => $report->updated_at,
                    ]);
                }
                
                // COMMIT DAN TAMBAH COUNTER SUKSES
                DB::commit();
                $this->importedCount++;

            } catch (\Exception $e) {
                // Tangani kegagalan pada baris ini (Database atau data)
                $errorMessage = $e->getMessage();
                
                // Log detail kegagalan
                $this->failedTickets[] = [
                    'ticket' => $currentTicket, 
                    'error' => substr($errorMessage, 0, 150) // Batasi panjang error
                ];
                
                DB::rollBack();
                continue; // Lanjut ke baris berikutnya
            }
        }
    }
    
    // Helper function untuk konversi tanggal (agar logic lebih bersih)
    private function convertDate($dateInput)
    {
        if (empty($dateInput)) { return null; }
        $dateValue = trim((string)$dateInput); 

        if (is_numeric($dateValue) && $dateValue > 25569) {
             return Date::excelToDateTimeObject($dateValue);
        } else {
             try {
                $timestamp = strtotime($dateValue);
                if ($timestamp !== false) { 
                     return (new \DateTime())->setTimestamp($timestamp);
                } 
             } catch (\Exception $e) {
                return null;
             }
        }
        return null;
    }
    
    // Method untuk mendapatkan hasil
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getFailedTickets(): array
    {
        return $this->failedTickets;
    }
}
