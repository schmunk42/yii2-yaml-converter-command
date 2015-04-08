<?php
/**
 * Created by PhpStorm.
 * User: tobias
 * Date: 30.03.15
 * Time: 20:22
 */

namespace dmstr\console\controllers;

use Symfony\Component\Yaml\Yaml;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class YamlController extends Controller
{

    public $dockerComposeFile = '@app/docker-compose.yml';
    public $templateDirectory = '@app/build';
    public $outputDirectory = '@app/build/stacks-generated';

    #### public $stacks = ['test', 'ci', 'staging', 'production'];


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

    public function actionConvert()
    {
        $this->stdout("Starting YAML convert process...\n");
        $dev   = $this->readFile($this->dockerComposeFile);

        $this->stdout("Creating 'local-test'...\n");
        $test  = ArrayHelper::merge($dev, $this->readFile($this->templateDirectory.'/local-test.tpl.yml'));
        $this->writeFile($this->templateDirectory.'/stacks-generated/local-test.yml', Yaml::dump($test, 10));

        $this->stdout("Creating 'gitlab-ci'...\n");
        $ci = $test;
        foreach ($ci as $i => $services) {
            foreach ($services as $j => $service) {
                unset($ci[$i]['volumes']);
                unset($ci[$i]['build']);
            }
        }
        $ci  = ArrayHelper::merge($ci, $this->readFile($this->templateDirectory.'/gitlab-ci.tpl.yml'));
        $this->writeFile($this->templateDirectory.'/stacks-generated/gitlab-ci.yml', Yaml::dump($ci, 10));

        $this->stdout("Creating 'tutum-staging'...\n");
        $staging = $ci;
        foreach ($staging as $name => $attrs) {
            unset($staging[$name]['volumes']);
            foreach ($attrs as $j => $data) {
                unset($staging[$name]['volumes']);
                unset($staging[$name]['build']);
            }
            switch ($name) {
                case 'seleniumchrome':
                case 'seleniumfirefox':
                    unset($staging[$name]);
                    break;
            }
        }
        $staging  = ArrayHelper::merge($staging, $this->readFile($this->templateDirectory.'/tutum-staging.tpl.yml'));
        $this->writeFile($this->templateDirectory.'/stacks-generated/tutum-staging.yml', Yaml::dump($staging, 10));

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