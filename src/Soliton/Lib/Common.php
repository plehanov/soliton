<?php
/**
 * User: Valentin Plehanov (Takamura) valentin@plehanov.su
 * Date: 22.02.17
 * Time: 13:40
 */

namespace Soliton\Lib;

/**
 * Class Common
 * @package Soliton
 */
class Common
{
     /**
     * Удостоверяет что все требуемые зависимости присутсвуют в группах
     * @param array $dependencies проверяемые зависимости
     * @param array $groups круги в которых проверяем наличие запросов от которых зависим
     * @return bool
     */
    public function presentNeededDependency(array $dependencies, array $groups)
    {
        $presentCounter = 0;
        if (count($dependencies) > 0) {
            foreach ($groups as $group) {
                foreach ($dependencies as $request) {
                    if (in_array($request, $group)) {
                        $presentCounter++;
                    }
                }
            }
        }
        return count($dependencies) === $presentCounter;
    }

    /**
     * @param array $files
     * @param array $options
     */
    public function prepareFiles(array $files, array &$options)
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
     * @return mixed
     */
    public function convertToStringArray($inputKey, $inputArray, &$resultArray)
    {
        foreach ($inputArray as $key => $value) {
            $tmpKey = (bool)$inputKey ? $inputKey."[$key]" : $key;
            if (is_array($value)) {
                return static::convertToStringArray($tmpKey, $value, $resultArray);
            }
            $resultArray[$tmpKey] = $value;
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
        foreach (array_keys($files['name']) as $index) {
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