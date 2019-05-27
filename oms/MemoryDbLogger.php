<?php
namespace OMS;

class MemoryDbLogger
{
    private static $queriesLog = [];
    private static $parsedQueries = [];

    static function logQuery($args, $time)
    {
        self::$queriesLog[] = ['q' => $args, 't' => $time];
    }

    static function getQueries()
    {
        if (self::$parsedQueries === []) {
            self::parseQueries();
        }

        return self::$parsedQueries;
    }

    private static function parseQueries()
    {
        foreach (self::$queriesLog as $queryLog) {
            $q = $queryLog['q'][0];
            $p = $queryLog['q'][1];
            $t = $queryLog['t'];

            $qh = md5($q);

            if (!array_key_exists($qh, self::$parsedQueries)) {
                self::$parsedQueries[$qh] = [
                    'id'=> $qh,
                    'q' => $q,
                    'p' => [$p],
                    't' => [$t]
                ];
            } else {
                self::$parsedQueries[$qh]['p'][] = $p;
                self::$parsedQueries[$qh]['t'][] = $t;
            }
        }

        self::$parsedQueries = array_values(self::$parsedQueries);
    }
}
