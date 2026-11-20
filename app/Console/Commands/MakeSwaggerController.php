<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('make:swagger {name}')]
#[Description('Cria um Controller e seu respectivo arquivo de documentação Swagger')]
class MakeSwaggerController extends Command
{
    public function handle()
    {
        $name = $this->argument('name');
        $controllerName = class_basename($name);
        $docName = $this->docClassName($controllerName);

        $controllerPath = app_path("Http/Controllers/{$name}.php");
        $docPath = app_path('Documentation/'.$this->siblingPath($name, $docName).'.php');

        if (File::exists($controllerPath) || File::exists($docPath)) {
            $this->error('O Controller ou o arquivo de documentação já existem!');

            return Command::FAILURE;
        }

        $this->makeDirectory($controllerPath);
        $this->makeDirectory($docPath);

        File::put($docPath, $this->buildDocumentation($name, $controllerName, $docName));
        File::put($controllerPath, $this->buildController($name, $controllerName));

        $this->info("Documentação gerada em: {$docPath}");
        $this->info("Controller gerado em: {$controllerPath}");

        return Command::SUCCESS;
    }

    protected function makeDirectory($path)
    {
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
    }

    protected function buildController($name, $controllerName)
    {
        $namespace = 'App\Http\Controllers'.$this->getExtraNamespace($name);

        return <<<PHP
<?php

namespace {$namespace};

class {$controllerName} extends Controller
{
    //
}
PHP;
    }

    protected function buildDocumentation($name, $controllerName, $docName)
    {
        $namespace = 'App\Documentation'.$this->getExtraNamespace($name);
        $tagName = $this->tagName($controllerName);

        return <<<PHP
<?php

namespace {$namespace};

use OpenApi\Attributes as OA;

/**
 * Documentação dos endpoints do {$controllerName}.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: '{$tagName}', description: 'Documentação dos endpoints')]
final class {$docName}
{
    //
}
PHP;
    }

    protected function docClassName($controllerName)
    {
        return $this->tagName($controllerName).'Docs';
    }

    protected function tagName($controllerName)
    {
        return str_ends_with($controllerName, 'Controller')
            ? substr($controllerName, 0, -strlen('Controller'))
            : $controllerName;
    }

    protected function getExtraNamespace($name)
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        array_pop($parts);

        return count($parts) > 0 ? '\\'.implode('\\', $parts) : '';
    }

    protected function siblingPath($name, $replacementBasename)
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        array_pop($parts);
        $parts[] = $replacementBasename;

        return implode('/', $parts);
    }
}
