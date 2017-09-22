YAML Converter Extension for Yii 2.0 Framework
==============================================

---

:rotating_light: **THIS REPOSITORY IS DEPRECATED**

---

TL;dr
-----

This is a console command to convert and merges YAML files.

This project was developed as a helper-tool for our Docker development and build process and may be currently in a
heavily biased state.

> Note you can run this command directly with `docker`, since it's part of [Phundament](https://github.com/phundament/app). See below for details.


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

### Within a Yii 2.0 application

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


### Via Docker image

You can run the converter for Docker stacks directly with Docker, from the `phundament/app` Docker image

    docker run phundament/app ./yii help yaml/convert-docker-compose
    
After checking the options, we may mount i.e. `tests` to `/mnt` in the container and run the conversion process
   
    docker run -v `pwd`/tests:/mnt phundament/app ./yii yaml/convert-docker-compose \
        --dockerComposeFile=/mnt/base.yml \
        --templateDirectory=/mnt/stacks-tpl \
        --templateReplacementsFile=/mnt/eny.yml \
        --outputDirectory=/mnt/stacks-gen

> Hint! You can check the installed version with `docker run phundament/app composer show -i dmstr/yii2-yaml-converter-command`

How it works?
-------------
    
### `docker-compose` converter

The conversion process follows the following simple ruleset

- read `dockerComposeFile` as new *base-file*
- find `*.tpl.yml` files in `templateDirectory`
- read `templateReplacementsFile` and replace values in every template
- apply `.variable` rules (like `CLEAN`)
- merge template with *base-file* and write new file to `outputDirectory`
- if there's a subfolder with the same name as the template, recurse into that folder and repeat the process with the new file, just created in the last step 

> You can use `.image: CLEAN` to remove the `image` attribute of a service.
