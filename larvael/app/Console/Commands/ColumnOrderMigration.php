<?php

namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use Config;
use Illuminate\Console\Command;

class ColumnOrderMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:columnOrder
                            {table : 対象テーブル}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'カラム順序変更マイグレーション作成:引数にテーブル物理名を指定して実行すると、現在のテーブル情報を反映したマイグレーションファイルが作成されます。
                            　データ型などの対応に漏れがあるかもしれないので、作成されたファイル内容をマイグレーション実行前に確認し、必要に応じて適宜修正してください。';

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
        $tableName = $this->argument('table');
        if ($this->confirm($tableName . 'のカラム変更用マイグレーションファイルを作成します')) {
            // DBからカラム情報を取得
            $columns = DB::connection('mysql_jewels')->table('information_schema.columns')->where('TABLE_NAME', $tableName)->get();
            $columnsMigration = '';
            foreach ($columns as $column) {
                if ($column->TABLE_SCHEMA !== 'jewels_test') {
                    // jewels_testを除外しないとカラム情報を二重に取ってきてしまう
                    $res = $this->createColumn($column);
                    $columnsMigration .= $res;
                }
            }
            // テンプレートファイル内容を取得
            $fileInfo = file('app/Console/Commands/Data/ColumnOrderMigrationSample');
            foreach ($fileInfo as $key => $line) {
                if (strpos($line, '${table}')) {
                    $fileInfo[$key] = str_replace('${table}', $tableName, $line);
                }
                if (strpos($line, '${columnInfo}')) {
                    $fileInfo[$key] = $columnsMigration;
                }
            }
            // ファイルを新規に作成し保存
            $path = 'database/migrations/';
            $date = Carbon::now()->format('Y_m_d_His');
            $snakeTableName = ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $tableName)), '_');

            $fileName = $date . '_change_order_' . $snakeTableName . '.php';
            touch($path . $fileName);
            file_put_contents($path . $fileName, $fileInfo);
            $this->info($fileName . 'を作成しました');
        }
    }

    // カラム作成マイグレーション行作成
    private function createColumn($column)
    {
        $columnTypes = [
            // 今後データ型を追加するかも
            'int' => 'integer',
            'varchar' => 'string',
            'text' => 'text',
            'datetime' => 'dateTime',
            'date' => 'date',
            'tinyint' => 'tinyInteger',
            'smallint' => 'smallInteger',
            'boolean' => 'boolean'
        ];

        $column = (array)$column;
        $name = $column['COLUMN_NAME'];
        $type = $columnTypes[$column['DATA_TYPE']];
        if ($column['COLUMN_KEY'] === "PRI") {
            // PKの場合
            $type = 'increments';
        }

        // 最大文字数
        preg_match("/([0-9]+)/", $column['COLUMN_TYPE'], $match);
        if (!empty($match) && ($type === 'varchar' || $type === 'text' || $type === 'string')) {
            $name = "('" . $name . "', " . $match[0] .")";
        } else {
            $name = "('" . $name . "')";
        }

        $nullable = $column['IS_NULLABLE'];
        $default = $column['COLUMN_DEFAULT'];
        $comment = $column['COLUMN_COMMENT'];
        // null許容
        if ($nullable === 'YES') {
            $nullable = '->nullable()';
        } elseif ($nullable === 'NO') {
            $nullable = '';
        }
        // 初期値
        if (!empty($default)) {
            $default = "->default('" . $default . "')";
        }
        // コメント
        if (!empty($comment)) {
            $comment = "->comment('" . $comment . "')";
        }

        return "\t\t\t\t\$table" .
                "->" . $type .
                $name .
                $default .
                $nullable .
                $comment .
                ";\n";
    }
}
