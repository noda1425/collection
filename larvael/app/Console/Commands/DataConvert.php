<?php

namespace App\Console\Commands;

use DB;

use Illuminate\Console\Command;

class DataConvert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:dataConvert
    {tableFrom : 元テーブル}
    {tableTo : 新テーブル}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '名称一致するデータカラムのデータを移行します。複合キーの新テーブルには非対応';

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
        $tableFrom = $this->argument('tableFrom');
        $tableTo = $this->argument('tableTo');

        if ($this->confirm($tableFrom . 'のレコードを' . $tableTo . 'に移し替えます')) {
            $db = DB::connection('mysql_jewels');
            $listFrom = $db->table('information_schema.columns')->where('TABLE_NAME', $tableFrom)->get();
            $listTo = $db->table('information_schema.columns')->where('TABLE_NAME', $tableTo)->get();
            $count = $db->table($tableFrom)->count();
            //1回毎に処理するデータ件数（変更可）
            $onceLimit = 1000;
            $columnsFrom = [];
            $columnsTo = [];
            $selectList = [];
            $pk = '';

            // 元テーブルのカラム名の配列を作成、主キーを特定
            for ($i = 0; $i < count($listTo); $i++) {
                $columnsTo[] = $listTo[$i]->COLUMN_NAME;
                if ($listTo[$i]->COLUMN_KEY === "PRI") {
                    if (!empty($pk)) {
                        info('新テーブルが複合主キーの場合には非対応です');
                        return;
                    }
                    $pk = $listTo[$i]->COLUMN_NAME;
                }
            }

            // 新テーブルのカラム名の配列を作成
            for ($i = 0; $i < count($listFrom); $i++) {
                $columnsFrom[] = $listFrom[$i]->COLUMN_NAME;
            }

            // 新テーブルと元テーブルでカラム名が一致するものを抽出
            foreach ($columnsFrom as $from) {
                if (in_array($from, $columnsTo)) {
                    $selectList[] = $from;
                }
            }

            // 繰り返しで[onceLimit]件ごとにinsert-selectを行う
            for ($off = 0; $off < $count; $off+=$onceLimit) {
                // 経過出力
                echo($off . '/' . $count);
                echo "\n";

                // 元テーブルの値を取得
                $valuesObj = $db->table($tableFrom)->select($selectList)->offset($off)->limit($onceLimit)->get();
                $valuesArray = [];

                foreach ($valuesObj as $value) {
                    // 取得した値を配列に変換
                    $valuesArray[] = json_decode(json_encode($value), true);
                }

                foreach ($valuesArray as $index => $values) {
                    foreach ($values as $key => $value) {
                        if (empty($value) || $value == "") {
                            // 値が空の場合にnullに置換
                            $valuesArray[$index][$key] = null;
                        }
                    }
                    // 主キーを外す
                    unset($valuesArray[$index][$pk]);
                }

                // 新テーブルに挿入
                $db->table($tableTo)->insert($valuesArray);
            }
        }
    }
}
