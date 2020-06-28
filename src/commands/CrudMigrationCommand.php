<?php

namespace Oy\Commands;

use Illuminate\Console\GeneratorCommand;

class CrudMigrationCommand extends GeneratorCommand
{

    protected $signature = 'crud:migration
                                {name : The name of the migration.}
                                {--schema= : The name of the schema.}
                                {--indexes= : The fields to add an index too.}
                                {--foreign-keys= : Foreign keys.}
                                {--pk=id : The name of the primary key.}';

    protected $description = 'Command Crud Migration description';
    protected $type = 'Migration';

    protected $typeLookup = [
        'char' => 'char',
        'date' => 'date',
        'datetime' => 'dateTime',
        'time' => 'time',
        'timestamp' => 'timestamp',
        'text' => 'text',
        'mediumtext' => 'mediumText',
        'longtext' => 'longText',
        'json' => 'json',
        'jsonb' => 'jsonb',
        'binary' => 'binary',
        'number' => 'integer',
        'integer' => 'integer',
        'bigint' => 'bigInteger',
        'mediumint' => 'mediumInteger',
        'tinyint' => 'tinyInteger',
        'smallint' => 'smallInteger',
        'boolean' => 'boolean',
        'decimal' => 'decimal',
        'double' => 'double',
        'float' => 'float',
        'enum' => 'enum',
    ];

    protected function getStub(){
        return config('crudgenerator.custom_template')
                    ? config('crudgenerator.path') . '/migration.stub'
                    : dirname(__DIR__).'/stubs/migration.stub';
	}

    protected function getPath($name){
        $name = str_replace($this->laravel->getNamespace(), '', $name);
        $datePrefix = date('Y_m_d_His');
        // dd(database_path());
        return database_path('/migrations/') . $datePrefix . '_create_' . $name . '_table.php';
    }

    protected function buildClass($name){
        $stub = $this->files->get($this->getStub());

        $tableName = $this->argument('name');
        // First, replace it to space.
        $classN = ucwords(str_replace('_', ' ', $tableName));
        $className = 'Create' . str_replace(' ', '', $classN) . 'Table';
        $fieldsToIndex = trim($this->option('indexes')) != ''
                                ? explode(',', $this->option('indexes'))
                                : [];
        $schema = rtrim($this->option('schema'), ';');
        $fields = explode(';', $schema);
        // fields = ['title#string#default','content#text']

        $data = array();

        if ($schema){
            $x = 0;
            foreach ($fields as $field) {
                $array = explode('#', $field);
                $data[$x]['name'] = trim($array[0]); // title
                $data[$x]['type'] = trim($array[1]); // string

                $data[$x]['modifier'] = '';

                $modifierLookup = [
                    'comment',
                    'default',
                    'first',
                    'nullable',
                    'unsigned',
                ];

                if (isset($array[2]) && in_array(trim($array[2]), $modifierLookup)) {
                    $data[$x]['modifier'] = "->" . trim($array[2]) . "()";
                }

                $x++;
            }
        }

        // $data[0] = ['name'=>'title',   'type'=>'string', 'modifier'=>'->default()']
        // $data[1] = ['name'=>'content', 'type'=>'text',   'modifier'=>'']
        $schemaFields = '';
        $tabIndent = '    ';
        foreach ($data as $item) {
            if (isset($this->typeLookup[$item['type']])) {
                $type = $this->typeLookup[$item['type']];

                $schemaFields .= "\$table->" . $type . "('" . $item['name'] . "')";
            } else {
                $schemaFields .= "\$table->string('" . $item['name'] . "')";
            }

            $schemaFields .= $item['modifier'];
            $schemaFields .= ";\n" . $tabIndent . $tabIndent . $tabIndent;
        }
        // $schemaFields
        // $table->string('title')->default();
        // $table->text('content');

        foreach ($fieldsToIndex as $fldData){
            $line = trim($fldData);
            if (strpos($line, '#') === false){
                $line .= '#';
            }

            $parts = explode('#', $line);
            if (strpos($parts[0],'|') !== 0){
                $fieldNames = "['" . implode("', '", explode('|', $parts[0])) . "']";
            }else{
                $fieldNames = trim($parts[0]);
            }

            if (count($parts) > 1 && $parts[1] == 'unique') {
                $schemaFields .= "\$table->unique('" . trim($fieldNames) . "')";
            } else {
                $schemaFields .= "\$table->index('" . trim($fieldNames) . "')";
            }

            $schemaFields .= ";\n" . $tabIndent . $tabIndent . $tabIndent;
        }

        $foreignKeys = trim($this->option('foreign-keys')) != ''
                                ? explode(',', $this->option('foreign-keys'))
                                : [];
        // --foreign keys
            // 0 => comment_id
            // 1 => id
            // 2 => comments
            // 3 => cascade
        foreach ($foreignKeys as $fk){
            $line = trim($fk);
            $parts = explode('#', $line);
            if (count($parts) == 3) {
                $schemaFields .= "\$table->foreign('" . trim($parts[0]) . "')"
                    . "->references('" . trim($parts[1]) . "')->on('" . trim($parts[2]) . "')";
            }elseif(count($parts) == 4){
                $schemaFields .= "\$table->foreign('" . trim($parts[0]) . "')"
                    . "->references('" . trim($parts[1]) . "')->on('" . trim($parts[2]) . "')"
                    . "->onDelete('" . trim($parts[3]) . "')";
            }else{
                continue;
            }

            $schemaFields .= ";\n" . $tabIndent . $tabIndent . $tabIndent;
        }
        // $primaryKey = ($this->option('pk'));

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
        $stub = str_replace('{{schema_up}}', $schemaUp, $stub);
        return $this;
    }

    protected function replaceSchemaDown(&$stub, $schemaDown){
        $stub = str_replace('{{schema_down}}', $schemaDown, $stub);
        return $this;
    }
}
