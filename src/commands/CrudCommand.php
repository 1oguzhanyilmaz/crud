<?php

namespace Oy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class CrudCommand extends Command
{

    protected $signature = 'crud:generate
                                {name : The name of the Crud.}
                                {--fields= : Fields name for the form & model.}
                                {--route=yes : Include Crud route to routes.php? yes|no.}
                                {--pk=id : The name of the primary key.}
                                {--view-path= : The name of the view path.}
                                {--namespace= : Namespace of the controller.}';

    // php artisan crud:generate Posts
        // --fields='title#string; content#text; category#select#options={"technology": "Technology", "tips": "Tips", "health": "Health"}'
        // --view-path=admin
        // --controller-namespace=Admin
        // --route-group=admin
        // --form-helper=html


    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name');
        $controllerNamespace = ($this->option('namespace')) ? $this->option('namespace') . '\\' : '';

        if($this->option('fields') ) {

            $fields = $this->option('fields'); // fields = ['title:string','content:text']
            $primaryKey = $this->option('pk');
            $viewPath = $this->option('view-path');


            $fillable_array = explode(',', $fields);
            foreach ($fillable_array as $value) {
                $data[] = preg_replace("/(.*?):(.*)/", "$1", trim($value));
            }
            // dd($data); // ['title','content']

            $comma_separeted_str = implode("', '", $data);
            $fillable = "['" . $comma_separeted_str .  "']";
            $this->call('crud:controller', ['name' => $controllerNamespace . $name . 'Controller', '--crud-name' => $name, '--view-path' => $viewPath]);
            $this->call('crud:model', ['name' => $name, '--fillable' => $fillable, '--table' => Str::plural(strtolower($name))]);
            $this->call('crud:migration', ['name' => Str::plural(strtolower($name)), '--schema' => $fields, '--pk' => $primaryKey]);
            $this->call('crud:view', ['name' => $name, '--fields' => $fields, '--view-path' => $viewPath]);
        }else {
            $this->call('make:controller', ['name' => $controllerNamespace . $name . 'Controller']);
            $this->call('make:model', ['name' => $name]);
        }

        $route_file = base_path('routes/web.php');
        if ( file_exists($route_file) && (strtolower($this->option('route')) === 'yes') )  {

            $controller = ($controllerNamespace != '')
                ? $controllerNamespace . '\\' . $name . 'Controller'
                : $name . 'Controller';
            $isAdded = File::append($route_file, "\nRoute::resource('" . strtolower($name) . "', '" . $controller . "');");

            if ($isAdded) {
                $this->info('Routes added to '. $route_file .'.');
            } else {
                $this->info('Unable to add routes to '. $route_file .'.');
            }

        }

        // File::append( base_path('routes/web.php') , "\nRoute::resource('" . strtolower($name) . "','" . $name . "Controller');" );

        dd('dont migrate');

        // migrate
       $this->call('migrate');

    }

}
