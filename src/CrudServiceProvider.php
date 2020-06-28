<?php

namespace Oy;

use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{

    public function register()
    {
        // migrate => add bigInteger
        // $table->bigInteger('user_id')->unsigned();

        // add related data in controller
        // $post->user_id = Auth()->id

        // php artisan crud:generate Post
            // --fields=user_id#bigint;title#string;content#text;status#boolean
            // --view-path=admin
            // --controller-namespace=Admin
            // --route-group=admin
            // --validations=title#required,unique:posts,max:255;content#required,max:255
            // --relationships=user#belongsTo#App\User,comments#hasMany#App\Comment
            // --foreign-keys=comment_id#id#comments#cascade
        $this->commands(
            'Oy\Commands\CrudCommand',
            'Oy\Commands\CrudControllerCommand',
            'Oy\Commands\CrudModelCommand',
            'Oy\Commands\CrudMigrationCommand',
            'Oy\Commands\CrudViewCommand',
            'Oy\Commands\CrudLangCommand'
        );
    }

    public function boot()
    {
        $this->publishes([
           dirname( __DIR__) . '/config/crudgenerator.php' => config_path('crudgenerator.php'),
        ]);

        $this->publishes([
            dirname(__DIR__) . '/publish/views/' => base_path('resources/views/'),
        ]);

        $this->publishes([
            __DIR__ . '/stubs/' => base_path('resources/crud-generator/'),
        ]);
    }
}
