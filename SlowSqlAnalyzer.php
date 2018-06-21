<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/6/21
 * Time: 22:48
 */

namespace sinri\SinriSSA;


class SlowSqlAnalyzer
{

    /**
     * Make a SQL string normalized, i.e. turn all variables into signals (# for string, @ for number, and ~ for list).
     * @param string $sql
     * @return string
     */
    public static function normalizeSQL($sql)
    {
        $sql = " {$sql} ";
        $sql = preg_replace('/(?<=[\s\=\(\<\>,])(\d+(\.\d+)?)(?=[\s,;\)\<\>\!])/', '@', $sql);
        $sql = self::replaceStringExpression($sql);
        $sql = preg_replace('/"(([^"]|(\\.))*)"/', '#', $sql);
        $sql = preg_replace('/\([\s,#@]+\)/', '(~)', $sql);

        $sql = trim($sql);

        return $sql;
    }

    /**
     * Use Status Machine to filter Quotations to signal(#)
     * @param string $sql
     * @return string
     */
    protected static function replaceStringExpression($sql)
    {
        $ptr = 0;

        $quoteType = '';
        $quoteStartIndex = -1;

        /**
         * status
         * 0: before quote and after quote
         * 1: met quote, then in quote
         * 2: met escape, then in escape
         */
        $status = 0;

        while (true) {
            $c = $sql[$ptr];
            switch ($status) {
                case 1:
                    if ($c === $quoteType) {
                        $quoteEndedIndex = $ptr + 1;
                        // replace
                        $t = substr($sql, 0, $quoteStartIndex) . "#";
                        if ($quoteEndedIndex < strlen($sql)) {
                            $t .= substr($sql, $quoteEndedIndex);
                        }
                        $sql = $t;

                        $ptr = $quoteStartIndex;
                        $quoteStartIndex = -1;
                        $quoteType = '';
                        $status = 0;
                    } elseif ($c === '\\') {
                        $status = 2;
                    }
                    break;
                case 2:
                    $status = 1;
                    break;
                case 0:
                default:
                    if ($c === '"' || $c === "'") {
                        $status = 1;
                        $quoteType = $c;
                        $quoteStartIndex = $ptr;
                    }
                    break;
            }
            $ptr++;
            if (strlen($sql) <= $ptr) break;

            //echo "NOW PTR={$ptr} sql={$sql}".PHP_EOL;
        }

        return $sql;
    }
}