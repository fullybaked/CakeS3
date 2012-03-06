# CakeS3 Plugin for CakePHP 2.0

## Setup

Do one of the following to use this plugin in your CakePHP 2.0 app

	git clone https://fullybaked@github.com/fullybaked/CakeS3.git app/Plugin/CakeS3
	
or

	git submodule add https://fullybaked@github.com/fullybaked/CakeS3.git app/Plugin/CakeS3
	
or

Just download the package and unzip it into your app/Plugin directory


Then remember to add the following to your Config/bootstrap.php

	//Add the CakeS3 plugin
	CakePlugin::load('CakeS3');

## Features

* List contents of given bucket
* List folder contents within a bucket
* Push files to a location on S3 
* Delete files from S3

## Attribution

The S3 php class used by this plugin was developed by [tpyo](https://github.com/tpyo/amazon-s3-php-class)

## License

Released under the [MIT license](http://www.opensource.org/licenses/MIT).
