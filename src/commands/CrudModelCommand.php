<?php

namespace Oy\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class CrudModelCommand extends GeneratorCommand
{

    protected $signature = 'crud:model
                                {name : The name of the model.}
                                {--table= : The name of the table.}
                                {--fillable= : The names of the fillable columns.}
                                {--relationships= : The relationships for the model}
                                {--pk=id : The name of the primary key.}';

    protected $description = 'Command Crud Model description';
    protected $type = 'Model';

    protected function getStub(){
        return config('crudgenerator.custom_template')
                    ? config('crudgenerator.path') . '/model.stub'
                    : dirname(__DIR__).'/stubs/model.stub';
    }

    protected function getDefaultNamespace($rootNamespace){
        return $rootNamespace;
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub());

        $table = $this->option('table')
                            ? $this->option('table')
                            : $this->argument('name');
        $fillable = $this->option('fillable');
        $relationships = trim($this->option('relationships')) != ''
                                ? explode(',', trim($this->option('relationships')))
                                : [];
//        $primaryKey = $this->option('pk');
//        if(!empty($primaryKey)) {
//            $primaryKey = "protected \$primaryKey = '$primaryKey'";
//        }

        $ret = $this->replaceNamespace($stub, $name)
                    ->replaceTable($stub, $table)
                    ->replaceFillable($stub, $fillable);

        foreach ($relationships as $rel){
            // user # belongsTo # App\User:foreign_key:owner_key
            $parts = explode('#',$rel);
            // parts[0] = user
            // parts[1] = belongsTo
            // parts[2] = App\User:foreign_key:owner_key

            if (count($parts) != 3){
                continue;
            }

            // blindly wrap each arg in single quotes
            $args = explode(':', trim($parts[2]));
            $argsString = '';
            foreach ($args as $k => $v){
                // $v = App\User
                // $v = foreign_key
                // $v = owner_key
                if (trim($v) == '')
                    continue;

                $argsString .= "'" . trim($v) . "', ";
            }
            $argsString = substr($argsString, 0, -2);   // remove last comma
            //  $argsString = 'App\User', 'foreign_key', 'owner_key'
            $ret->createRelationshipFunction($stub, trim($parts[0]), trim($parts[1]), $argsString);
        }

        $ret->replaceRelationshipPlaceholder($stub);

        return $ret->replaceClass($stub, $name);
    }

    protected function replaceTable(&$stub, $table){
        $stub = str_replace('{{table}}', $table, $stub);
        return $this;
    }

    protected function replaceFillable(&$stub, $fillable){
        $stub = str_replace('{{fillable}}', $fillable, $stub);
        return $this;
    }

    protected function createRelationshipFunction(&$stub, $relationshipName, $relationshipType, $argsString){
        $func = "public function " . $relationshipName . "()\n\t{\n\t\t"
            . "return \$this->" . $relationshipType . "(" . $argsString . ");"
            . "\n\t}";

        $str = '{{relationships}}';
        $stub = str_replace($str, $func . "\n\t$str", $stub);

        return $this;
    }

    protected function replaceRelationshipPlaceholder(&$stub){
        $stub = str_replace('{{relationships}}', '', $stub);
        return $this;
    }

//    protected function replacePrimaryKey(&$stub, $primaryKey){
//        $stub = str_replace(
//            '{{primaryKey}}', $primaryKey, $stub
//        );
//
//        return $this;
//    }

}
