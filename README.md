# Laravel Review

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fajarwz/laravel-review.svg?style=flat-square)](https://packagist.org/packages/fajarwz/laravel-review)
[![GitHub Tests Action Status](https://github.com/fajarwz/laravel-review/actions/workflows/run-tests.yml/badge.svg)](https://github.com/fajarwz/laravel-review/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://github.com/fajarwz/laravel-review/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/fajarwz/laravel-review/actions/workflows/fix-php-code-style-issues.yml)
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/fajarwz/laravel-review.svg?style=flat-square)](https://packagist.org/packages/fajarwz/laravel-review) -->

Effortlessly add review functionality to any Laravel model with this flexible and powerful review system.

## Installation

You can install the package via composer:

```bash
composer require fajarwz/laravel-review
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-review_migrations"
php artisan migrate
```

## Setup

### Models Setup
Include the necessary traits in your models:

#### Reviewed/Reviewable Model
For models that can be reviewed, use the `CanBeReviewed` trait:

```php
use Fajarwz\LaravelReview\CanBeReviewed;

class Mentor extends Model
{
    use CanBeReviewed;
    // Optionally, use CanReview if the model can also act as a reviewer
    // use CanReview;
}
```

#### Reviewer Model
For models that can submit reviews, use the `CanReview` trait:

```php
use Fajarwz\LaravelReview\CanReview;

class Mentee extends Model
{
    use CanReview;
}
```

## Usage

### Creating a Review

To create a new review:

```php
$mentee = Mentee::find(1);
$mentor = Mentor::find(1);

// Create an approved review
$mentee->review($mentor, 4.5, 'Great mentor!');

// Create an unapproved review
$mentee->review($mentor, 3.0, 'Needs improvement', false);
```

The `review()` method requires the reviewable model, rating, and review content. Optionally, set the `$isApproved` parameter to `false` to create an unapproved review.

Only approved reviews are calculated in the `review_summaries` table. Updating an unapproved review will not affect the summary.

If the reviewer model has already submitted a review for the same reviewable model, a `Fajarwz\LaravelReview\Exceptions\DuplicateReviewException` will be thrown.

To update a review, use `updateReview()` instead.

### Updating a review

To update an existing review:

```php
$mentee->updateReview($mentor, 5, 'Mentor is even better now!');
```

### Unreviewing a model

To cancel an existing review:

```php
$mentee->unreview($mentor);
```

If the reviewer model has not previously reviewed the model, a `Fajarwz\LaravelReview\Exceptions\ReviewNotFoundException` will be thrown.

### Approving a Review

To approve a review:

```php
$review = $mentor->receivedReviews()->first();
$review->approve();
```

### Unapproving a Review

To unapprove a review:

```php
$review = $mentor->receivedReviews()->first();
$review->unapprove();
```

### Querying Reviews

#### Get all received reviews

By default, only approved reviews are retrieved:

```php
$mentor->receivedReviews()->get();
```

To include both approved and unapproved reviews:

```php
$mentor->receivedReviews()->withUnapproved()->get();
```

To include reviewer information:

```php
Mentor::with('receivedReviews.reviewer')->paginate();
```

This query will eager load the reviewer information for each received review.

**Note:** Consider using appropriate eager loading strategies based on your application's needs to optimize query performance.

#### Get all given reviews

To get all reviews given by a model:

```php
$mentee->givenReviews()->get();
```

To include reviewable model information:

```php
Mentee::with('givenReviews.reviewable')->paginate();
```

This will eager load the reviewable model for each review given by the model.

### Review Model

The `Fajarwz\LaravelReview\Models\Review` model includes methods for managing and querying reviews:

#### Approve a Review

To approve a review, use the `approve()` method. This sets the `approved_at` timestamp to the current date and time, indicating that the review has been approved. It also updates the review summary of the associated model.

```php
use Fajarwz\LaravelReview\Models\Review;

$review = Review::find($id);
$review->approve();
```

#### Unapprove a Review

To unapprove a review, use the `unapprove()` method. This sets the `approved_at` timestamp to null, indicating that the review is no longer approved. It also updates the review summary of the associated model to decrement the rating count if necessary.

```php
$review = Review::find($id);
$review->unapprove();
```

#### Check If a Review Is Approved

To check if a review is approved, use the `isApproved()` method. This method returns true if the `approved_at` timestamp is not null, and false otherwise.

```php
$review = Review::find($id);
if ($review->isApproved()) {
    // The review is approved
}
```

#### Query Approved Reviews

By default, the Review model applies a global scope to only include approved reviews. To query only approved reviews, you can use the model's standard query methods:

```php
$approvedReviews = Review::all();
```

#### Query Unapproved Reviews

To include unapproved reviews in your query, use the `withUnapproved()` method. This removes the global scope that filters out unapproved reviews, allowing you to query both approved and unapproved reviews.

```php
$allReviews = Review::withUnapproved()->get();
```

#### Get the Reviewer

To get the model that reviewed the item, use the `reviewer()` method. This method returns a polymorphic relationship to the reviewer model.

```php
$review = Review::find($id);
$reviewer = $review->reviewer;
```

#### Get the Reviewable Model

To get the model that was reviewed, use the `reviewable()` method. This method returns a polymorphic relationship to the reviewed model.

```php
$review = Review::find($id);
$reviewable = $review->reviewable;
```

### ReviewSummary Model

The `Fajarwz\LaravelReview\Models\ReviewSummary` model represents a summary of reviews for a specific reviewable model.

#### Attributes

- `average_rating`: The average rating of all reviews for the reviewable model.
- `review_count`: The total number of reviews for the reviewable model.

#### Get the Reviewable Model

The `reviewable()` method defines a polymorphic relationship to the model that is being reviewed. It allows you to access the model that this review summary belongs to.

```php
use Fajarwz\LaravelReview\Models\ReviewSummary;

$reviewSummary = ReviewSummary::find($id);
$reviewable = $reviewSummary->reviewable;
```

## Testing

```bash
# Use PHPStan to perform static analysis
composer analyse

# Execute PHPUnit tests
composer test

# Use Pint to format your code
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

We welcome contributions. Please open [issues](https://github.com/fajarwz/laravel-review/issues) or [pull requests](https://github.com/fajarwz/laravel-review/pulls) with your suggestions or improvements.

## Getting Help

For questions, discussions, or seeking assistance, please use the [GitHub Discussions](https://github.com/fajarwz/laravel-review/discussions) forum. This will help keep issues focused on bug reports and feature requests.

## Security Vulnerabilities

Please contact [hi@fajarwz.com](mailto:hi@fajarwz.com)

## Credits

- [fajarwz](https://github.com/fajarwz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
