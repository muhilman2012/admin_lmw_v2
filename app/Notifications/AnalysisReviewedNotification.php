<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Report;
use App\Models\User;

class AnalysisReviewedNotification extends Notification
{
    use Queueable;

    public $report;
    public $reviewer;
    public $action; // 'approved' atau 'revision_needed'

    public function __construct(Report $report, User $reviewer, string $action)
    {
        $this->report = $report;
        $this->reviewer = $reviewer;
        $this->action = $action;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $statusMap = [
            'approved' => ['title' => 'Analisis Disetujui', 'icon' => 'ti ti-checks', 'color' => 'success'],
            'revision_needed' => ['title' => 'Perlu Perbaikan Analisis', 'icon' => 'ti ti-alert-triangle', 'color' => 'danger'],
        ];

        $status = $statusMap[$this->action];

        return [
            'type' => 'analysis_reviewed',
            'title' => $status['title'],
            'message' => "Analisis Laporan #{$this->report->ticket_number} telah di-{$this->action} oleh {$this->reviewer->name}.",
            'report_id' => $this->report->id,
            'report_uuid' => $this->report->uuid,
            'url' => route('reports.show', $this->report->uuid),
            'icon' => $status['icon'],
            'color' => $status['color'],
        ];
    }
}
