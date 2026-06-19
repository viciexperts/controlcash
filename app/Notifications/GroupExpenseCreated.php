<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupExpenseCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly Expense $expense)
    {
        $this->expense->loadMissing(['group', 'owner', 'payer', 'category']);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->expense->amount, 2);

        return (new MailMessage)
            ->subject('Nuevo gasto en '.$this->expense->group->name)
            ->greeting('Hola '.$notifiable->name)
            ->line($this->expense->owner->name.' agrego un gasto al grupo '.$this->expense->group->name.'.')
            ->line('Descripcion: '.$this->expense->description)
            ->line('Monto: DOP '.$amount)
            ->line('Fecha: '.$this->expense->expense_date->format('Y-m-d'))
            ->line('Categoria: '.($this->expense->category?->name ?? 'Sin categoria'))
            ->line('Pagado por: '.($this->expense->payer?->name ?? $this->expense->owner->name))
            ->action('Ver gastos', route('expenses.index'))
            ->line('Este gasto queda visible para los miembros del grupo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'expense_id' => $this->expense->id,
            'group_id' => $this->expense->group_id,
        ];
    }
}
