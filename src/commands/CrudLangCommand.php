<?php

namespace Oy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CrudLangCommand extends Command
{

    protected $signature = 'crud:lang
                                {name : The name of the Crud.}
                                {--fields= : The field names for the form.}
                                {--locales= : locale file.}';

    protected $description = 'Command Crud Lang description';
    protected $crudName = '';
    protected $viewDirectoryPath = '';
    protected $locales;
    protected $formFields = [];

    public function __construct(){
        parent::__construct();

        $this->viewDirectoryPath = config('crudgenerator.custom_template')
                ? config('crudgenerator.path')
                : dirname(__DIR__) . '/stubs/';
    }

    public function handle(){
        $this->crudName = $this->argument('name');
        $this->locales = explode(',', $this->option('locales'));

        $fields = $this->option('fields');
        $fieldsArray = explode(';', $fields);

        $this->formFields = array();

        if ($fields){
            $x = 0;
            foreach ($fieldsArray as $item) {
                $itemArray = explode('#', $item);
                $this->formFields[$x]['name'] = trim($itemArray[0]);
                $this->formFields[$x]['type'] = trim($itemArray[1]);
                $this->formFields[$x]['required'] = (isset($itemArray[2]) && trim($itemArray[2]) == 'required')
                    ? true
                    : false;
                $x++;
            }
        }

        // create Template Files : index,create,edit,show
        // index
        foreach($this->locales as $locale) {
            $locale = trim($locale);
            $path = config('view.paths')[0] . '/../lang/' . $locale . '/';

            //create directory for locale
            if (!File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $langFile = $this->viewDirectoryPath . 'lang.stub';
            $newLangFile = $path . $this->crudName . '.php';
            if (!File::copy($langFile, $newLangFile)) {
                echo "failed to copy $langFile...\n";
            } else {
                $this->template($newLangFile);
            }
        }

        $this->info('Lang [' . $locale . '] created successfully.');

    } // end handle()


    // index.blade
    public function template($newLangFile){
        $messages = [];
        foreach($this->formFields as $field) {
            $index = $field['name'];
            $text = ucwords(strtolower(str_replace('_', ' ', $index)));
            $messages[] = "'$index' => '$text'";
        }

        file_put_contents($newLangFile, str_replace('%%messages%%', implode(",\n",$messages), file_get_contents($newLangFile)));
    }
}
