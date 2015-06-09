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
        #$replacements = require();
        $replacements = is_file(\Yii::getAlias($this->templateReplacementsFile))?$this->readFile($this->templateReplacementsFile):[];
        #var_dump($replacements);exit;
        $files = FileHelper::findFiles($path, ['only' => ['/*.tpl.yml']]);
        if (empty($files)) {
            $this->stdout("No templates found in {$path}.");
            return;
        }

        $dev   = $this->readFile($baseFile, $replacements);

        foreach ($files AS $filePath) {
            $file = basename($filePath, '.tpl.yml');
            $this->stdout("\nCreating '{$file}' ");

            // TODO - begin
            $stack = $dev;
            $stack = $this->removeServiceAttributes($stack, ['volumes' => '/.*/']);
            $stack = $this->removeServiceAttributes($stack, ['build' => '/.*/']);
            $stack = $this->removeServiceAttributes($stack, ['external_links' => '/.*/']);
            $stack = $this->removeServiceAttributes($stack, ['links' => '/TMP/']);
            $stack = $this->removeServiceAttributes($stack, ['environment' => '/\~\^dev/']);
            $stack = $this->removeServiceAttributes(
                $stack,
                ['volumes' => '/./']
            ); ## TODO - fix me, both volume remove calls needed?!?
            $stack = $this->removeServices($stack, ['/TMP$/',]);
            $stack = $this->removeServices($stack, ['/tmp$/',]);
            // TODO - end

            $stack = ArrayHelper::merge($stack, $this->readFile("{$path}/{$file}.tpl.yml", $replacements));

            $stack = $this->removeServiceAttributes($stack, ['image' => '/REMOVE/']);

            $filePrefix = basename($baseFile, '.yml') . '-';
            $filePrefix = str_replace('docker-compose-', '', $filePrefix);

            $outputFile = "{$this->outputDirectory}/{$filePrefix}{$file}.yml";
            $this->writeFile($outputFile, $this->dump($stack));

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
        // TODO: make generic functions
        foreach ($stack as $serviceName => $serviceAttributes) {
            if (is_array($serviceAttributes)) {
                foreach ($serviceAttributes as $attrName => $attrData) {
                    foreach ($removeAttributes AS $removeAttr => $removeValuePattern) {
                        if ($removeAttr == $attrName) {
                            if (!is_array($attrData)) {
                                if (preg_match($removeValuePattern, $attrData)) {
                                    echo "O";
                                    unset($stack[$serviceName][$attrName]);
                                    continue;
                                }
                            } elseif (is_array($stack[$serviceName][$attrName])) {
                                foreach ($attrData AS $i => $value) {
                                    if (preg_match($removeValuePattern, $value)) {
                                        unset($stack[$serviceName][$attrName][$i]);
                                        if (count($stack[$serviceName][$attrName]) == 0) {
                                            unset($stack[$serviceName][$attrName]);
                                        } else {
                                            $stack[$serviceName][$attrName] = array_values(
                                                $stack[$serviceName][$attrName]
                                            );
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