<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\RecipeBuilder;
use App\Livewire\Admin\RecipeReviewQueue;
use App\Livewire\RecipeBook;
use App\Livewire\RecipeShow;

Route::get('/', RecipeBook::class)->name('home');
Route::get('/recipes/{recipe}', RecipeShow::class)->name('recipes.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('admin/recipes', RecipeBuilder::class)->name('admin.recipes');
    Route::get('admin/recipes/review', RecipeReviewQueue::class)->name('admin.recipes.review');
});

require __DIR__.'/settings.php';
