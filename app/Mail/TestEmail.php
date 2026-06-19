<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestEmail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prueba de correo de ControlCash',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>ControlCash pudo enviar este correo de prueba desde Render.</p>',
        );
    }
}
