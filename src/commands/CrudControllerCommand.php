<?php

namespace Oy\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class CrudControllerCommand extends GeneratorCommand
{

    protected $signature = 'crud:controller
                                {name : The name of the controler.}
                                {--crud-name= : The name of the Crud.}
                                {--model-name= : The name of the Crud.}
                                {--model-namespace= : The namespace of the Model.}
                                {--controller-namespace= : The namespace of the Model.}
                                {--view-path= : The name of the view path.}
                                {--fields= : Field names for the form & migration.}
                                {--validations= : Validation rules for the fields.}
                                {--route-group= : Prefix of the route group.}
                                {--pagination=15 : The amount of models per page for index pages.}
                                {--force : Overwrite controller.}';

    protected $description = 'Command Crud Controller description';
    protected $type = 'Controller';

    protected function getStub(){
        return dirname(__DIR__).'/stubs/controller.stub';
	}

    protected function getDefaultNamespace($rootNamespace){
        return $rootNamespace . '\\' . ($this->option('controller-namespace')
                                                ? $this->option('controller-namespace')
                                                : 'Http\Controllers');
    }

    protected function alreadyExists($rawName){
        if($this->option('force')){
            return false;
        }
        return parent::alreadyExists($rawName);
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub()); // get controller stub

        // admin.
        $viewPath = $this->option('view-path')
                                ? $this->option('view-path') . '.'
                                : '';

		$crudName = strtolower($this->option('crud-name')); // posts
        $crudNameSingular = Str::singular($crudName); // post
        $modelName = $this->option('model-name'); // Post
        $modelNamespace = $this->option('model-namespace'); // ""

        // admin/
        $routeGroup = ($this->option('route-group'))
                            ? $this->option('route-group') . '/'
                            : '';

        // admin
        $routePrefix = ($this->option('route-group'))
                            ? $this->option('route-group')
                            : '';
        $routePrefixCap = ucfirst($routePrefix); // Admin

        $perPage = intval($this->option('pagination')); // 10

        $viewName = Str::snake($this->option('crud-name'), '-'); // posts
        $fields = $this->option('fields'); // user_id#bigint,title#string,body#text,status#boolean
        $validations = rtrim($this->option('validations'), ';'); // title#required|unique:posts|max:123;content#required|max:255;status#boolean

        $validationRules = '';
        if (trim($validations) != '') {
            $validationRules = "\$request->validate([";

            $rules = explode(';', $validations);
            foreach ($rules as $v){
                // title#required|unique:posts|max:123
                // content#required|max:255
                // status#boolean
                if (trim($v) == ''){
                    continue;
                }

                // extract field name and args
                $parts = explode('#', $v);
                $fieldName = trim($parts[0]); // title
                $rules = trim($parts[1]); // required|unique:posts|max:123
                $rules = str_replace(',','|',$rules);
                $validationRules .= "\n\t\t\t'$fieldName' => '$rules',";
            }
            $validationRules = substr($validationRules, 0, -1); // remove the last comma
            $validationRules .= "\n\t\t]);";
        }
//        $request->validate([\n
//            'title' => 'required|unique:posts|max:123',
//            'content' => 'required|max:255'
//            'status' => 'required',
//        ]);

        $snippet = "
            if (\$request->hasFile('{{fieldName}}')) {
                \$requestData['{{fieldName}}'] = \$request->file('{{fieldName}}')->store('uploads', 'public');
            }";

        $fieldsArray = explode(';', $fields);
        $fileSnippet = '';
        $whereSnippet = '';
        if ($fields) {
            foreach ($fieldsArray as $index => $item) {
                // $item => user_id#bigint
                // $item => title#string
                // $item => body#text
                // $item => status#boolean
                $itemArray = explode('#', $item);

                if (trim($itemArray[1]) == 'file') {
                    $fileSnippet .= str_replace('{{fieldName}}', trim($itemArray[0]), $snippet) . "\n";
                }

                $fieldName = trim($itemArray[0]);

                $whereSnippet .= ($index == 0)
                                        ? "where('$fieldName', 'LIKE', \"%\$keyword%\")" . "\n"
                                        : "->orWhere('$fieldName', 'LIKE', \"%\$keyword%\")" . "\n";
            }
            $whereSnippet .= "->";
        }

        return $this->replaceNamespace($stub, $name)
                    ->replaceViewPath($stub, $viewPath)
                    ->replaceViewName($stub, $viewName)
                    ->replaceCrudName($stub, $crudName)
                    ->replaceCrudNameSingular($stub, $crudNameSingular)
                    ->replaceModelName($stub, $modelName)
                    ->replaceModelNamespaceSegments($stub, $modelNamespace)
                    ->replaceModelNamespace($stub, $modelNamespace)
                    ->replaceRouteGroup($stub, $routeGroup)
                    ->replaceRoutePrefix($stub, $routePrefix)
                    ->replaceRoutePrefixCap($stub, $routePrefixCap)
                    ->replaceValidationRules($stub, $validationRules)
                    ->replacePaginationNumber($stub, $perPage)
                    ->replaceFileSnippet($stub, $fileSnippet)
                    ->replaceWhereSnippet($stub, $whereSnippet)
                    ->replaceClass($stub, $name);
    }

    protected function replaceViewPath(&$stub, $viewPath){
        $stub = str_replace('{{viewPath}}', $viewPath, $stub);
        return $this;
    }

    protected function replaceViewName(&$stub, $viewName){
        $stub = str_replace('{{viewName}}', $viewName, $stub );
        return $this;
    }

    protected function replaceCrudName(&$stub, $crudName){
        $stub = str_replace('{{crudName}}', $crudName, $stub);
        return $this;
    }

    protected function replaceCrudNameSingular(&$stub, $crudNameSingular){
        $stub = str_replace('{{crudNameSingular}}', $crudNameSingular, $stub);
        return $this;
    }

    protected function replaceModelName(&$stub, $modelName){
        $stub = str_replace('{{modelName}}', $modelName, $stub);
        return $this;
    }

    protected function replaceModelNamespace(&$stub, $modelNamespace){
        $stub = str_replace('{{modelNamespace}}', $modelNamespace, $stub);
        return $this;
    }

    protected function replaceModelNamespaceSegments(&$stub, $modelNamespace){
        $modelSegments = explode('\\', $modelNamespace);
        foreach($modelSegments as $key => $segment){
            $stub = str_replace('{{modelNamespace[' . $key . ']}}', $segment, $stub);
        }

        $stub = preg_replace('{{modelNamespace\[\d*\]}}', '', $stub);

        return $this;
    }

    protected function replaceRouteGroup(&$stub, $routeGroup){
        $stub = str_replace('{{routeGroup}}', $routeGroup, $stub);
        return $this;
    }

    protected function replaceRoutePrefix(&$stub, $routePrefix){
        $stub = str_replace('{{routePrefix}}', $routePrefix, $stub);
        return $this;
    }

    protected function replaceRoutePrefixCap(&$stub, $routePrefixCap){
        $stub = str_replace('{{routePrefixCap}}', $routePrefixCap, $stub);
        return $this;
    }

    protected function replaceValidationRules(&$stub, $validationRules){
        $stub = str_replace('{{validationRules}}', $validationRules, $stub);
        return $this;
    }

    protected function replacePaginationNumber(&$stub, $perPage){
        $stub = str_replace('{{pagination}}', $perPage, $stub);
        return $this;
    }

    protected function replaceFileSnippet(&$stub, $fileSnippet){
        $stub = str_replace('{{fileSnippet}}', $fileSnippet, $stub);
        return $this;
    }

    protected function replaceWhereSnippet(&$stub, $whereSnippet){
        $stub = str_replace('{{whereSnippet}}', $whereSnippet, $stub);
        return $this;
    }
}
