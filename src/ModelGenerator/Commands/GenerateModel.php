<?php

namespace Djancok\ModelGenerator\Commands;

use Illuminate\Console\Command;
use File;
use DB;

class GenerateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:model {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Model';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->sm = DB::connection()->getDoctrineSchemaManager();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //list of all table
        $tables = $this->sm->listTables();

        if ($tables) {
            foreach ($tables as $table) {

                //get table name
                $config['table_name'] = $table->getName();

                //get columns name
                $config['columns'] = $table->getColumns();

                //get foreign key
                $config['foreignkey'] = $this->sm->listTableForeignKeys($table->getName());

                $config['path'] = $this->argument('path');

                //filename
                $fileName = $this->formatName($table->getName());

                //create file
                $pathReplace = str_replace("\\", "/", $this->argument('path'));
                File::put('app/' . $pathReplace . "/" . $fileName . '.php', $this->createClass($config));
            }
        }
    }

    public function formatName($table_name)
    {
        $name = str_replace("_", " ", $table_name);
        $upperWord = ucwords($name);
        $upperMerge = str_replace(" ", "", $upperWord);
        return $upperMerge;
    }

    public function createClass($config)
    {
        $path = '\Models\Base';
        if ($config['path']) {
            $path = $config['path'];
        }
        $file = '<?php

namespace App\\' . $path . ';
use Illuminate\Database\Eloquent\Model;
     
class ' . $this->formatName($config['table_name']) . ' extends Model{

    protected $table="' . $config['table_name'] . '";';

        //get indexes
        $indexes = $this->sm->listTableIndexes($config['table_name']);
        if ($indexes) {
            foreach ($indexes as $keyIndex => $index) {

                $columns = collect($index->getColumns());
                $columnName = $columns->first();
                if ($index->isPrimary()) {
                    $file .= "\n" . "\x20\x20\x20\x20" . 'protected $primaryKey="' . $columnName . '";' . "\n";
                }
            }
        }

        //get columns
        if ($config['columns']) {
            $columns = [];
            foreach ($config['columns'] as $key => $column) {
                $columns[] = $column->getName();
            }
        }
        if (!in_array("created_at", $columns)) {
            $file .= "\n" . "\x20\x20\x20\x20" . 'public $timestamps=' . 'false' . ';' . "\n";
        }

        //relationship
        //foreign key
        $foreignKeys = $this->sm->listTableForeignKeys($config['table_name']);
        if ($foreignKeys) {
            foreach ($foreignKeys as $foreignKey) {

                //table name
                $table_reference = $foreignKey->getForeignTableName();

                //local column name
                $localColumnName = collect($foreignKey->getLocalColumns())->first();

                if ($table_reference) {
                    $file .= "\n" . "\x20\x20\x20\x20" . 'public function ' . lcfirst($this->formatName($table_reference)) . '(){' . "\n";
                    $file .= "\x20\x20\x20\x20" . 'return $this->belongsTo(' . $this->formatName($table_reference) . '::class,"' . $localColumnName . '");';
                    $file .= "\n" . "\x20\x20\x20\x20" . "}";
                    $file .= "\n";
                }
            }
        }

        $file .= "\n" . '}';
        return $file;
    }
}
