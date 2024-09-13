<?php

namespace Fajarwz\LaravelReview\Models;

use Fajarwz\LaravelReview\Scopes\ApprovedReviewsScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Rennokki\QueryCache\Traits\QueryCacheable;

/**
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property float $rating
 */
class Review extends Model
{
    // Query Cache
    use QueryCacheable;

    protected $fillable = [
        'reviewer_id',
        'reviewer_type',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'content',
        'approved_at',
    ];

    protected static function booted2()
    {
        static::addGlobalScope(new ApprovedReviewsScope);
    }

    /**
     * Scope a query to include unapproved reviews.
     */
    public function scopeWithUnapproved(Builder $query): void
    {
        $query->withoutGlobalScope(ApprovedReviewsScope::class);
    }

    /**
     * Checks if the review is approved.
     *
     * A review is considered approved if the `approved_at` timestamp is not null.
     *
     * @return bool True if the review is approved, false otherwise.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Approves a review.
     *
     * Sets the `approved_at` timestamp to indicate approval and updates the review summary.
     */
    public function approve(): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        DB::transaction(function () {
            $this->approved_at = now();
            $this->save();

            $params = [
                'rating' => $this->rating,
            ];
            /** @phpstan-ignore-next-line */
            $this->reviewable->updateReviewSummary($params);
        });

        return true;
    }

    /**
     * Unapproves a review.
     *
     * Sets the `approved_at` timestamp to null and updates the review summary.
     */
    public function unapprove(): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        DB::transaction(function () {
            $this->approved_at = null;
            $this->save();

            $params = [
                'rating' => $this->rating,
                'decrement' => true,
            ];
            /** @phpstan-ignore-next-line */
            $this->reviewable->updateReviewSummary($params);
        });

        return true;
    }

    /**
     * Get the owning reviewer model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reviewer()
    {
        return $this->morphTo();
    }

    /**
     * Get the owning reviewable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reviewable()
    {
        return $this->morphTo();
    }


    /**
     * Specify the amount of time to cache queries.
     * Do not specify or set it to null to disable caching.
     *
     * @var int|\DateTime
     */
    protected $cacheFor = 604800; // 1 week

    /**
     * The tags for the query cache. Can be useful
     * if flushing cache for specific tags only.
     *
     * @var array|null
     */
    public $cacheTags = ['reviews'];

    /**
     * A cache prefix string that will be prefixed
     * on each cache key generation.
     *
     * @var string
     */
    public $cachePrefix = 'reviews_';

    /**
     * The cache driver to be used.
     *
     * @var string
     */
    // public $cacheDriver = 'dynamodb';

    /**
     * Set the base cache tags that will be present
     * on all queries.
     */
    protected function getCacheBaseTags(): array
    {
        return [
            'custom_review_tag',
        ];
    }

    /**
     * When invalidating automatically on update, you can specify
     * which tags to invalidate.
     *
     * @param string|null $relation
     * @param \Illuminate\Database\Eloquent\Collection|null $pivotedModels
     */
    public function getCacheTagsToInvalidateOnUpdate($relation = null, $pivotedModels = null): array
    {
        return [
            "review:{$this->id}",
            'reviews',
        ];
    }

    /**
     * Specify the amount of time to cache queries.
     * Set it to null to disable caching.
     *
     * @return int|\DateTime
     */
    protected function cacheForValue()
    {
        // is local
        if (app()->environment('local')) {
            // return null;
        }

        return $this->cacheFor;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new ApprovedReviewsScope);
        static::created(function (Review $agent) {
            // ...
            $agent::flushQueryCache(['reviews']);
        });
        // Update
        static::saved(function (Review $agent) {
            // ...
            $agent::flushQueryCache(['reviews']);
        });

        // Delete
        static::deleted(function (Review $agent) {
            // ...
            $agent::flushQueryCache(['reviews']);
        });
    }

    // flushQueryCacheItem
    public function flushQueryCacheItem()
    {
        $cacheKeyAgent = 'review_cache_for';
        // Delete cache
        cache()->forget($cacheKeyAgent);

        return true;
    }

    protected function getCacheForKey()
    {
        return 'review_cache_for';
    }

    /**
     * The tags for the query cache. Can be useful
     * if flushing cache for specific tags only.
     *
     * @return array|null
     */
    protected function cacheTagsValue()
    {
        return ['reviews'];
    }
}
