<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 22.02.17
 * Time: 13:40
 */

namespace Soliton;

/**
 * Class Common
 * @package Soliton
 */
class Common
{


    /**
     * @param array $files
     * @param array $options
     */
    public static function prepareFiles(array $files, array &$options)
    {
        if (count($files)) {
            foreach ($files as $key => $dataOfFile) {
                if (is_array($dataOfFile['name'])) {
                    $options = static::setRequestFiles($options, $key, $dataOfFile);
                } else {
                    $options = static::setRequestFile($options, $key, $dataOfFile);
                }
            }
        }
    }


    /**
     * @param string $inputKey
     * @param array $inputArray
     * @param array $resultArray
     */
    public static function convertToStringArray($inputKey, $inputArray, &$resultArray)
    {
        foreach ($inputArray as $key => $value) {
            $tmpKey = (bool)$inputKey ? $inputKey."[$key]" : $key;
            if (is_array($value)) {
                static::convertToStringArray($tmpKey, $value, $resultArray);
            } else {
                $resultArray[$tmpKey] = $value;
            }
        }
    }

// Helpers functions----------------------------------------------------------------------------------------------------

    /**
     * Добавляет в массив упакованную строку с данными о файлах для курла.
     * На вход получает массив со сведениями о файле формата $_FILES.
     *
     * @param array $options
     * @param string $key
     * @param array $files
     * @return array
     */
    protected static function setRequestFiles(array $options, $key, array $files)
    {
        foreach ($files['name'] as $index => $tmp) {
            if ($files['error'][$index] === 0) {
                $options[CURLOPT_POSTFIELDS]["{$key}[{$index}]"]
                    = new \CURLFile($files['tmp_name'][$index], $files['type'][$index], $files['name'][$index]);
            }
        }
        return $options;
    }
    /**
     * Добавляет в массив упакованную строку с данными о файле для курла.
     * На вход получает массив со сведениями о файле формата $_FILES.
     *
     * @param array $options
     * @param string $key
     * @param array $file
     * @return array
     */
    protected static function setRequestFile(array $options, $key, array $file)
    {
        if ($file['error'] === 0) {
            $options[CURLOPT_POSTFIELDS][$key] = new \CURLFile($file['tmp_name'], $file['type'], $file['name']);
        }
        return $options;
    }
}