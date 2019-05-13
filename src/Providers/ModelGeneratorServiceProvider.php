<?php
namespace ModelGenerator\Providers;
use Illuminate\Support\ServiceProvider;
class ModelGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \ModelGenerator\Commands\GenerateModel::class,
            ]);
        }
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}