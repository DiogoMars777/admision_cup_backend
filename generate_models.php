<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

$tables = Schema::getTableListing();

$appPath = __DIR__ . '/app/Models';
if (!is_dir($appPath)) {
    mkdir($appPath, 0755, true);
}

foreach ($tables as $table) {
    if (strpos($table, '.') !== false) {
        $table = explode('.', $table)[1];
    }
    if (in_array($table, ['migrations', 'personal_access_tokens', 'password_reset_tokens', 'failed_jobs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) continue;

    $className = Str::studly($table); // Use table name as is, no singularization since they are already singular in Spanish
    $columns = Schema::getColumnListing($table);
    
    $fillable = [];
    foreach ($columns as $column) {
        if (!in_array($column, ['id', 'created_at', 'updated_at'])) {
            $fillable[] = "'$column'";
        }
    }
    $fillableStr = implode(", ", $fillable);

    $content = "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Factories\HasFactory;\nuse Illuminate\Database\Eloquent\Model;\n\nclass {$className} extends Model\n{\n    use HasFactory;\n\n    protected \$table = '{$table}';\n\n    protected \$fillable = [\n        {$fillableStr}\n    ];\n}\n";

    file_put_contents($appPath . '/' . $className . '.php', $content);
    echo "Created Model: {$className}.php\n";
}
echo "Models generated.\n";
