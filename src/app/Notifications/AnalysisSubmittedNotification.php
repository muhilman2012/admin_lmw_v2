<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Report;

class AnalysisSubmittedNotification extends Notification
{
    use Queueable;

    public $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'analysis_submitted',
            'title' => 'Analisis Siap Diverifikasi',
            'message' => "Analis telah mengirimkan hasil analisis untuk Laporan #{$this->report->ticket_number}. Mohon diverifikasi.",
            'report_id' => $this->report->id,
            'report_uuid' => $this->report->uuid,
            'url' => route('reports.show', $this->report->uuid),
            'icon' => 'ti ti-clipboard-check',
        ];
    }
}
