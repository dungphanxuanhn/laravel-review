<?php

namespace Fajarwz\LaravelReview\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class ReviewSummary extends Model
{
    // Query Cache
    use QueryCacheable;

    protected $fillable = [
        'average_rating',
        'review_count',
    ];

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
    protected $cacheFor = 604800; // 1 week = 604800 seconds

    /**
     * The tags for the query cache. Can be useful
     * if flushing cache for specific tags only.
     *
     * @var array|null
     */
    public $cacheTags = ['review_summarys'];

    /**
     * A cache prefix string that will be prefixed
     * on each cache key generation.
     *
     * @var string
     */
    public $cachePrefix = 'review_summarys_';

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
            'custom_review_summary_tag',
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
            "review_summary:{$this->id}",
            'review_summarys',
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
        static::created(function (ReviewSummary $agent) {
            // ...
            $agent::flushQueryCache(['review_summarys']);
        });
        // Update
        static::saved(function (ReviewSummary $agent) {
            // ...
            $agent::flushQueryCache(['review_summarys']);
        });

        // Delete
        static::deleted(function (ReviewSummary $agent) {
            // ...
            $agent::flushQueryCache(['review_summarys']);
        });
    }

    // flushQueryCacheItem
    public function flushQueryCacheItem()
    {
        $cacheKeyAgent = 'review_summary_cache_for';
        // Delete cache
        cache()->forget($cacheKeyAgent);

        return true;
    }

    protected function getCacheForKey()
    {
        return 'review_summary_cache_for';
    }

    /**
     * The tags for the query cache. Can be useful
     * if flushing cache for specific tags only.
     *
     * @return array|null
     */
    protected function cacheTagsValue()
    {
        return ['review_summarys'];
    }
}
