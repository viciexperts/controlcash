<?php

namespace App\Notifications;

use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly GroupInvitation $invitation)
    {
        $this->invitation->loadMissing(['group', 'inviter']);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->invitation->inviter->name.' te invito a ControlCash')
            ->greeting('Hola')
            ->line($this->invitation->inviter->name.' te invito a unirte al grupo '.$this->invitation->group->name.' en ControlCash.')
            ->line('Cuando te registres con este correo, entraras automaticamente al grupo.')
            ->action('Crear cuenta', route('register'))
            ->line('Usa este mismo correo para aceptar la invitacion: '.$this->invitation->email);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'group_id' => $this->invitation->group_id,
            'email' => $this->invitation->email,
        ];
    }
}
