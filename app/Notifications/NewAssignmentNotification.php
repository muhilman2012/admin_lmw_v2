<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Report;
use App\Models\User;

class NewAssignmentNotification extends Notification
{
    use Queueable;

    public $report;
    public $assigner;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report, User $assigner)
    {
        $this->report = $report;
        $this->assigner = $assigner;
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
        return [
            'type' => 'disposition_assigned',
            'title' => 'Tugas Analisis Baru',
            'message' => "Laporan {$this->report->ticket_number} baru saja ditugaskan kepada Anda oleh {$this->assigner->name}.",
            'report_id' => $this->report->id,
            'report_uuid' => $this->report->uuid,
            'url' => route('reports.show', $this->report->uuid),
            'icon' => 'ti ti-file-symlink',
        ];
    }
}
