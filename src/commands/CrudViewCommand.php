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
                                {--validations= : Validation rules for the fields.}
                                {--view-path= : The name of the view path.}
                                {--route-group= : Prefix of the route group.}
                                {--pk=id : primary key.}
                                {--localize=no : yes|no.}
                                {--foreign-keys : Fields that will not show on the form.}';

    protected $description = 'Command Crud View description';
    protected $viewDirectoryPath;
    protected $formFields = [];
    protected $formFieldsHtml = '';
    protected $crudName = '';
    protected $crudNameCap = '';
    protected $crudNameSingular = '';
    protected $primaryKey = 'id';
    protected $modelName = '';
    protected $modelNameCap = '';
    protected $viewName = '';
    protected $routePrefix = '';
    protected $routePrefixCap = '';
    protected $routeGroup = '';
    protected $userViewPath = '';
    protected $formHeadingHtml = '';
    protected $formBodyHtml = '';
    protected $formBodyHtmlForShowView = '';
    protected $varName = '';
    protected $viewTemplateDir = '';
    protected $delimiter;

    protected $vars = [
        'formFields',
        'formFieldsHtml',
        'varName',
        'crudName',
        'crudNameCap',
        'crudNameSingular',
        'primaryKey',
        'modelName',
        'modelNameCap',
        'viewName',
        'routePrefix',
        'routePrefixCap',
        'routeGroup',
        'formHeadingHtml',
        'formBodyHtml',
        'viewTemplateDir',
        'formBodyHtmlForShowView'
    ];

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
        'unsignedbiginteger' => 'number',
        'bigint'    => 'number',
        'tinyint'   => 'number',
        'date'      => 'date',
        'datetime'  => 'date',
        'timestamp' => 'date',
        'time'      => 'date',
        'radio'     => 'radio',
        'boolean'   => 'radio',
        'enum'      => 'select',
        'select'    => 'select',
        'file'      => 'file',
    ];

    public function __construct(){
        parent::__construct();



        $this->delimiter = config('crudgenerator.custom_delimiter')
                                        ? config('crudgenerator.custom_delimiter')
                                        : ['%%', '%%'];
    }

    public function handle(){
        $this->viewDirectoryPath =  dirname(__DIR__) . '/stubs/views/html/'; // ...src/stubs/views/html
        $this->crudName = strtolower($this->argument('name')); // posts
        $this->varName = lcfirst($this->argument('name')); // posts ucfirst()
        $this->crudNameCap = ucwords($this->crudName); // Posts
        $this->crudNameSingular = Str::singular($this->crudName); // post
        $this->modelName = Str::singular($this->argument('name')); // Post
        $this->modelNameCap = ucfirst($this->modelName); // Post

        // $this->primaryKey = $this->option('pk');

        // admin/
        $this->routeGroup = ($this->option('route-group'))
                                ? $this->option('route-group') . '/'
                                : $this->option('route-group');

        $this->routePrefix = ($this->option('route-group'))
                                ? $this->option('route-group')
                                : '';
        $this->routePrefixCap = ucfirst($this->routePrefix); // Admin

        $this->viewName = Str::snake($this->argument('name'), '-'); // post

        $viewDirectory = resource_path() . '/views/';

        if ($this->option('view-path')) {
            $this->userViewPath = $this->option('view-path');  // admin
            $path = $viewDirectory . $this->userViewPath . '/' . $this->viewName . '/'; // ...resources/views/admin/post
        } else {
            $path = $viewDirectory . $this->viewName . '/'; // ...resources/views/post
        }

        // admin.post || post
        $this->viewTemplateDir = isset($this->userViewPath)
                                        ? $this->userViewPath . '.' . $this->viewName
                                        : $this->viewName;

        if(!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        // --fields = title#string,body#text
        $fields = $this->option('fields');
        $fieldsArray = explode(';', $fields);
        // $fieldsArray = ['title#string','content#text']

        $this->formFields = [];
        $validations = $this->option('validations');
        // --validations = title#required,unique:posts,max:255;body#required,max:255

        $foreignKeys = trim($this->option('foreign-keys')) != ''
                            ? explode(',', $this->option('foreign-keys'))
                            : [];
        // user_id#id#users#cascade
        // category_id#id#categories#cascade
        $fk_field_arr = [];
        foreach ($foreignKeys as $fk){
            $fk_arr = explode('#', $fk);
            array_push($fk_field_arr, $fk_arr[0]);
        }
        // user_id
        // category_id

        if ($fields){
            $x = 0;
            foreach ($fieldsArray as $item) {
                // $item => title#string
                // $item => body#text
                $itemArray = explode('#', $item);

                if (!in_array($itemArray[0], $fk_field_arr)){
                    $this->formFields[$x]['name'] = trim($itemArray[0]); // title, body
                    $this->formFields[$x]['type'] = trim($itemArray[1]); // string, text
                    $this->formFields[$x]['required'] = preg_match('/' . $itemArray[0] . '/', $validations) ? true : false;

                    if (($this->formFields[$x]['type'] === 'select' || $this->formFields[$x]['type'] === 'enum') && isset($itemArray[2])) {
                        $options = trim($itemArray[2]);
                        $options = str_replace('options=', '', $options);
                        $this->formFields[$x]['options'] = $options;
                    }

                    $x++;
                }

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

        $this->templateStubs($path);

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

        $start = $this->delimiter[0];
        $end = $this->delimiter[1];
        $formGroup = str_replace($start . 'label' . $end, $label, $formGroupFile);
        $formGroup = str_replace($start . 'itemName' . $end, $item['name'], $formGroup);
        $formGroup = str_replace($start . 'field' . $end, $field, $formGroup);
        // $formGroup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $formGroup);

        return $formGroup;
    }

    protected function createFormField($item){
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $form_type = $this->typeLookup[$item['type']];
        $required = $item['required'] ? "required" : "";

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/form-field.blade.stub');
        // <input type="%%fieldType%%" class="form-control" name="%%itemName%%" id="%%itemName%%" %%required%%>
        // $markup = str_replace('%%required%%', $required, $markup);
        // $markup = str_replace('%%fieldType%%', $form_type, $markup);
        // $markup = str_replace('%%itemName%%', $item['name'], $markup);

        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'fieldType' . $end, $form_type, $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField($item,$markup);
    }

    protected function createTextareaField($item){
        $required = $item['required'] ? "required" : "";

        $start = $this->delimiter[0]; // %%
        $end = $this->delimiter[1]; // %%

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/textarea-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $this->wrapField($item, $markup);
    }

    protected function createRadioField($item){
        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/radio-field.blade.stub');
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $markup;
    }

    protected function createSelectField($item){
        $required = $item['required'] ? 'required' : '';

        $start = $this->delimiter[0];
        $end = $this->delimiter[1];

        // $item['options'] = str_replace('+', ',', $item['options']);
        // $item['options'] = str_replace(',', '","', $item['options']);
        // $item['options'] = str_replace(':', '":"', $item['options']);
        // $item['options'] = str_replace('{', '{"', $item['options']);
        // $item['options'] = str_replace('}', '"}', $item['options']);

        $markup = file_get_contents($this->viewDirectoryPath . 'form-fields/select-field.blade.stub');
        $markup = str_replace($start . 'required' . $end, $required, $markup);
        $markup = str_replace($start . 'itemName' . $end, $item['name'], $markup);
        $markup = str_replace($start . 'options' . $end, $item['options'], $markup);
        $markup = str_replace($start . 'crudNameSingular' . $end, $this->crudNameSingular, $markup);

        return $markup;
    }

    private function defaultTemplating(){
        return [
            'index' => [
                'formHeadingHtml',
                'formBodyHtml',
                'crudName',
                'crudNameCap',
                'modelName',
                'viewName',
                'routeGroup',
                'primaryKey'
            ],
            'form' => ['formFieldsHtml'],
            'create' => [
                'crudName',
                'crudNameCap',
                'modelName',
                'modelNameCap',
                'viewName',
                'routeGroup',
                'viewTemplateDir',
            ],
            'edit' => [
                'crudName',
                'crudNameSingular',
                'crudNameCap',
                'modelName',
                'modelNameCap',
                'viewName',
                'routeGroup',
                'primaryKey',
                'viewTemplateDir'
            ],
            'show' => [
                'formHeadingHtml',
                'formBodyHtml',
                'formBodyHtmlForShowView',
                'crudName',
                'crudNameSingular',
                'crudNameCap',
                'modelName',
                'viewName',
                'routeGroup',
                'primaryKey'
            ]
        ];
    }

    // create.blade
    protected function templateStubs($path){
        // path => ...resources/views/admin/post
        // $this->viewDirectoryPath => ...src/stubs/views/html
        $dynamicViewTemplate = config('crudgenerator.dynamic_view_template')
                                            ? config('crudgenerator.dynamic_view_template')
                                            : $this->defaultTemplating();

        foreach($dynamicViewTemplate as $name => $vars){
            $file = $this->viewDirectoryPath . $name . '.blade.stub';
            $newFile = $path . $name . '.blade.php';
            if (!File::copy($file, $newFile)) {
                echo "failed to copy $file...\n";
            } else {
                $this->templateVars($newFile, $vars);
            }
        }
    }

    // edit.blade
    protected function templateVars($file, $vars) {
        $start =  $this->delimiter[0];
        $end   =  $this->delimiter[1];

        foreach($vars as $var){
            $replace = $start . $var . $end;
            if(in_array($var, $this->vars)){
                File::put($file, str_replace($replace, $this->$var, File::get($file)));
            }
        }
    }

}
