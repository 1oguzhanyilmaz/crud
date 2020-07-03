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
                                {--fields= : Fields name for the form & migration.}
                                {--validations= : Validation details for the fields.}
                                {--route-group= : Prefix of the route group.}
                                {--pagination=15 : The amount of models per page for index pages.}
                                {--force : Overwrite controller.}';

    protected $description = 'Command Crud Controller description';
    protected $type = 'Controller';

    protected function getStub(){
        return config('crudgenerator.custom_template')
                    ? config('crudgenerator.path') . '/controller.stub'
                    : dirname(__DIR__).'/stubs/controller.stub';
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
        return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub()); // get controller stub

        $viewPath = $this->option('view-path')
                                ? $this->option('view-path') . '.'
                                : '';

		$crudName = strtolower($this->option('crud-name'));
        $crudNameSingular = Str::singular($crudName);
        $modelName = $this->option('model-name');
        $modelNamespace = $this->option('model-namespace');

        $routeGroup = ($this->option('route-group'))
                            ? $this->option('route-group') . '/'
                            : '';

        $perPage = intval($this->option('pagination'));

        $viewName = Str::snake($this->option('crud-name'), '-');
        $fields = $this->option('fields');
        $validations = rtrim($this->option('validations'), ';');
        // --validations = title#required|unique:posts|max:123; content#required|max:255

        $validationRules = '';
        if (trim($validations) != '') {
            $validationRules = "\$request->validate([";

            $rules = explode(';', $validations);
            foreach ($rules as $v){
                if (trim($v) == ''){
                    continue;
                }

                // extract field name and args
                $parts = explode('#', $v);
                $fieldName = trim($parts[0]);
                $rules = trim($parts[1]);
                $rules = str_replace(',','|',$rules);
                $validationRules .= "\n\t\t\t'$fieldName' => '$rules',";
            }
            $validationRules = substr($validationRules, 0, -1); // remove the last comma
            $validationRules .= "\n\t\t]);";
        }
//        $this->validate($request, [\n
//            'title' => 'required|unique:posts|max:123',
//            'content' => 'required|max:255'
//        ]);

        $snippet = "
        if (\$request->hasFile('{{fieldName}}')) {
            \$uploadPath = public_path('/uploads/');

            \$extension = \$request->file('{{fieldName}}')->getClientOriginalExtension();
            \$fileName = rand(11111, 99999) . '.' . \$extension;

            \$request->file('{{fieldName}}')->move(\$uploadPath, \$fileName);
            \$requestData['{{fieldName}}'] = \$fileName;
        }";

        $fieldsArray = explode(';', $fields);
        $fileSnippet = '';
        $whereSnippet = '';

        if ($fields) {
            foreach ($fieldsArray as $index => $item) {
                // $item => title#string
                // $item => content#text
                $itemArray = explode('#', $item);

                if (trim($itemArray[1]) == 'file') {
                    $fileSnippet .= "\n\n" . str_replace('{{fieldName}}', trim($itemArray[0]), $snippet) . "\n";
                }

                $fieldName = trim($itemArray[0]);

                $whereSnippet .= ($index == 0)
                                    ? "where('$fieldName', 'LIKE', \"%\$keyword%\")" . "\n\t\t\t\t"
                                    : "->orWhere('$fieldName', 'LIKE', \"%\$keyword%\")" . "\n\t\t\t\t";
            }
        }

        return $this->replaceNamespace($stub, $name)
                    ->replaceViewPath($stub, $viewPath)
                    ->replaceViewName($stub, $viewName)
                    ->replaceCrudName($stub, $crudName)
                    ->replaceCrudNameSingular($stub, $crudNameSingular)
                    ->replaceModelName($stub, $modelName)
                    ->replaceModelNamespace($stub, $modelNamespace)
                    ->replaceRouteGroup($stub, $routeGroup)
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

    protected function replaceRouteGroup(&$stub, $routeGroup){
        $stub = str_replace('{{routeGroup}}', $routeGroup, $stub);
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
