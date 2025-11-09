<?php

namespace App\Domains\FollowUps\Notifications;

use App\Domains\FollowUps\Models\FollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowUpDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly FollowUp $followUp)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $client = $this->followUp->client;
        $dueDate = optional($this->followUp->due_date)->timezone(config('app.timezone'))->format('M d, Y H:i');

        return (new MailMessage())
            ->subject('Follow-up Due: '.($client?->name ?? 'Client'))
            ->line('A follow-up task is due.')
            ->line('Client: '.($client?->name ?? 'Unknown'))
            ->line('Due: '.$dueDate)
            ->action('View Follow-up', url('/app/clients/'.$this->followUp->client_id.'/follow-ups/'.$this->followUp->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'follow_up_id' => $this->followUp->id,
            'client_id' => $this->followUp->client_id,
            'due_date' => optional($this->followUp->due_date)->toISOString(),
            'status' => $this->followUp->status,
        ];
    }
}
