<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public OrganizationInvitation $invitation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->invitation->organization;
        $inviter = $this->invitation->invitedBy;

        return (new MailMessage)
            ->subject("You're invited to join {$organization->name}")
            ->greeting("Hello!")
            ->line("You've been invited by {$inviter->name} to join {$organization->name}.")
            ->line("Click the button below to accept the invitation and create your account.")
            ->action('Accept Invitation', route('invitations.accept', $this->invitation->token))
            ->line("This invitation will expire in 7 days.")
            ->line("If you didn't expect this invitation, you can safely ignore this email.")
            ->salutation("Best regards,\nThe {$organization->name} Team");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'organization_id' => $this->invitation->organization_id,
            'invited_by_user_id' => $this->invitation->invited_by_user_id,
            'email' => $this->invitation->email,
        ];
    }
}
