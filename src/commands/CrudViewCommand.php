<?php

namespace Oy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudViewCommand extends Command
{

    protected $signature = 'crud:view
                                {name : The name of the Crud.}
                                {--fields= : The field names for the form.}
                                {--view-path= : The name of the view path.}';

    protected $description = 'Command Crud View description';

    // form type
    protected $typeLookup = [
        'string'     => 'text',
        'char'       => 'text',
        'varchar'    => 'text',
        'text'       => 'textarea',
        'json'       => 'textarea',
        'password'   => 'password',
        'email'      => 'email',
        'number'     => 'number',
        'integer'    => 'number',
        'bigint'     => 'number',
        'tinyint'    => 'number',
        'date'       => 'date',
        'datetime'   => 'date',
        'time'       => 'date',
        'boolean'    => 'radio',
    ];

    public function handle(){
        $crudName = strtolower($this->argument('name'));
        $crudNameCap = ucwords($crudName);
        $crudNameSingular = Str::singular($crudName);
        $crudNameSingularCap = ucwords($crudNameSingular);
        $crudNamePlural = Str::plural($crudName);
        $crudNamePluralCap = ucwords($crudNamePlural);

        $viewDirectory = resource_path('/views/');

        if ($this->option('view-path')) {
            $userPath = $this->option('view-path');
            $path = $viewDirectory . $userPath . '/' . $crudName . '/';
        } else {
            $path = $viewDirectory . $crudName . '/';
        }

        // $path = $viewDirectory.$crudName.'/';
        if(!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $fields = $this->option('fields');
        $fieldsArray = explode(',', $fields);

        $formFields = array();
        $x = 0;
        foreach ($fieldsArray as $item) {
            $itemArray = explode(':', $item);
            $formFields[$x]['name'] = trim($itemArray[0]);
            $formFields[$x]['type'] = trim($itemArray[1]);
            $formFields[$x]['required'] = (isset($itemArray[2]) && trim($itemArray[2]) == 'required')
                                                ? true
                                                : false;
            $x++;
        }

//        $formFields = [
//            [0] => [
//                'name' => 'title',
//                'type' => 'string'
//            ],
//            [1] => [
//                'name' => 'content',
//                'type' => 'text'
//            ],
//        ]

        // make form fields
        $formFieldsHtml = '';
        foreach ($formFields as $item) {
            $formFieldsHtml .= $this->createField($item);
        }

        $formHeadingHtml = '';
        $formBodyHtml = '';
        $formBodyHtmlForShowView = '';

        foreach ($formFields as $key => $value) {
            $field = $value['name'];
            $label = ucwords(str_replace('_', ' ', $field));
            $formHeadingHtml .= '<th>' . $label . '</th>';

            $formBodyHtml .= '<td>{{ $item->' . $field . ' }}</td>';

            $formBodyHtmlForShowView .= '<td> {{ $%%crudNameSingular%%->' . $field . ' }} </td>';
        }

        // index
        $indexFile = dirname(__DIR__).'/stubs/index.blade.stub';
        $newIndexFile = $path.'index.blade.php';
        if (!File::copy($indexFile, $newIndexFile)) {
            echo "failed to copy $indexFile...\n";
        } else {
            file_put_contents($newIndexFile, str_replace('%%formHeadingHtml%%', $formHeadingHtml, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile, str_replace('%%formBodyHtml%%', $formBodyHtml, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile, str_replace('%%crudName%%', $crudName, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile, str_replace('%%crudNameCap%%', $crudNameCap, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile, str_replace('%%crudNamePlural%%', $crudNamePlural, file_get_contents($newIndexFile)));
            file_put_contents($newIndexFile, str_replace('%%crudNamePluralCap%%', $crudNamePluralCap, file_get_contents($newIndexFile)));
        }

        // create
        $createFile = dirname(__DIR__).'/stubs/create.blade.stub';
        $newCreateFile = $path.'create.blade.php';
        if (!File::copy($createFile, $newCreateFile)) {
            echo "failed to copy $createFile...\n";
        } else {
            file_put_contents($newCreateFile, str_replace('%%crudName%%',$crudName,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile, str_replace('%%crudNamePlural%%',$crudNamePlural,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile, str_replace('%%formFieldsHtml%%',$formFieldsHtml,file_get_contents($newCreateFile)));
            file_put_contents($newCreateFile, str_replace('%%crudNameSingularCap%%', $crudNameSingularCap, file_get_contents($newCreateFile)));
        }

        // edit
        $editFile = dirname(__DIR__).'/stubs/edit.blade.stub';
        $newEditFile = $path.'edit.blade.php';
        if (!File::copy($editFile, $newEditFile)) {
            echo "failed to copy $editFile...\n";
        } else {
            file_put_contents($newEditFile, str_replace('%%crudName%%',$crudName,file_get_contents($newEditFile)));
            file_put_contents($newEditFile, str_replace('%%crudNameSingular%%',$crudNameSingular,file_get_contents($newEditFile)));
            file_put_contents($newEditFile, str_replace('%%crudNameSingularCap%%', $crudNameSingularCap, file_get_contents($newEditFile)));
            file_put_contents($newEditFile, str_replace('%%formFieldsHtml%%',$formFieldsHtml,file_get_contents($newEditFile)));
        }

        // show
        $showFile = dirname(__DIR__).'/stubs/show.blade.stub';
        $newShowFile = $path.'show.blade.php';
        if (!File::copy($showFile, $newShowFile)) {
            echo "failed to copy $showFile...\n";
        } else {
            file_put_contents($newShowFile, str_replace('%%formHeadingHtml%%', $formHeadingHtml, file_get_contents($newShowFile)));
            file_put_contents($newShowFile, str_replace('%%formBodyHtmlForShowView%%', $formBodyHtmlForShowView, file_get_contents($newShowFile)));
            file_put_contents($newShowFile, str_replace('%%crudNameSingular%%',$crudNameSingular,file_get_contents($newShowFile)));
            file_put_contents($newShowFile, str_replace('%%crudNameSingularCap%%', $crudNameSingularCap, file_get_contents($newShowFile)));
        }

        // layouts/master.blade.php file
        $layoutsDirPath = resource_path('/views/layouts/');
        if(!File::isDirectory($layoutsDirPath)) {
            File::makeDirectory($layoutsDirPath);
        }

        $layoutsFile = dirname(__DIR__).'/stubs/master.blade.stub';
        $newLayoutsFile = $layoutsDirPath.'master.blade.php';

        if ( !File::exists($newLayoutsFile) ) {
            if (!File::copy($layoutsFile, $newLayoutsFile)) {
                echo "failed to copy $layoutsFile...\n";
            } else {
                file_get_contents($newLayoutsFile);
            }
        }

        $this->info('View created successfully.');
    }

    // pa crud:generate --fields=title:string,content:text,joining:date,age:number,status:boolean
    protected function createField($item){
        // $item['name'] = title
        // $item['type'] = string
        $form_type = $this->typeLookup[$item['type']];

        switch ($form_type) {
            case 'textarea':
                return $this->createTextareaField($item);
                break;
            case 'radio':
                return $this->createRadioField($item);
                break;
            default:
                 return $this->createFormField($item);
        }
    }

    protected function createFormField($item){
        $form_type = $this->typeLookup[$item['type']];
        $required = ($item['required'] === true) ? ", 'required' => 'required'" : "";

        return $this->wrapField(
            $item,
            "<input type=\"".$form_type."\" class=\"form-control\" name=\"".$item['name']."\" id=\"".$item['name']."\" ".$required.">"
        );
    }

    protected function createTextareaField($item){
        $required = ($item['required'] === true) ? ", 'required' => 'required'" : "";

        return $this->wrapField(
            $item,
            "<textarea class=\"form-control\" name=\"".$item['name']."\" id=\"".$item['name']."\" ".$required."></textarea>"
        );
    }

    protected function createRadioField($item){
        return "<div class=\"\">
                    <div class=\"radio\">
                        <label><input type=\"radio\" name=\"".$item['name']."\" id=\"".$item['name']."\" value=\"1\">Yes</label>
                    </div>
                    <div class=\"radio\">
                        <label><input type=\"radio\" name=\"".$item['name']."\" id=\"".$item['name']."\" value=\"0\" checked>No</label>
                    </div>
                </div>";
    }

    protected function wrapField($item, $field){
        $label = ucwords(strtolower(str_replace('_', ' ', $item['name'])));

        return "<div class=\"form-group\">
                    <label for=\"".$item['name']."\">".$label.":</label>
                    ".$field."
                </div>\n";
    }
}
