<?php
/**
 * Created by PhpStorm.
 * User: tobias
 * Date: 29.05.15
 * Time: 21:24
 */

namespace dmstr\console\controllers;


use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class DockerStackConverterController extends BaseYamlConverterController
{
    /**
     * convert and merge docker-compose.yml with templates
     */
    public function actionConvertDockerCompose()
    {
        $this->stdout("Starting YAML convert process...\n");
        $this->convertYamlTemplates($this->dockerComposeFile, \Yii::getAlias($this->templateDirectory));
        $this->stdout("\n\nDone.\n");
    }

    private function convertYamlTemplates($baseFile, $path)
    {
        $path = realpath($path);
        $replacements = is_file(\Yii::getAlias($this->templateReplacementsFile)) ?
            $this->readFile($this->templateReplacementsFile) :
            [];

        $files = FileHelper::findFiles($path, ['only' => ['/*.tpl.yml']]);
        if (empty($files)) {
            $this->stdout("No templates found in '{$path}'");
            return;
        }

        // import stack data
        $baseStack = $this->readFile($baseFile, $replacements);

        foreach ($files AS $filePath) {
            $file = basename($filePath, '.tpl.yml');
            $this->stdout("\nCreating '{$file}' ");

            // Start
            $stack = $baseStack;
            $template = $this->readFile("{$path}/{$file}.tpl.yml", $replacements);

            // Rule: parse control attributes (.name) for cleanup before merge
            foreach ($template AS $name => $service) {
                if (substr($name, 0, 1) == '.') {
                    #echo $service;exit;
                    if ($service == 'CLEAN') {
                        unset($stack[substr($name,1)]);
                        unset($template[$name]);
                        echo "S";
                        continue;
                    }
                }
                foreach ($service AS $controlAttr => $data) {
                    if (substr($controlAttr, 0, 1) == '.') {
                        $targetAttr = substr($controlAttr, 1);
                        if ($service[$controlAttr] == 'CLEAN') {
                            unset($stack[$name][$targetAttr]);
                            unset($template[$name][$controlAttr]);
                            echo "C";
                        }
                    }
                }
            }

            // Step: merge stack and template
            $stack = ArrayHelper::merge($stack, $template);

            // output file
            $filePrefix = basename($baseFile, '.yml') . '-';
            $filePrefix = str_replace('docker-compose-', '', $filePrefix);
            $outputFile = "{$this->outputDirectory}/{$filePrefix}{$file}.yml";
            $this->writeFile($outputFile, $this->dump($stack));

            // check subdirectories
            $alias = \Yii::getAlias($path) . '/' . $file;
            if (is_dir($alias)) {
                $this->convertYamlTemplates($outputFile, $alias);
            }
        }
    }

    /**
     * helper function
     *
     * @param $stack
     * @param $removeAttributes
     *
     * @return mixed
     */
    private function removeServiceAttributes($stack, $removeAttributes)
    {
        // custom parser for docker-compose files
        foreach ($stack as $serviceName => $serviceAttributes) {
            if (is_array($serviceAttributes)) {
                foreach ($serviceAttributes as $attrName => $attrData) {
                    foreach ($removeAttributes AS $removeAttr => $removeValuePattern) {
                        if ($removeAttr == $attrName) {
                            if (!is_array($attrData)) {
                                if (preg_match($removeValuePattern, $attrData)) {
                                    echo "O";
                                    unset($stack[$serviceName][$attrName]);
                                }
                            } elseif (is_array($stack[$serviceName][$attrName])) {
                                foreach ($attrData AS $i => $value) {
                                    if (preg_match($removeValuePattern, $value)) {
                                        unset($stack[$serviceName][$attrName][$i]);
                                        // unset attribute if this is the last element
                                        if (count($stack[$serviceName][$attrName]) == 0) {
                                            unset($stack[$serviceName][$attrName]);
                                        }
                                        echo "\n{$attrName}[{$value}/{$removeValuePattern}]:";
                                        echo "X";
                                    } else {
                                        #echo ".";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $stack;
    }


    /**
     * helper function
     *
     * @param $stack
     * @param $services
     *
     * @return mixed
     */
    private function removeServices($stack, $patterns)
    {
        foreach ($stack AS $name => $service) {
            foreach ($patterns AS $pattern) {
                if (preg_match($pattern, $name)) {
                    unset($stack[$name]);
                }
            }
        }
        return $stack;
    }

    private function removeAttributes(&$service, $attributes)
    {
        foreach ($attributes AS $attr) {
            unset($service[$attr]);
        }
    }
}