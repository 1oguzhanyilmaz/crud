<?php

namespace Oy\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class CrudControllerCommand extends GeneratorCommand
{

    protected $signature = 'crud:controller
                                {name : The name of the controler.}
                                {--crud-name= : The name of the Crud.}
                                {--view-path= : The name of the view path.}';

    protected $description = 'Command Crud Controller description';
    protected $type = 'Controller';

    protected function getStub(){
		return dirname(__DIR__).'/stubs/controller.stub';
	}

    protected function getDefaultNamespace($rootNamespace){
		return $rootNamespace.'\Http\Controllers';
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub());

        $viewPath = $this->option('view-path') ? strtolower($this->option('view-path')) . '.' : '';

		$crudName = strtolower($this->option('crud-name'));
        $crudNameCap = $this->option('crud-name');
		$crudNamePlural = Str::plural($crudName);
		$crudNamePluralCap = Str::plural($crudNameCap);
        $crudNameSingular = Str::singular($crudName);

        return $this->replaceNamespace($stub, $name)
                    ->replaceViewPath($stub, $viewPath)
                    ->replaceCrudName($stub, $crudName)
                    ->replaceCrudNameCap($stub, $crudNameCap)
                    ->replaceCrudNamePlural($stub, $crudNamePlural)
                    ->replaceCrudNamePluralCap($stub, $crudNamePluralCap)
                    ->replaceCrudNameSingular($stub, $crudNameSingular)
                    ->replaceClass($stub, $name);
    }

    protected function replaceViewPath(&$stub, $viewPath){
        $stub = str_replace(
            '{{viewPath}}', $viewPath, $stub
        );

        return $this;
    }

    protected function replaceCrudName(&$stub, $crudName){
        $stub = str_replace(
            '{{crudName}}', $crudName, $stub
        );

        return $this;
    }

    protected function replaceCrudNameCap(&$stub, $crudNameCap){
        $stub = str_replace(
            '{{crudNameCap}}', $crudNameCap, $stub
        );

        return $this;
    }

    protected function replaceCrudNamePlural(&$stub, $crudNamePlural){
        $stub = str_replace(
            '{{crudNamePlural}}', $crudNamePlural, $stub
        );

        return $this;
    }

    protected function replaceCrudNamePluralCap(&$stub, $crudNamePluralCap){
        $stub = str_replace(
            '{{crudNamePluralCap}}', $crudNamePluralCap, $stub
        );

        return $this;
    }

    protected function replaceCrudNameSingular(&$stub, $crudNameSingular){
        $stub = str_replace(
            '{{crudNameSingular}}', $crudNameSingular, $stub
        );

        return $this;
    }
}
