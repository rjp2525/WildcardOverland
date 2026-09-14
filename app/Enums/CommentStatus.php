<?php

namespace App\Enums;

/**
 * Where a comment is in the queue.
 *
 * Nothing reaches the page on its own. Spam is kept rather than deleted, so
 * the same address turning up again is something we can see.
 */
enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting',
            self::Approved => 'On the page',
            self::Spam => 'Spam',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
