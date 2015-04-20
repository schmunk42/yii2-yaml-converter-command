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
    public $outputDirectory = '@app';

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
        $dev = $this->readFile($this->dockerComposeFile);

        $file = 'test-local';
        $this->stdout("\nCreating '{$file}' ");
        $dev   = $this->removeServiceAttributes($dev, ['volumes' => '/.*/', 'build' => '/.*/']);
        $stack = $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml");
        $stack = ArrayHelper::merge($dev, $stack);
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $file = 'ci-gitlab';
        $this->stdout("\nCreating '{$file}' ");
        $stack = $this->removeServiceAttributes($stack, ['volumes' => '/.*/', 'build' => '/.*/']);
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $file = 'prestaging-local';
        $this->stdout("\nCreating '{$file}' ");
        $stack = $this->removeServiceAttributes($stack, ['volumes' => '/.*/', 'build' => '/.*/']);
        #####$this->removeAttributes($stack['appcli'], ['links']);
        $this->removeAttributes($stack['apicli'], ['links']);
        $stack = $this->removeServices($stack, ['/^TMP/',]);
        $stack = $this->removeServiceAttributes($stack, ['links' => '/^TMP/']);
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $stagingFiles = FileHelper::findFiles(\Yii::getAlias($this->templateDirectory),['only'=>['staging*']]);
        foreach($stagingFiles AS $filePath) {
            $file = basename($filePath,'.tpl.yml');
            $this->stdout("\nCreating '{$file}'...\n");
            $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
            $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        }

        $this->stdout("\n\nDone.\n");
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
                                echo "X";
                                unset($stack[$serviceName][$attrName]);
                                continue;
                            }
                            foreach ($attrData AS $value) {
                                if (preg_match($removeValuePattern, $value)) {
                                    unset($stack[$serviceName][$attrName]);
                                    echo "X";
                                } else {
                                    echo ".";
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