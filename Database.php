<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (!empty($args)) {
            foreach ($args as $value) {
                $rep_pos = strpos($query, "?");

                if ($value === '!!skip!!') {
                    $query = preg_replace('/\{.*?\}/', '', $query, 1);
                } else {
                    switch($query[$rep_pos + 1]) {
                        case "d":
                            $pattern = '/\?d/';
                            $replacement = $value === null ? 'NULL' : (int)$value;
                            break;
                        case "f":
                            $pattern = '/\?f/';
                            $replacement = $value === null ? 'NULL' : (float)$value;
                            break;
                        case "a":
                            $pattern = '/\?a/';
                            $replacement_arr = [];

                            if (!is_array($value)) {
                                throw new Exception('Wrong data type. Must be array.');
                            }

                            foreach ($value as $key => $item) {
                                if (in_array(gettype($item), ['string', 'integer', 'double', 'boolean', 'NULL'])) {
                                    if (gettype($key) === 'string') {
                                        $val = gettype($item) === 'string' ? "'" . addslashes($item) . "'" : $item;
                                        $val = gettype($val) === 'boolean' ? (int)$val : $val;
                                        $val = $val === null ? 'NULL' : $val;
                                        $replacement_arr[] = '`' . addslashes($key) . '` = ' .  $val;
                                    } else {
                                        $replacement_arr[] = gettype($item) === 'string' ? "'" . addslashes($item) . "'" : $item;
                                    }
                                } else {
                                    throw new Exception('Wrong data type. Must be string, int, float, bool or null.');
                                }
                            }
                            $replacement = implode(', ', $replacement_arr);
                            break;
                        case "#":
                            $pattern = '/\?\#/';
                            if (is_array($value)) {
                                $data = array_map(fn($n) => '`' . addslashes($n) . '`', $value);
                                $replacement = implode(', ', $data);
                            } else {
                                $replacement = '`' . addslashes($value) . '`';
                            }
                            break;
                        default:
                            if (in_array(gettype($value), ['string', 'integer', 'double', 'boolean', 'NULL'])) {
                                $pattern = '/\?/';
                                $replacement = gettype($value) === 'string' ? "'" . addslashes($value) . "'" : $value;
                                $replacement = gettype($replacement) === 'boolean' ? (int)$replacement : $replacement;
                                $replacement = $replacement === null ? 'NULL' : $replacement;
                            } else {
                                throw new Exception('Wrong data type. Must be string, int, float, bool or null.');
                            }
                            break;
                    }
                    $query = preg_replace($pattern, $replacement, $query, 1);
                }                
            }

            $query = preg_replace('/([\{\}])/', '', $query);
        }

        return $query;
    }

    public function skip()
    {
        return '!!skip!!';
    }
}
