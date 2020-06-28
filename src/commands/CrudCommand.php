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
                                {--fields= : Fields name for the form & migration.}
                                {--fields_from_file= : Fields from a json file.}
                                {--validations= : Validation details for the fields.}
                                {--controller-namespace= : Namespace of the controller.}
                                {--model-namespace= : Namespace of the model inside "app" dir}
                                {--pk=id : The name of the primary key.}
                                {--pagination=10 : The amount of models per page for index pages.}
                                {--indexes= : The fields to add an index to.}
                                {--foreign-keys= : The foreign keys for the table}
                                {--relationships= : The relationships for the model}
                                {--route=yes : Include Crud route to routes.php? yes|no.}
                                {--route-group= : Prefix of the route group.}
                                {--view-path= : The name of the view path.}
                                {--localize=no : Allow localize? yes|no. }
                                {--locales=en : Locales}';

    // php artisan crud:generate Posts
        // --fields='title#string; content#text; category#select#options={"technology": "Technology", "tips": "Tips", "health": "Health"}'
        // --view-path=admin
        // --controller-namespace=Admin
        // --route-group=admin
        // --form-helper=html


    protected $description = 'Command description';
    protected $routeName = '';
    protected $controller = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $name = $this->argument('name'); // Posts
        $modelName = Str::singular($name); // Post
        $migrationName = Str::plural(Str::snake($name)); // posts
        $tableName = $migrationName; // posts

        $routeGroup = $this->option('route-group'); // optional
        $this->routeName = ($routeGroup)
                                ? $routeGroup . '/' . Str::snake($name,'-')
                                : Str::snake($name, '-');

        $perPage = intval($this->option('pagination')); // 10

        $controllerNamespace = ($this->option('controller-namespace'))
                                        ? $this->option('controller-namespace') . '\\'
                                        : '';
        $modelNamespace = ($this->option('model-namespace'))
                                    ? trim($this->option('model-namespace')) . '\\'
                                    : '';

        $fields = rtrim($this->option('fields'), ';');

        if ($this->option('fields_from_file')) {
            $fields = $this->processJSONFields($this->option('fields_from_file'));
        }

        $primaryKey = $this->option('pk');
        $viewPath = $this->option('view-path');

        $foreignKeys = $this->option('foreign-keys');

        $fieldsArray = explode(';', $fields);
        $fillableArray = [];

        foreach ($fieldsArray as $item) {
            // $fieldsArray[0] = item = 'title#string#required'
            // $fieldsArray[1] = item = 'content#text#required'
            // $fieldsArray[2] = item = 'status#boolean'

            $temp_arr = explode('#', trim($item));
            $fillableArray[] = $temp_arr[0];
        }
        // $fillableArray[0]=title
        // $fillableArray[1]=content
        // $fillableArray[2]=status

        $comma_separeted_str = implode("', '", $fillableArray);
        $fillable = "['" . $comma_separeted_str .  "']";
        // $fillable = "['title','content','status']"

        $localize = $this->option('localize');
        $locales = $this->option('locales');

        $indexes = $this->option('indexes'); // null by default
        $relationships = $this->option('relationships'); // null by default
        $validations = trim($this->option('validations')); // null by default

        $this->call('crud:controller', [
            'name' => $controllerNamespace . $name . 'Controller',
            '--crud-name' => $name,
            '--model-name' => $modelName,
            '--model-namespace' => $modelNamespace,
            '--view-path' => $viewPath,
            '--route-group' => $routeGroup,
            '--pagination' => $perPage,
            '--fields' => $fields,
            '--validations' => $validations
        ]);
        // dd('Controller Created');

        $this->call('crud:model', [
            'name' => $modelNamespace . $modelName,
            '--fillable' => $fillable,
            '--table' => $tableName,
            '--pk' => $primaryKey,
            '--relationships' => $relationships
        ]);
         // dd('Model Created');

        $this->call('crud:migration', [
            'name' => $migrationName,
            '--schema' => $fields,
            '--pk' => $primaryKey,
            '--indexes' => $indexes,
            '--foreign-keys' => $foreignKeys
        ]);
        // dd('Migration Created');

        $this->call('crud:view', [
            'name' => $name,
            '--fields' => $fields,
            '--validations' => $validations,
            '--view-path' => $viewPath,
            '--route-group' => $routeGroup,
            '--localize' => $localize,
            '--pk' => $primaryKey
        ]);
        // dd('View Created');

        // no by default
        if($localize == 'yes') {
            $this->call('crud:lang', ['name' => $name, '--fields' => $fields, '--locales' => $locales]);
        }
        // optimizing
        // $this->callSilent('optimize');

        $route_file = base_path('routes/web.php');
        if ( file_exists($route_file) && (strtolower($this->option('route')) === 'yes') )  {

            $this->controller = ($controllerNamespace != '')
                ? $controllerNamespace . '\\' . $name . 'Controller'
                : $name . 'Controller';

            $isAdded = File::append($route_file, "\n".implode("\n", $this->addRoutes()));

            if ($isAdded) {
                $this->info('Routes added to '. $route_file .'.');
            } else {
                $this->info('Unable to add routes to '. $route_file .'.');
            }

        }

        // File::append( base_path('routes/web.php') , "\nRoute::resource('" . strtolower($name) . "','" . $name . "Controller');" );

        // dd('dont migrate');

        // migrate
       $this->call('migrate');

    }

    protected function processJSONFields($file){
        $json = File::get($file);
        $fields = json_decode($json);

        $fieldsString = '';
        foreach ($fields->fields as $field) {
            if ($field->type == 'select') {
                $fieldsString .= $field->name . '#' . $field->type . '#options=' . implode(',', $field->options) . ';';
            } else {
                $fieldsString .= $field->name . '#' . $field->type . ';';
            }
        }

        $fieldsString = rtrim($fieldsString, ';');

        return $fieldsString;
    }

    protected function addRoutes() {
        return ["Route::resource('" . $this->routeName . "', '" . $this->controller . "');"];
    }

}
