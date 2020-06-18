<?php

namespace App\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CrudViewCommand extends Command
{

    protected $signature = 'crud:view
                                {name : The name of the Crud.}
                                {--fields= : The field names for the form.}';

    protected $description = 'Command Crud View description';

    public function __construct(){
        parent::__construct();
    }

    public function handle(){
        $crudName = strtolower($this->argument('name'));
        $crudNameCap = ucwords($crudName);
        $crudNameSingular = Str::singular($crudName);
        $crudNamePlural = Str::plural($crudName);
//        $viewDirectory = $this->laravel['path'].'/../resources/views/';
        $viewDirectory = resource_path('/views/');
        $path = $viewDirectory.$crudName.'/';
        if(!is_dir($path)) {
            mkdir($path);
        }

        $fields = $this->option('fields');
        $fieldsArray = explode(',', $fields);

        $data = array();
        $x = 0;
        foreach ($fieldsArray as $item) {
            $array = explode(':', $item);
            $data[$x]['name'] = trim($array[0]);
            $data[$x]['type'] = trim($array[1]);
            $x++;
        }

        $formFields = '';
        $formFieldsShow = '';
        foreach ($data as $item) {
            $label = ucwords(strtolower(str_replace('_', ' ', $item['name'])));

            $formFields .=
                "<div class=\"form-group\">
                    <label for='".$item['name']."'>'".$label.":'</label>
                    <input type=\"text\" class=\"form-control\" name='".$item['name']."'>
                </div>";

            $formFieldsShow .= "<p>";
            $formFieldsShow .= "{{ $".$crudNameSingular."->".$item['name']." }}";
            $formFieldsShow .= "</p>";
            $formFieldsShow .= "\n";

        }
        // dd($formFields);
        // dd($formFieldsShow);

        // index
        $indexFile = dirname(__DIR__).'/stubs/index.blade.stub';
        $newIndexFile = $path.'index.blade.php';
        if (!copy($indexFile, $newIndexFile)) {
            echo "failed to copy $indexFile...\n";
        } else {
            file_put_contents($newIndexFile,
                str_replace('%%crudName%%', $crudName, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile,
                str_replace('%%crudNameCap%%', $crudNameCap, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile,
                str_replace('%%crudNameSingular%%', $crudNameSingular, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile,
                str_replace('%%crudNamePlural%%', $crudNamePlural, file_get_contents($newIndexFile)));
        }

        // create
        $createFile = dirname(__DIR__).'/stubs/create.blade.stub';
        $newCreateFile = $path.'create.blade.php';
        if (!copy($createFile, $newCreateFile)) {
            echo "failed to copy $createFile...\n";
        } else {
            file_put_contents($newCreateFile,
                str_replace('%%crudName%%',$crudName,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile,
                str_replace('%%crudNameCap%%',$crudNameCap,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile,
                str_replace('%%crudNameSingular%%',$crudNameSingular,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile,
                str_replace('%%crudNamePlural%%',$crudNamePlural,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile,
                str_replace('%%formFields%%',$formFields,file_get_contents($newCreateFile)));
        }

        // edit
        $editFile = dirname(__DIR__).'/stubs/edit.blade.stub';
        $newEditFile = $path.'edit.blade.php';
        if (!copy($editFile, $newEditFile)) {
            echo "failed to copy $editFile...\n";
        } else {
            file_put_contents($newEditFile,
                str_replace('%%crudName%%',$crudName,file_get_contents($newEditFile)));
            file_put_contents($newEditFile,
                str_replace('%%crudNameCap%%',$crudNameCap,file_get_contents($newEditFile)));
            file_put_contents($newEditFile,
                str_replace('%%crudNameSingular%%',$crudNameSingular,file_get_contents($newEditFile)));
            file_put_contents($newEditFile,
                str_replace('%%crudNamePlural%%',$crudNamePlural,file_get_contents($newEditFile)));
            file_put_contents($newEditFile,
                str_replace('%%formFields%%',$formFields,file_get_contents($newEditFile)));
        }

        // show
        $showFile = dirname(__DIR__).'/stubs/show.blade.stub';
        $newShowFile = $path.'show.blade.php';
        if (!copy($showFile, $newShowFile)) {
            echo "failed to copy $showFile...\n";
        } else {
            file_put_contents($newShowFile,
                str_replace('%%crudName%%',$crudName,file_get_contents($newShowFile)));
            file_put_contents($newShowFile,
                str_replace('%%crudNameCap%%',$crudNameCap,file_get_contents($newShowFile)));
            file_put_contents($newShowFile,
                str_replace('%%crudNameSingular%%',$crudNameSingular,file_get_contents($newShowFile)));
            file_put_contents($newShowFile,
                str_replace('%%crudNamePlural%%',$crudNamePlural,file_get_contents($newShowFile)));
            file_put_contents($newShowFile,
                str_replace('%%formFieldsShow%%',$formFieldsShow,file_get_contents($newShowFile)));
        }

        $this->info('View created successfully.');
    }
}
