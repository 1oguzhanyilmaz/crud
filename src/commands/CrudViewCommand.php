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
                                {--view-path= : The name of the view path.}
                                {--route-group= : Prefix of the route group.}
                                {--pk=id : primary key.}
                                {--validations= : Validation details for the fields.}
                                {--localize=no : yes|no.}';

    protected $description = 'Command Crud View description';
    protected $viewDirectoryPath;

    protected $formFields = [];
    protected $formFieldsHtml = '';
    protected $crudName = '';
    protected $crudNameCap = '';
    protected $crudNameSingular = '';

    protected $primaryKey = 'id';

    protected $modelName = '';
    protected $viewName = '';
    protected $routeGroup = '';
    protected $userViewPath = '';

    protected $formHeadingHtml = '';
    protected $formBodyHtml = '';
    protected $formBodyHtmlForShowView = '';

    // form type
    protected $typeLookup = [
        'string'    => 'text',
        'char'      => 'text',
        'varchar'   => 'text',
        'text'      => 'textarea',
        'json'      => 'textarea',
        'password'  => 'password',
        'email'     => 'email',
        'number'    => 'number',
        'integer'   => 'number',
        'bigint'    => 'number',
        'tinyint'   => 'number',
        'date'      => 'date',
        'datetime'  => 'date',
        'timestamp' => 'date',
        'time'      => 'date',
        'boolean'   => 'radio',
        'enum'      => 'select',
        'select'    => 'select',
        'file'    => 'file',
    ];

    public function __construct(){
        parent::__construct();

        $this->viewDirectoryPath = config('crudgenerator.custom_template')
                ? config('crudgenerator.path')
                : dirname(__DIR__) . '/stubs/';
    }

    public function handle(){
        $this->crudName = strtolower($this->argument('name')); // post
        $this->crudNameCap = ucwords($this->crudName); // Post
        $this->crudNameSingular = Str::singular($this->crudName); // post
        $this->modelName = Str::singular($this->argument('name')); // Post

        // $this->primaryKey = $this->option('pk');

        // admin/
        $this->routeGroup = ($this->option('route-group'))
                                ? $this->option('route-group') . '/'
                                : $this->option('route-group');

        $this->viewName = Str::snake($this->argument('name'), '-'); // post

        $viewDirectory = resource_path() . '/views/';

        if ($this->option('view-path')) {
            $this->userViewPath = $this->option('view-path');
            $path = $viewDirectory . $this->userViewPath . '/' . $this->viewName . '/'; // ...resources/views/admin/post
        } else {
            $path = $viewDirectory . $this->viewName . '/'; // ...resources/views/post
        }

        if(!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        // --fields = title#string;content#text
        $fields = $this->option('fields');
        $fieldsArray = explode(';', $fields);
        // $fieldsArray = ['title#string','content#text']

        $this->formFields = [];
        $validations = $this->option('validations');
        // --validations = title#required,unique:posts,max:255;content#required,max:255

        if ($fields){
            $x = 0;
            foreach ($fieldsArray as $item) {
                // $item => title#string
                // $item => content#text
                $itemArray = explode('#', $item);
                $this->formFields[$x]['name'] = trim($itemArray[0]); // title, content
                $this->formFields[$x]['type'] = trim($itemArray[1]); // string, text
                $this->formFields[$x]['required'] = preg_match('/' . $itemArray[0] . '/', $validations) ? true : false;

                if ($this->formFields[$x]['type'] == 'select' && isset($itemArray[2])) {
                    $options = trim($itemArray[2]);
                    $options = str_replace('options=', '', $options);
                    $optionsArray = explode(',', $options);

                    $commaSeparetedString = implode("', '", $optionsArray);
                    $options = "['" . $commaSeparetedString . "']";

                    $this->formFields[$x]['options'] = $options;
                }

                $x++;
            }
        }
//        $formFields = [
//            [0] => [
//                'name' => 'title',
//                'type' => 'string'
//                'required' => true
//            ],
//            [1] => [
//                'name' => 'content',
//                'type' => 'text'
//                'required' => false
//            ],
//        ]

        // make form fields
        foreach ($this->formFields as $item) {
            $this->formFieldsHtml .= $this->createField($item);
        }

        // for index.blade.stub
        foreach ($this->formFields as $item) {
            $field_name = $item['name'];
            $label = ucwords(str_replace('_', ' ', $field_name));

            if($this->option('localize') == 'yes') {
                $label = 'trans(\'' . $this->crudName . '.' . $field_name . '\')';
            }

            $this->formHeadingHtml .= '<th>' . $label . '</th>';
            $this->formBodyHtml .= '<td>{{ $item->' . $field_name . ' }}</td>';
            $this->formBodyHtmlForShowView .= '<td> {{ $%%crudNameSingular%%->' . $field_name . ' }} </td>';
        }

        // create Template Files : index,create,edit,show
        // index
        $indexFile = $this->viewDirectoryPath . 'index.blade.stub';
        $newIndexFile = $path.'index.blade.php';
        if (!File::copy($indexFile, $newIndexFile)) {
            echo "failed to copy $indexFile...\n";
        } else {
            $this->templateIndex($newIndexFile);
        }

        // form blade
        $formFile = $this->viewDirectoryPath . 'form.blade.stub';
        $newFormFile = $path . 'form.blade.php';
        if (!File::copy($formFile, $newFormFile)) {
            echo "failed to copy $formFile...\n";
        } else {
            $this->templateForm($newFormFile);
        }

        // create
        $createFile = $this->viewDirectoryPath . 'create.blade.stub';
        $newCreateFile = $path.'create.blade.php';
        if (!File::copy($createFile, $newCreateFile)) {
            echo "failed to copy $createFile...\n";
        } else {
            $this->templateCreate($newCreateFile);
        }

        // edit
        $editFile = $this->viewDirectoryPath . 'edit.blade.stub';
        $newEditFile = $path.'edit.blade.php';
        if (!File::copy($editFile, $newEditFile)) {
            echo "failed to copy $editFile...\n";
        } else {
            $this->templateEdit($newEditFile);
        }

        // show
        $showFile = $this->viewDirectoryPath . 'show.blade.stub';
        $newShowFile = $path.'show.blade.php';
        if (!File::copy($showFile, $newShowFile)) {
            echo "failed to copy $showFile...\n";
        } else {
            $this->templateShow($newShowFile);
        }

        $this->info('View created successfully.');
    } // end handle()

    // pa crud:generate --fields=title:string,content:text,joining:date,age:number,status:boolean
    protected function createField($item){
        // $item['name'] = title
        // $item['type'] = string
        // $item['required'] = true
        $form_type = $this->typeLookup[$item['type']];

        switch ($form_type) {
            case 'textarea':
                return $this->createTextareaField($item);
                break;
            case 'radio':
                return $this->createRadioField($item);
                break;
            case 'select':
                return $this->createSelectField($item);
                break;
            case 'enum':
                return $this->createSelectField($item);
                break;
            default:
                 return $this->createFormField($item);
        }
    }

    protected function wrapField($item, $field){
        $label = ucwords(strtolower(str_replace('_', ' ', $item['name'])));
        if($this->option('localize') == 'yes') {
            $label = 'trans(\'' . $this->crudName . '.' . $item['name'] . '\')';
        }

        $formGroupFile = file_get_contents($this->viewDirectoryPath . 'form-fields/wrap-field.blade.stub');
        // <div class="form-group">
        //    <label for="%%itemName%%">%%label%% : </label>
        //    %%field%%
        // </div>;

        $formGroup = str_replace('%%label%%', $label, $formGroupFile);
        $formGroup = str_replace('%%itemName%%', $item['name'], $formGroup);
        $formGroup = str_replace('%%field%%', $field, $formGroup);

        return $formGroup;
    }

    protected function createFormField($item){
        $form_type = $this->typeLookup[$item['type']];
        $required = ($item['required'] === true) ? "required" : "";

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/form-field.blade.stub');
        // <input type="%%fieldType%%" class="form-control" name="%%itemName%%" id="%%itemName%%" %%required%%>
        $markup = str_replace('%%required%%', $required, $markup);
        $markup = str_replace('%%fieldType%%', $form_type, $markup);
        $markup = str_replace('%%itemName%%', $item['name'], $markup);

        return $this->wrapField($item,$markup);
    }

    protected function createTextareaField($item){
        $required = ($item['required'] === true) ? "required" : "";

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/textarea-field.blade.stub');
        $markup = str_replace('%%required%%', $required, $markup);
        $markup = str_replace('%%itemName%%', $item['name'], $markup);

        return $this->wrapField($item, $markup);
    }

    protected function createRadioField($item){
        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/radio-field.blade.stub');
        $markup = str_replace('%%itemName%%', $item['name'], $markup);

        return $markup;
    }

    protected function createSelectField($item){
        $required = ($item['required'] === true) ? ", 'required' => 'required'" : "";

        $options = '';
        foreach ($item['options'] as $option){
            $options .= "<option value='0'>".$option."</option>";
        }

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/select-field.blade.stub');
        $markup = str_replace('%%required%%', $required, $markup);
        $markup = str_replace('%%itemName%%', $item['name'], $markup);
        $markup = str_replace('%%options%%', $options, $markup);

        return $markup;
    }

    // index.blade
    public function templateIndex($newIndexFile){
        file_put_contents($newIndexFile, str_replace('%%formHeadingHtml%%', $this->formHeadingHtml, file_get_contents($newIndexFile)));
        file_put_contents($newIndexFile, str_replace('%%formBodyHtml%%', $this->formBodyHtml, file_get_contents($newIndexFile)));
        file_put_contents($newIndexFile, str_replace('%%crudName%%', $this->crudName, file_get_contents($newIndexFile)));
        file_put_contents($newIndexFile, str_replace('%%crudNameCap%%', $this->crudNameCap, file_get_contents($newIndexFile)));
        file_put_contents($newIndexFile, str_replace('%%modelName%%', $this->modelName, file_get_contents($newIndexFile)));
        file_put_contents($newIndexFile, str_replace('%%viewName%%', $this->viewName, file_get_contents($newIndexFile)));
        file_put_contents($newIndexFile, str_replace('%%routeGroup%%', $this->routeGroup, file_get_contents($newIndexFile)));
    }

    public function templateForm($newFormFile){
        file_put_contents($newFormFile, str_replace('%%formFieldsHtml%%', $this->formFieldsHtml, file_get_contents($newFormFile)));
    }

    // create.blade
    public function templateCreate($newCreateFile) {
        $viewTemplateDir = isset($this->userViewPath)
                                ? $this->userViewPath . '.' . $this->viewName
                                : $this->viewName;
        file_put_contents($newCreateFile, str_replace('%%crudName%%',$this->crudName,file_get_contents($newCreateFile)));
        file_put_contents($newCreateFile, str_replace('%%crudNameCap%%',$this->crudNameCap,file_get_contents($newCreateFile)));
        file_put_contents($newCreateFile, str_replace('%%modelName%%',$this->modelName,file_get_contents($newCreateFile)));
        file_put_contents($newCreateFile, str_replace('%%viewName%%',$this->viewName,file_get_contents($newCreateFile)));
        file_put_contents($newCreateFile, str_replace('%%routeGroup%%',$this->routeGroup,file_get_contents($newCreateFile)));
        file_put_contents($newCreateFile, str_replace('%%viewTemplateDir%%',$viewTemplateDir,file_get_contents($newCreateFile)));
    }

    // edit.blade
    public function templateEdit($newEditFile) {
        $viewTemplateDir = isset($this->userViewPath)
                                    ? $this->userViewPath . '.' . $this->viewName
                                    : $this->viewName;
        file_put_contents($newEditFile, str_replace('%%crudName%%',$this->crudName,file_get_contents($newEditFile)));
        file_put_contents($newEditFile, str_replace('%%crudNameSingular%%',$this->crudNameSingular,file_get_contents($newEditFile)));
        file_put_contents($newEditFile, str_replace('%%crudNameCap%%',$this->crudNameCap,file_get_contents($newEditFile)));
        file_put_contents($newEditFile, str_replace('%%modelName%%',$this->modelName,file_get_contents($newEditFile)));
        file_put_contents($newEditFile, str_replace('%%viewName%%',$this->viewName,file_get_contents($newEditFile)));
        file_put_contents($newEditFile, str_replace('%%routeGroup%%',$this->routeGroup,file_get_contents($newEditFile)));
        file_put_contents($newEditFile, str_replace('%%viewTemplateDir%%',$viewTemplateDir,file_get_contents($newEditFile)));
    }

    // show.blade
    public function templateShow($newShowFile){
        file_put_contents($newShowFile, str_replace('%%formHeadingHtml%%', $this->formHeadingHtml, file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%formBodyHtmlForShowView%%', $this->formBodyHtmlForShowView, file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%crudName%%', $this->crudName, file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%crudNameSingular%%',$this->crudNameSingular,file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%crudNameCap%%',$this->crudNameCap,file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%modelName%%',$this->modelName,file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%viewName%%',$this->viewName,file_get_contents($newShowFile)));
        file_put_contents($newShowFile, str_replace('%%routeGroup%%',$this->routeGroup,file_get_contents($newShowFile)));
    }

}
