<?php

namespace Oy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudCommand extends Command
{

    protected $signature = 'crud:generate
                                {name : The name of the Crud.}
                                {--fields= : Field names for the form & migration.}
                                {--fields_from_file= : Fields from a json file.}
                                {--validations= : Validation rules for the fields.}
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
                                {--locales=en : Locales language}
                                {--soft-deletes=no : Include soft deletes fields.}';

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
        }else{
            $fields = str_replace(',', '","', $fields);
            $fields = str_replace(':', '":"', $fields);
            $fields = str_replace('{', '{"', $fields);
            $fields = str_replace('}', '"}', $fields);
        }

        $primaryKey = $this->option('pk');
        $viewPath = $this->option('view-path');

        $foreignKeys = $this->option('foreign-keys');

        if ($this->option('fields_from_file')) {
            $foreignKeys = $this->processJSONForeignKeys($this->option('fields_from_file'));
        }

        $localize = $this->option('localize');
        $locales = $this->option('locales');

        $indexes = $this->option('indexes'); // null by default
        $relationships = $this->option('relationships'); // null by default
        if ($this->option('fields_from_file')) {
            $relationships = $this->processJSONRelationships($this->option('fields_from_file'));
        }

        $validations = trim($this->option('validations')); // null by default
        if ($this->option('fields_from_file')) {
            $validations = $this->processJSONValidations($this->option('fields_from_file'));
        }

        $softDeletes = $this->option('soft-deletes');
        $fieldsArray = explode(';', $fields);
        $fillableArray = [];
        $migrationFields = '';

        foreach ($fieldsArray as $item) {
            // $fieldsArray[0] = item = 'title#string#required'
            // $fieldsArray[1] = item = 'content#text#required'
            // $fieldsArray[2] = item = 'status#boolean'

            $temp_arr = explode('#', trim($item));
            $fillableArray[] = $temp_arr[0];
            $modifier = !empty($temp_arr[2])
                                ? $temp_arr[2]
                                : '';

            $migrationFields .= $temp_arr[0] . '#' . $temp_arr[1];
            $migrationFields .= '#' . $modifier;
            $migrationFields .= ';';
            // Process migration fields
        }
        // $fillableArray[0]=title
        // $fillableArray[1]=content
        // $fillableArray[2]=status

        $comma_separeted_str = implode("', '", $fillableArray);
        $fillable = "['" . $comma_separeted_str .  "']";
        // $fillable = "['title','content','status']"

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
            '--relationships' => $relationships,
            '--soft-deletes' => $softDeletes
        ]);
        // dd('Model Created');

        $this->call('crud:migration', [
            'name' => $migrationName,
            '--schema' => $migrationFields,
            '--pk' => $primaryKey,
            '--indexes' => $indexes,
            '--foreign-keys' => $foreignKeys,
            '--soft-deletes' => $softDeletes
        ]);
        // dd('Migration Created');

        $this->call('crud:view', [
            'name' => $name,
            '--fields' => $fields,
            '--validations' => $validations,
            '--view-path' => $viewPath,
            '--route-group' => $routeGroup,
            '--pk' => $primaryKey,
            '--localize' => $localize,
            '--foreign-keys' => $foreignKeys,
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

        // dd('dont migrate');

       $this->call('migrate');

    }

    protected function processJSONFields($file){
        $json = File::get($file);
        $fields = json_decode($json, true);

        $fieldsString = '';
        foreach ($fields['fields'] as $field) {
            if ($field['type'] === 'select' || $field['type'] === 'enum') {
                $fieldsString .= $field['name'] . '#' . $field['type'] . '#options=' . json_encode($field['options']) . ';';
            } else {
                $fieldsString .= $field['name'] . '#' . $field['type'] . ';';
            }
        }

        $fieldsString = rtrim($fieldsString, ';');

        return $fieldsString;
    }

    protected function processJSONForeignKeys($file){
        $json = File::get($file);
        $fields = json_decode($json, true);

        if (!array_key_exists('foreign_keys', $fields)) {
            return '';
        }

        $foreignKeysString = '';
        foreach ($fields['foreign_keys'] as $foreign_key) {
            $foreignKeysString .= $foreign_key['column'] . '#' . $foreign_key['references'] . '#' . $foreign_key['on'];

            if (array_key_exists('onDelete', $foreign_key)) {
                $foreignKeysString .= '#' . $foreign_key['onDelete'];
            }

            if (array_key_exists('onUpdate', $foreign_key)) {
                $foreignKeysString .= '#' . $foreign_key['onUpdate'];
            }

            $foreignKeysString .= ',';
        }

        $foreignKeysString = rtrim($foreignKeysString, ',');

        return $foreignKeysString;
    }

    protected function processJSONRelationships($file){
        $json = File::get($file);
        $fields = json_decode($json, true);

        if (!array_key_exists('relationships', $fields)) {
            return '';
        }

        $relationsString = '';
        foreach ($fields['relationships'] as $relation) {
            $relationsString .= $relation['name'] . '#' . $relation['type'] . '#' . $relation['class'] . ';';
        }

        $relationsString = rtrim($relationsString, ';');

        return $relationsString;
    }

    protected function processJSONValidations($file){
        $json = File::get($file);
        $fields = json_decode($json, true);

        if (!array_key_exists('validations', $fields)) {
            return '';
        }

        $validationsString = '';
        foreach ($fields['validations'] as $validation) {
            $validationsString .= $validation['field'] . '#' . $validation['rules'] . ';';
        }

        $validationsString = rtrim($validationsString, ';');

        return $validationsString;
    }

    protected function addRoutes() {
        return ["Route::resource('" . $this->routeName . "', '" . $this->controller . "');"];
    }

}
