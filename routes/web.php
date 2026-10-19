<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClerkAuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavedListingController;
use App\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Clerk-hosted sign-in (Google etc.) and sign-up; Clerk uses hash routing.
Route::inertia('sign-in', 'clerk/sign-in')->name('clerk.sign-in');
Route::inertia('sign-up', 'clerk/sign-up')->name('clerk.sign-up');

Route::post('auth/clerk', [ClerkAuthController::class, 'store'])->middleware('throttle:auth')->name('clerk.session');

// Public browsing
Route::get('plans', [PlanController::class, 'index'])->name('plans');
Route::get('sellers/{seller}', [SellerController::class, 'show'])->whereNumber('seller')->name('sellers.show');

Route::middleware('auth')->group(function () {
    // Listings (static paths first so they are not captured by {listing})
    Route::get('sell', [ListingController::class, 'create'])->name('listings.create');
    Route::get('my/listings', [ListingController::class, 'mine'])->name('listings.mine');
    Route::post('listings', [ListingController::class, 'store'])->name('listings.store');
    Route::get('listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
    Route::post('listings/{listing}/update', [ListingController::class, 'update'])->name('listings.update');
    Route::delete('listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
    Route::post('listings/{listing}/status/{action}', [ListingController::class, 'status'])->name('listings.status');

    // Social
    Route::post('listings/{listing}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('listings/{listing}/reaction', [ReactionController::class, 'listing'])->name('listings.react');
    Route::post('comments/{comment}/reaction', [ReactionController::class, 'comment'])->name('comments.react');
    Route::post('listings/{listing}/save', [SavedListingController::class, 'toggle'])->name('listings.save');
    Route::get('saved', [SavedListingController::class, 'index'])->name('saved');

    // Sellers & reviews
    Route::get('seller/apply', [SellerController::class, 'apply'])->name('seller.apply');
    Route::post('seller/apply', [SellerController::class, 'store'])->name('seller.store');
    Route::post('sellers/{seller}/reviews', [SellerController::class, 'review'])->whereNumber('seller')->name('sellers.review');

    // Chat
    Route::get('inbox/{conversation?}', [ConversationController::class, 'index'])->whereNumber('conversation')->name('inbox');
    Route::post('conversations', [ConversationController::class, 'start'])->name('conversations.start');
    Route::post('conversations/{conversation}/messages', [ConversationController::class, 'send'])->name('conversations.send');

    // Subscriptions
    Route::get('plans/{plan:slug}/checkout', [PlanController::class, 'checkout'])->name('plans.checkout');
    Route::post('plans/{plan:slug}/checkout', [PlanController::class, 'store'])->middleware('throttle:10,1')->name('plans.store');
    Route::post('listings/{listing}/boost', [ListingController::class, 'boost'])->name('listings.boost');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{notification}/open', [NotificationController::class, 'read'])->name('notifications.open');

    Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('reports.store');
});

// Admin moderation
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::put('reports/{report}', [AdminController::class, 'updateReport'])->name('reports.update');
    Route::put('sellers/{seller}', [AdminController::class, 'updateSeller'])->name('sellers.update');
    Route::post('users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::post('categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('payments/{subscription}/confirm', [AdminController::class, 'confirmPayment'])->name('payments.confirm');
    Route::post('payments/{subscription}/reject', [AdminController::class, 'rejectPayment'])->name('payments.reject');
});

// Listing detail last: it is a catch-all on a single segment under /listings
Route::get('listings/{listing}', [ListingController::class, 'show'])->name('listings.show');

Route::get('dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

require __DIR__.'/settings.php';
