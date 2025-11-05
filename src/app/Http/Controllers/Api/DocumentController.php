<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Document;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file_base64' => 'required|string',
            'description' => 'nullable|string',
        ]);
    
        try {
            $base64String = $request->input('file_base64');
            $extension = $this->getFileExtension($base64String);

            if (!$extension) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Tipe file tidak didukung.'
                ], 400);
            }

            $cleanedBase64 = preg_replace('/^data:([a-zA-Z0-9\/]+);base64,/', '', $base64String);
            $decodedFile = base64_decode($cleanedBase64);

            if ($decodedFile === false) {
                return response()->json([
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Data file tidak valid atau rusak.'
                ], 400);
            }

            $fileSize = strlen($decodedFile);
            $maxFileSizeInBytes = 20 * 1024 * 1024; // 20 MB

            if ($fileSize > $maxFileSizeInBytes) {
                return response()->json([
                    'status' => 'error',
                    'code' => 413,
                    'message' => 'Ukuran file melebihi batas maksimal 20MB.'
                ], 413);
            }

            $fileName = 'documents/' . Str::uuid() . '.' . $extension;
            Storage::disk('complaints')->put($fileName, $decodedFile);

            $document = Document::create([
                'file_path' => $fileName,
                'description' => $request->input('description'),
                'report_id' => null,
            ]);
            
            $fileUrl = Storage::disk('complaints')->url($fileName);
            
            Log::info('Dokumen berhasil diunggah.', [
                'document_id' => $document->id,
                'file_path' => $fileName,
                'description' => $document->description,
            ]);
    
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Dokumen berhasil diunggah.',
                'data' => [
                    'id' => $document->id,
                    'filename' => basename($fileName),
                    'url' => $fileUrl,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Terjadi kesalahan saat mengunggah dokumen: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => 'Terjadi kesalahan internal. Silakan periksa log server untuk detailnya.'
            ], 500);
        }
    }

    private function getFileExtension(string $base64String): ?string
    {
        preg_match('/^data:([a-zA-Z0-9\/]+);base64,/', $base64String, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        $mimeType = $matches[1];
        
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];

        return $allowedMimeTypes[$mimeType] ?? null;
    }
}
