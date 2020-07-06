<?php

namespace Oy;

use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{

    public function register()
    {

        // add related data in controller
        // $post->user_id = Auth()->id

        /*
            php artisan crud:generate Posts
                --fields=user_id#unsignedbiginteger;title#string;body#text#nullable;status#boolean;categories#select#options={software:Software,hardware:Hardware,notebok:Notebook,tablet:Tablet}
                --fields_from_file
                --validations=title#required,unique:posts,max:123;body#required,max:255;status#required
                --controller-namespace=Admin
                --model-namespace
                --pk=id
                --pagination=10
                --indexes=title
                --foreign-keys=user_id#id#users#cascade
                --relationships=user#belongsTo#App\User,comments#hasMany#App\Comment
                --route=yes
                --route-group=admin
                --view-path=admin
                --localize=no
                --locales=en
                --soft-deletes=yes
         */

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
