<?php
/**
 * YAML converter
 */

namespace dmstr\console\controllers;

use Symfony\Component\Yaml\Yaml;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class YamlController extends Controller
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
            ['dockerComposeFile', 'templateDirectory', 'outputDirectory']
        );
    }

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
        $files = FileHelper::findFiles($path, ['only' => ['/*.tpl.yml']]);
        $dev   = $this->readFile($baseFile);

        foreach ($files AS $filePath) {
            $file = basename($filePath, '.tpl.yml');
            $this->stdout("\nCreating '{$file}' ");

            // TODO - begin
            $stack = $this->removeServiceAttributes($dev, ['volumes' => '/.*/']);
            $stack = $this->removeServiceAttributes($stack, ['build' => '/.*/']);
            $stack = $this->removeServiceAttributes($stack, ['external_links' => '/.*/']);
            $stack = $this->removeServiceAttributes($stack, ['links' => '/TMP/']);
            $stack = $this->removeServiceAttributes($stack, ['environment' => '/\~\^dev/']);
            $stack = $this->removeServices($stack, ['/TMP$/',]);
            // TODO - end

            $stack = ArrayHelper::merge($stack, $this->readFile("{$path}/{$file}.tpl.yml"));

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
     * @param $file YAML file to read and parse
     *
     * @return array data from the YAML file
     */
    public function readFile($file)
    {
        $file = file_get_contents(\Yii::getAlias($file));
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

    private function dump($stack)
    {
        $this->ksortRecursive($stack);
        return Yaml::dump($stack, 10);
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