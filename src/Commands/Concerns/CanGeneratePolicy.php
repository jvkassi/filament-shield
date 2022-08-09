<?php

namespace BezhanSalleh\FilamentShield\Commands\Concerns;

use Illuminate\Support\Str;

trait CanGeneratePolicy
{
    protected function generateModelName(string $name): string
    {
        return (string) Str::of($name)->studly()
            ->beforeLast('Resource')
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->studly()
            ->replace('/', '\\');
    }

    protected function generatePolicyPath(array $entity): string
    {
        $path = (new \ReflectionClass($entity['fqcn']::getModel()))->getFileName();

        if (Str::of($path)->contains(['vendor','src'])) {
            $basePolicyPath = app_path(
                (string) Str::of($entity['model'])
                ->prepend('Policies\\')
                ->replace('\\', DIRECTORY_SEPARATOR),
            );

            return "{$basePolicyPath}Policy.php";
        }

        /** @phpstan-ignore-next-line */
        $basePath = Str::of($path)
            ->replace('Models', 'Policies')
            ->replaceLast('.php', 'Policy.php')
            ->replace('\\', DIRECTORY_SEPARATOR);

        return $basePath;
    }

    protected function generatePolicyStubVariables(array $entity): array
    {
        $stubVariables = collect(config('filament-shield.permission_prefixes.resource'))
            ->reduce(function ($gates, $permission) use ($entity) {
                $gates[Str::studly($permission)] = $permission.'_'.$entity['resource'];

                return $gates;
            }, collect())->toArray();

        $stubVariables['auth_model_fqcn'] = config('filament-shield.auth_provider_model.fqcn');
        $stubVariables['auth_model_name'] = Str::of($stubVariables['auth_model_fqcn'])->afterLast('\\');

        $reflectionClass = new \ReflectionClass($entity['fqcn']::getModel());
        $namespace = $reflectionClass->getNamespaceName();
        $path = $reflectionClass->getFileName();

        $stubVariables['namespace'] = Str::of($path)->contains(['vendor','src'])
            ? 'App\Policies'
            : Str::of($namespace)->replace('Models', 'Policies'); /** @phpstan-ignore-line */

        $stubVariables['modelPolicy'] = "{$entity['model']}Policy";

        return $stubVariables;
    }
}
