<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Item>  $items
     */
    public function __construct(public Collection $items)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Vigie — {$this->items->count()} article(s) pour toi",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.digest',
            with: ['items' => $this->items],
        );
    }
}
