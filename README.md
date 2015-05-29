YAML Converter Extension for Yii 2.0 Framework
==============================================

Converts and merges YAML files based on YAML rules

---

**Project is in initial development phase, do not use in production**

---


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist dmstr/yii2-yaml-converter-command "*"
```

to the require section of your `composer.json` file.

Register a converter command in console configuration

	'controllerMap' => [
		'stack-converter' => 'dmstr\console\controllers\DockerStackConverterController'
	],

Usage
-----

Once the extension is installed, use it on the command line:

    ./yii yaml/convert-docker-compose \
        --dockerComposeFile=@app/docker-compose.yml \
        --templateDirectory=@app/build/stacks-tpl \
        --outputDirectory=@app
        
Alternative alias        

    ./yii yaml/convert-docker-compose \
        --dockerComposeFile=@root/docker-compose.yml \
        --templateDirectory=@root/build/stacks-tpl \
        --outputDirectory=@root
