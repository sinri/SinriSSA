<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/6/21
 * Time: 22:50
 */

require_once __DIR__ . '/../SlowSqlAnalyzer.php';

$sqls = [
    "select a,b,c,5,'33' from `k1`.`b1` where c='3 h 2.234 o e' and r>0.5 and t in ('rr',3441,449136.32,'dg 53 fe') or j< 10",
    "select r5t from k2.r4 where j3ke=\"ue'rds\\\"kgfj\\\"kahdfi'udf\" and ewr='si\"ufh\'jds\'gfd\"hgsd'",
];

foreach ($sqls as $sql) {
    $normalizedSQL = \sinri\SinriSSA\SlowSqlAnalyzer::normalizeSQL($sql);
    echo "ORIGIN: " . PHP_EOL;
    echo $sql . PHP_EOL;
    echo "---------" . PHP_EOL;
    echo "NORMALIZED: " . PHP_EOL;
    echo $normalizedSQL . PHP_EOL;
    echo "=========" . PHP_EOL;
}