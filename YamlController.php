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
        $stack = ArrayHelper::merge($dev, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", Yaml::dump($stack, 10));

        $file = 'ci-gitlab';
        $this->stdout("Creating '{$file}'...\n");
        // TODO: make generic functions
        foreach ($stack as $i => $services) {
            foreach ($services as $j => $service) {
                unset($stack[$i]['volumes']);
                unset($stack[$i]['build']);
            }
        }
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", Yaml::dump($stack, 10));

        $file = 'staging-tutum';
        $this->stdout("Creating '{$file}'...\n");
        // TODO: make generic functions
        foreach ($stack as $name => $attrs) {
            unset($stack[$name]['volumes']);
            foreach ($attrs as $j => $data) {
                unset($stack[$name]['volumes']);
                unset($stack[$name]['build']);
            }
            switch ($name) {
                case 'seleniumchrome':
                case 'seleniumfirefox':
                    unset($stack[$name]);
                    break;
            }
        }
        $stack = ArrayHelper::merge($stack, $this->readFile("{$this->templateDirectory}/{$file}.tpl.yml"));
        $this->writeFile("{$this->outputDirectory}/docker-compose-{$file}.yml", Yaml::dump($stack, 10));

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
}