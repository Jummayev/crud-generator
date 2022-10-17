<?php

namespace Oks\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generator
    {name : Class (singular) for example User} {path : Class (singular) for example User Api}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create CRUD operations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $path = $this->argument('path');

        $this->model($name);
        $this->controller($name, $path);
        $name_lower = Str::plural(strtolower($name));
        $name_upper = Str::plural(strtoupper($name));
        if ($path == '/') {
            $namespace = '';
        } else {
            $namespace = str_replace('/', '\\', $path);
        }
        $routes = "/*--------------------------------------------------------------------------------
            {$name_upper} ROUTES  => START
        --------------------------------------------------------------------------------*/
        Route::prefix('v1')->group(function () {
            Route::middleware('auth:Api')->group(function () {
                Route::prefix('/admin/{$name_lower}')->group(function () {
                    Route::get('/', '{$namespace}\\{$name}Controller@index');
                    Route::post('/', '{$namespace}\\{$name}Controller@create');
                    Route::put('/{id}', '{$namespace}\\{$name}Controller@update');
                    Route::get('/{id}', '{$namespace}\\{$name}Controller@show');
                    Route::delete('/{id}', '{$namespace}\\{$name}Controller@destroy');
                });
            });
            Route::prefix('/{$name_lower}')->group(function () {
                Route::get('/', '{$namespace}\\{$name}Controller@index');
                Route::get('/{id}', '{$namespace}\\{$name}Controller@show');
            });
        });
        /*--------------------------------------------------------------------------------
            {$name_upper} ROUTES  => END
        --------------------------------------------------------------------------------*/";
        File::append(base_path('routes/api.php'), $routes);
    }

    protected function model($name)
    {
        $attributes = Schema::getColumnListing(strtolower($name));
        $fields = '';
        $rules = '';
        $i = 0;
        $count = count($attributes);
        foreach ($attributes as $attribute) {
            $i++;
            if ($i == $count) {
                $fields .= "'{$attribute}'";
            } else {
                $fields .= "'{$attribute}', ";
            }
            $type = Schema::getColumnType(strtolower($name), $attribute);
            $rules .= "'{$attribute}' => '{$type}',\n";
        }

        $modelTemplate = str_replace(
            [
                '{{modelName}}',
                '{{fillable}}',
                '{{table}}',
                '{{rules}}'
            ],
            [
                $name,
                $fields,
                strtolower($name),
                $rules
            ],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

    protected function controller($name, $path)
    {
        $attributes = Schema::getColumnListing(strtolower($name));
        $fields = '';
        $response = '';
        foreach ($attributes as $attribute) {
            $type = Schema::getColumnType(strtolower($name), $attribute);
            $fields .= "* @bodyParam {$attribute} {$type} no-required {$attribute}\n";
            $response .= "*  \"{$attribute}\": \"{$type}\",\n";
        }

        if ($path == '/') {
            $path = "/Http/Controllers/{$name}Controller.php";
            $namespace = '';
        } else {
            $namespace = '\\' . str_replace('/', '\\', $path);
            $path = "/Http/Controllers/{$path}/{$name}Controller.php";
        }

        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
                '{{fields}}',
                '{{namespace}}',
                '{{response}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name),
                $fields,
                $namespace,
                $response
            ],
            $this->getStub('Controller')

        );


        file_put_contents(app_path($path), $controllerTemplate);
    }
}
