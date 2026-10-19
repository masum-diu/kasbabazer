<?php

namespace App\Services;

use App\Models\Review;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * One review per reviewer per seller; submitting again updates it.
     */
    public function upsert(User $reviewer, User $seller, int $rating, ?string $text): Review
    {
        if ($reviewer->id === $seller->id) {
            throw ValidationException::withMessages(['rating' => ['You cannot review yourself.']]);
        }

        $review = Review::updateOrCreate(
            ['seller_id' => $seller->id, 'reviewer_id' => $reviewer->id],
            ['rating' => $rating, 'review' => $text],
        );

        $this->notifications->notify($seller, $reviewer, 'review', "{$reviewer->name} rated you {$rating}/5", route('sellers.show', $seller, false), $text);

        return $review;
    }

    /**
     * @return array{average: float, count: int}
     */
    public function summary(User $seller): array
    {
        $row = Review::where('seller_id', $seller->id)->where('status', 'visible')
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')->first();

        return ['average' => round((float) ($row->a ?? 0), 1), 'count' => (int) ($row->c ?? 0)];
    }
}
