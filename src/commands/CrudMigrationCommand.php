<?php

namespace Oy\Commands;

use Illuminate\Console\GeneratorCommand;

class CrudMigrationCommand extends GeneratorCommand
{

    protected $signature = 'crud:migration
                                {name : The name of the migration.}
                                {--schema= : The name of the schema.}';

    protected $description = 'Command Crud Migration description';
    protected $type = 'Migration';

    protected function getStub(){
		return dirname(__DIR__).'/stubs/migration.stub';
	}

    protected function getPath($name){
        $name = str_replace($this->laravel->getNamespace(), '', $name);
        $datePrefix = date('Y_m_d_His');
        // dd(database_path());
        return database_path('/migrations/') . $datePrefix . '_create_' . $name . '_table.php';
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub());
        $tableName = strtolower($this->argument('name'));
        $className = 'Create' . ucwords($tableName) . 'Table';

        $schema = $this->option('schema');
        // fields = ['title:string','content:text']
        $fields = explode(',', $schema);

        $data = array();
        $x = 0;
        foreach ($fields as $field) {
            $array = explode(':', $field);
            $data[$x]['name'] = trim($array[0]);
            $data[$x]['type'] = trim($array[1]);
            $x++;
        }

        $schemaFields = '';
        foreach ($data as $item) {
            if( $item['type']=='string' ) {
                $schemaFields .= "\$table->string('".$item['name']."');";
            } elseif( $item['type']=='text' ) {
                $schemaFields .= "\$table->text('".$item['name']."');";
            } elseif( $item['type']=='integer' ) {
                $schemaFields .= "\$table->integer('".$item['name']."');";
            } elseif( $item['type']=='date' ) {
                $schemaFields .= "\$table->date('".$item['name']."');";
            } else {
                $schemaFields .= "\$table->string('".$item['name']."');";
            }
        }

        $schemaUp = "
        Schema::create('".$tableName."', function(Blueprint \$table)
        {
            \$table->id();
            ".$schemaFields."
            \$table->timestamps();
        });
        ";

        $schemaDown = "Schema::drop('".$tableName."');";

        return $this->replaceSchemaUp($stub, $schemaUp)
                    ->replaceSchemaDown($stub, $schemaDown)
                    ->replaceClass($stub, $className);
    }

    protected function replaceSchemaUp(&$stub, $schemaUp){
        $stub = str_replace(
            '{{schema_up}}', $schemaUp, $stub
        );

        return $this;
    }

    protected function replaceSchemaDown(&$stub, $schemaDown){
        $stub = str_replace(
            '{{schema_down}}', $schemaDown, $stub
        );

        return $this;
    }
}
