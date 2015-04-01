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
    public $stacks = ['test', 'ci', 'staging', 'production'];

    public function actionConvert()
    {
        $file      = file_get_contents(\Yii::getAlias($this->dockerComposeFile));
        $base      = Yaml::parse($file);

        $file   = file_get_contents(\Yii::getAlias($this->templateDirectory . '/test.yml'));
        $stack  = Yaml::parse($file);
        $stacks = ArrayHelper::merge($base, $stack);
        $data   = Yaml::dump($stacks, 10);
        file_put_contents(\Yii::getAlias($this->outputDirectory . '/test.yml'), $data);

        foreach ($stacks as $i => $services) {
            foreach ($services as $j => $service) {
                #var_dump($stacks[0][$i]);
                unset($stacks[$i]['volumes']);
                unset($stacks[$i]['build']);
                ###$stacks[0][$i]['tags'] = ['app'];
            }
            if ($i == 'ftp') {
                ###unset($stacks[0][$i]);
            }
            if ($i == 'db') {
                ###unset($stacks[0][$i]);
            }
        }

        $file   = file_get_contents(\Yii::getAlias($this->templateDirectory . '/ci.yml'));
        $stack  = Yaml::parse($file);
        $stacks = ArrayHelper::merge($stacks, $stack);
        $data   = Yaml::dump($stacks, 10);
        file_put_contents(\Yii::getAlias($this->outputDirectory . '/ci.yml'), $data);

        $file   = file_get_contents(\Yii::getAlias($this->templateDirectory . '/staging.yml'));
        $stack  = Yaml::parse($file);
        $stacks = ArrayHelper::merge($stacks, $stack);
        $data   = Yaml::dump($stacks, 10);
        file_put_contents(\Yii::getAlias($this->outputDirectory . '/staging.yml'), $data);

        $this->stdout("Done.\n");
    }

}