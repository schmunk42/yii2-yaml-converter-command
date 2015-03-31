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
use yii\helpers\Console;

class YamlController extends Controller
{

    public function actionConvert()
    {
        $file   = file_get_contents(\Yii::getAlias('@app/../docker-compose.yml'));
        $stacks[0] = Yaml::parse($file);

        foreach ($stacks[0] as $i => $services) {
            foreach ($services as $j => $service) {
                #var_dump($stacks[0][$i]);
                unset($stacks[0][$i]['volumes']);
                unset($stacks[0][$i]['build']);

                ###$stacks[0][$i]['tags'] = ['app'];
            }
            if ($i == 'ftp') {
                ###unset($stacks[0][$i]);
            }
            if ($i == 'db') {
                ###unset($stacks[0][$i]);
            }
        }

        $file   = file_get_contents(\Yii::getAlias('@app/../stacks/templates/test.yml'));
        $stacks[1] = Yaml::parse($file);
        $stacks = ArrayHelper::merge($stacks[0], $stacks[1]);
        $data   = Yaml::dump($stacks, 2, 4, true, true);
        file_put_contents(\Yii::getAlias('@app/../stacks/test.yml'), $data);

        $file   = file_get_contents(\Yii::getAlias('@app/../stacks/templates/staging.yml'));
        $stacks[2] = Yaml::parse($file);
        $stacks = ArrayHelper::merge($stacks, $stacks[2]);
        $data   = Yaml::dump($stacks, 2, 4, true, true);
        file_put_contents(\Yii::getAlias('@app/../stacks/staging.yml'), $data);

        $this->stdout("Done.\n");
    }

}