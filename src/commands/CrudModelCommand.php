<?php

namespace Oy\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class CrudModelCommand extends GeneratorCommand
{

    protected $signature = 'crud:model
                                {name : The name of the model.}
                                {--table= : The name of the table.}
                                {--fillable= : The names of the fillable columns.}';

    protected $description = 'Command Crud Model description';
    protected $type = 'Model';

    protected function getStub(){
        return dirname(__DIR__).'/stubs/model.stub';
    }

    protected function getDefaultNamespace($rootNamespace){
        return $rootNamespace;
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub());

        $table = $this->option('table')? : strtolower($this->argument('name'));
        $fillable = $this->option('fillable');

        return $this->replaceNamespace($stub, $name)
                    ->replaceTable($stub, $table)
                    ->replaceFillable($stub, $fillable)
                    ->replaceClass($stub, $name);
    }

    protected function replaceTable(&$stub, $table){
        $stub = str_replace(
            '{{table}}', $table, $stub
        );

        return $this;
    }

    protected function replaceFillable(&$stub, $fillable){
        $stub = str_replace(
            '{{fillable}}', $fillable, $stub
        );

        return $this;
    }

    protected function getOptions()
    {
        return [
            ['table', null, InputOption::VALUE_OPTIONAL, 'The table name.', null],
            ['fillable', null, InputOption::VALUE_OPTIONAL, 'The fillable columns.', null],
        ];
    }

}
