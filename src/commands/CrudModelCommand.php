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
                                {--pk=id : The name of the primary key.}
                                {--soft-deletes=no : Include soft deletes fields.}';

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

        // posts
        $table = $this->option('table')
                            ? $this->option('table')
                            : $this->argument('name');

        $fillable = $this->option('fillable'); // ['user_id','title','body','status']

        // user#belongsTo#App\User
        // comments#hasMany#App\Comment
        $relationships = trim($this->option('relationships')) != ''
                                ? explode(',', trim($this->option('relationships')))
                                : [];

        $softDeletes = $this->option('soft-deletes'); // no

//        $primaryKey = $this->option('pk');
//        if(!empty($primaryKey)) {
//            $primaryKey = "protected \$primaryKey = '$primaryKey'";
//        }

        $ret = $this->replaceNamespace($stub, $name)
                    ->replaceTable($stub, $table)
                    ->replaceFillable($stub, $fillable)
                    ->replaceSoftDelete($stub, $softDeletes);

        foreach ($relationships as $rel){
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

    protected function replaceSoftDelete(&$stub, $replaceSoftDelete){
        if ($replaceSoftDelete == 'yes') {
            $stub = str_replace('{{softDeletes}}', "use SoftDeletes;\n    ", $stub);
            $stub = str_replace('{{useSoftDeletes}}', "use Illuminate\Database\Eloquent\SoftDeletes;\n", $stub);
        } else {
            $stub = str_replace('{{softDeletes}}', '', $stub);
            $stub = str_replace('{{useSoftDeletes}}', '', $stub);
        }

        return $this;
    }

    protected function createRelationshipFunction(&$stub, $relationshipName, $relationshipType, $argsString){
        $tabIndent = '    ';

        $func = "public function " . $relationshipName . "()\n" . $tabIndent . "{\n" . $tabIndent . $tabIndent
            . "return \$this->" . $relationshipType . "(" . $argsString . ");"
            . "\n" . $tabIndent . "}";

        $str = '{{relationships}}';
        $stub = str_replace($str, $func . "\n" . $tabIndent . $str, $stub);
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
