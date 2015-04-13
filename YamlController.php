<?php
/**
 * YAML converter
 */

namespace dmstr\console\controllers;

use Symfony\Component\Yaml\Yaml;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

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
        $dev = $this->readFile($this->dockerComposeFile);

        $file = 'test-local';
        $this->stdout("Creating '{$file}'...\n");
        $dev   = $this->removeServiceAttributes($dev, ['volumes', 'build']);
        $stack = $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml");
        $stack = ArrayHelper::merge($dev, $stack);
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $file = 'ci-gitlab';
        $this->stdout("Creating '{$file}'...\n");
        $stack = $this->removeServiceAttributes($stack, ['volumes', 'build']);
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $file = 'staging-local';
        $this->stdout("Creating '{$file}'...\n");
        $stack = $this->removeServiceAttributes($stack, ['volumes', 'build']);
        $this->removeAttributes($stack['appcli'], ['links']);
        $stack = $this->removeServices(
            $stack,
            ['appbuilder', 'starragcombuilder', 'seleniumchrome', 'seleniumfirefox']
        );
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $file = 'staging-tutum';
        $this->stdout("Creating '{$file}'...\n");
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", $this->dump($stack));

        $this->stdout("Done.\n");
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

    private function dump($stack){
        $this->ksortRecursive($stack);
        return Yaml::dump($stack, 10);
    }

    /**
     * helper function
     *
     * @param $stack
     * @param $attributes
     *
     * @return mixed
     */
    private function removeServiceAttributes($stack, $attributes)
    {
        // TODO: make generic functions
        foreach ($stack as $i => $services) {
            if (is_array($services)) {
                foreach ($services as $j => $service) {
                    foreach ($attributes AS $attr) {
                        unset($stack[$i][$attr]);
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
    private function removeServices($stack, $services)
    {
        foreach ($services AS $name) {
            unset($stack[$name]);
        }
        return $stack;
    }

    private function removeAttributes(&$service, $attributes)
    {
        foreach ($attributes AS $attr) {
            unset($service[$attr]);
        }
    }

    private function ksortRecursive(&$array, $sort_flags = SORT_REGULAR) {
        if (!is_array($array)) return false;
        ksort($array, $sort_flags);
        foreach ($array as &$arr) {
            $this->ksortRecursive($arr, $sort_flags);
        }
        return true;
    }
}