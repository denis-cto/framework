<?php

namespace Pina;

class Language
{

    private static $code = null;
    private static $data = [];

    public static function init()
    {
        $config = Config::load('language');
        static::$code = isset($config['default']) ? $config['default'] : false;
    }

    public static function code($code = '')
    {
        if ($code == '') {
            return static::$code;
        }

        $oldCode = static::$code;

        static::$code = $code;

        return $oldCode;
    }

    public static function getCode()
    {
        return static::$code;
    }

    public static function translate($string, $ns = null)
    {
        if (!isset(self::$code)) {
            static::init();
        }

        if (empty(self::$code)) {
            return '';
        }

        $string = trim($string);
        if (empty($string)) {
            return '';
        }

        $module = $ns ? App::modules()->get($ns . "\\Module") : Request::module();
        if (empty($module)) {
            return $string;
        }

        $moduleKey = $module->getNamespace();

        if (!isset(static::$data[static::$code])) {
            static::$data[static::$code] = [];
        }

        if (!isset(static::$data[static::$code][$moduleKey])) {
            $path = $module->getPath();
            $file = $path . "/lang/" . static::$code . '.php';
            static::$data[static::$code][$moduleKey] = file_exists($file) ? include($file) : [];
        }

        if (!isset(static::$data[static::$code][$moduleKey][$string])) {
            $moduleKey = '__fallback__';
            $file = App::path() . '/../lang/' . static::$code . '.php';
            static::$data[static::$code][$moduleKey] = file_exists($file) ? include($file) : [];
        }

        if (!isset(static::$data[static::$code][$moduleKey][$string])) {
            return $string;
        }

        return static::$data[static::$code][$moduleKey][$string];
    }

    public static function val($key)
    {
        return self::translate($string);
    }

}
