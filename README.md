YAML Converter Extension for Yii 2.0 Framework
==============================================
Converts and merges YAML files based on YAML rules

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist dmstr/yii2-yaml-converter-command "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, use it on the command line:

    ./yii yaml/convert \
        --dockerComposeFile=@root/docker-compose.yml \
        --templateDirectory=@root/build/ \
        --outputDirectory=@root/build/stacks-generated