<?php

namespace Framework {

    /**
     * ArrayMethods
     */
    class ArrayMethods {

        private function __construct() {
            # code...
        }

        private function __clone() {
            //do nothing
        }

        /**
         * Useful for converting a multidimensional array into a unidimensional array.
         * 
         * @param type $array
         * @param type $return
         * @return type
         */
        public static function flatten($array, $return = array()) {
            foreach ($array as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $return = self::flatten($value, $return);
                } else {
                    $return[] = $value;
                }
            }
            return $return;
        }

        public static function first($array) {
            if (sizeof($array) == 0) {
                return null;
            }

            $keys = array_keys($array);
            return $array[$keys[0]];
        }

        public static function last($array) {
            if (sizeof($array) == 0) {
                return null;
            }

            $keys = array_keys($array);
            return $array[$keys[sizeof($keys) - 1]];
        }

        public static function toObject($array) {
            $result = new \stdClass();
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $result->{$key} = self::toObject($value);
                } else {
                    $result->{$key} = $value;
                }
            } return $result;
        }

        public static function clean($array) {
            return array_filter($array, function ($item) {
                return !empty($item);
            });
        }

        public static function trim($array) {
            return array_map(function ($item) {
                return trim($item);
            }, $array);
        }

    }

}