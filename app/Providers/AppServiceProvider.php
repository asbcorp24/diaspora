<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('admin.index', function ($view): void {
            $sections = $view->getData()['sections'] ?? [];
            if (isset($sections['letters'])) {
                $sections['letters'] = 'Юридическая помощь';
            }
            $view->with('sections', $sections);
        });
    }
}
