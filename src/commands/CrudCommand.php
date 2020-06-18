<?php

namespace Oy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CrudCommand extends Command
{

    protected $signature = 'crud:generate
                                {name : The name of the Crud.}
                                {--fields= : Field names for the form & migration.}';

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
        $name = ucwords(strtolower($this->argument('name')));
        $modelName = Str::singular($name);
        $migrationName = Str::plural(Str::snake($name));
        $tableName = $migrationName;

        if($this->option('fields') ) {
            $fields = $this->option('fields'); // fields = ['title:string','content:text']

            $fillable_array = explode(',', $fields);
            foreach ($fillable_array as $value) {
                $data[] = preg_replace("/(.*?):(.*)/", "$1", trim($value));
            }
            // dd($data); // ['title','content']

            $comma_separeted_str = implode("', '", $data);
            $fillable = "['";
            $fillable .= $comma_separeted_str;
            $fillable .= "']";

            $this->call('crud:controller', ['name' => $name . 'Controller', '--crud-name' => $name]);
            $this->call('crud:model', ['name' => Str::plural($name), '--fillable' => $fillable]);
            $this->call('crud:migration', ['name' => Str::plural(strtolower($name)), '--schema' => $fields]);
            $this->call('crud:view', ['name' => $name, '--fields' => $fields]);
        }else {
//           $this->call('make:controller', ['name' => $name.'Controller']);
//           $this->call('make:model', ['name' => $name]);
        }
        dd('dont migrate');

        // migrate
       $this->call('migrate');

    }
}
