<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Report;
use App\Models\Document;
use App\Models\User;

class NewDocumentSubmitted extends Notification
{
    use Queueable;

    public $report;
    public $document;

    /**
     * Create a new notification instance.
     * @param Report $report Laporan yang diperbarui
     * @param Document $document Dokumen baru yang diunggah
     */
    public function __construct(Report $report, Document $document)
    {
        $this->report = $report;
        $this->document = $document;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $message = "Pelapor telah mengirimkan dokumen tambahan ({$this->document->description}) untuk Tiket #{$this->report->ticket_number}. Status laporan diubah ke 'Proses verifikasi dan telaah'.";
        
        $baseUrl = rtrim(config('app.url'), '/');
        $reportUrl = "{$baseUrl}/admin/reports/{$this->report->uuid}/detail";

        return [
            'type' => 'document_submitted',
            'title' => 'Dokumen Tambahan Diunggah Pelapor',
            'message' => $message,
            'report_id' => $this->report->id,
            'report_uuid' => $this->report->uuid,
            'document_id' => $this->document->id,
            'url' => $reportUrl,
            'icon' => 'ti ti-file-plus', 
        ];
    }
}