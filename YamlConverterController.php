<?php
/**
 * YAML converter
 */

namespace dmstr\console\controllers;

use Symfony\Component\Yaml\Yaml;
use yii\console\Controller;

class YamlConverterController extends Controller
{
    /**
     * @var string development docker-compose file
     */
    public $dockerComposeFile = '@app/docker-compose.yml';
    /**
     * @var string yaml template directory
     */
    public $templateDirectory = '@app/build/stacks-tpl';
    /**
     * @var string php file containing replacement values
     */
    public $templateReplacementsFile = '@app/build/stacks-tpl/replacements.php';
    /**
     * @var string yaml output directory
     */
    public $outputDirectory = '@app/build/stacks-gen';

    /**
     * @inheritdoc
     */
    public function options($actionId)
    {
        return array_merge(
            parent::options($actionId),
            ['dockerComposeFile', 'templateDirectory', 'outputDirectory', 'templateReplacementsFile']
        );
    }


    /**
     * @param $file YAML file to read and parse
     * @param $replacements
     *
     * @return array data from the YAML file
     */
    public function readFile($file, $replacements = [])
    {
        $file = file_get_contents(\Yii::getAlias($file));
        $file = $this->parseReplacements($file, $replacements);
        return Yaml::parse($file);
    }

    /**
     * @param $file
     * @param $data
     */
    public function writeFile($file, $data)
    {
        file_put_contents(\Yii::getAlias($file), $data);
    }

    public function dump($stack)
    {
        $this->ksortRecursive($stack);
        return Yaml::dump($stack, 10);
    }


    private function parseReplacements($string, $replacements)
    {
        foreach ($replacements AS $token => $value) {
            $string = str_replace('%' . $token . '%', $value, $string);
        }
        return $string;
    }


    private function ksortRecursive(&$array, $sort_flags = SORT_REGULAR)
    {
        if (!is_array($array)) {
            return false;
        }
        ksort($array, $sort_flags);
        foreach ($array as &$arr) {
            $this->ksortRecursive($arr, $sort_flags);
        }
        return true;
    }
}