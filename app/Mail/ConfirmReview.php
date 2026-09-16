<?php

namespace App\Mail;

use App\Models\RecipeComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * The one email this site sends to a stranger.
 *
 * It goes only to the address typed into the form, says only what it is for,
 * and asks for nothing except a click. Anybody who gets one they did not ask
 * for can throw it away and nothing happens - which is the other half of why
 * the review does not go anywhere until the link is followed.
 */
class ConfirmReview extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RecipeComment $review) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your review of '.$this->review->recipe?->name,
            replyTo: array_filter([config('site.email')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.confirm-review',
            with: [
                'recipe' => $this->review->recipe,
                'url' => $this->link(),
                'days' => (int) config('feedback.comments.confirm_days'),
            ],
        );
    }

    /**
     * Signed, so it cannot be guessed or edited into somebody else's review,
     * and dated, so an address that changes hands later is not still holding
     * a working link.
     */
    protected function link(): string
    {
        return URL::temporarySignedRoute(
            'recipes.reviews.confirm',
            now()->addDays((int) config('feedback.comments.confirm_days')),
            ['recipe' => $this->review->recipe?->slug, 'review' => $this->review->getKey()],
        );
    }
}
