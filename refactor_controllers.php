<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Str;

$dir = new RecursiveDirectoryIterator(__DIR__ . '/app/Http/Controllers');
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;

        // Replace DB::table('xxx') with \App\Models\Xxx::query()
        $content = preg_replace_callback('/DB::table\(\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\)(->)?/', function ($matches) {
            $tableName = $matches[1];
            $arrow = isset($matches[2]) ? $matches[2] : '';
            $modelName = Str::studly($tableName);
            
            // If there's an arrow right after, it means we are chaining
            if ($arrow === '->') {
                return "\\App\\Models\\{$modelName}::";
            }
            return "\\App\\Models\\{$modelName}::query()";
        }, $content);

        // Also add the model imports? Since we used absolute \App\Models\Xxx we don't strictly need use statements, 
        // but it's cleaner to use the absolute path for this quick refactor or add the imports.
        // The regex above uses absolute paths like \App\Models\Persona::where(...) which is perfectly valid in PHP.

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Refactored: " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.\n";
