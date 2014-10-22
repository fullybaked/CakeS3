# DEPRECATED

As may be abundantly clear I am no longer maintaining this component. It will *not* be updated for CakePHP 3.  It was a learning tool that some few people found useful, but moving forward I'd rather put my time into supporting one of the other S3 libraries on Packagist. Not only framework agnostic but reducing the number of S3 library clones by one.

I'll leave the repo up for posterity and anyone that is still relying on it.



# CakeS3 Plugin for CakePHP 2.0

The CakeS3 plugin for CakePHP allows easy integration with an Amazon S3 instance to be dropped into your app.

Utilising [tpyo's](https://github.com/tpyo/amazon-s3-php-class) amazon-s3-php-class, which in his words is..

> This class is a standalone Amazon S3 REST implementation for PHP 5.2.x (using CURL), that supports large file uploads and doesn’t require PEAR.

This means it can be dropped in and run on a variety of hosting platforms.

## Installation

Do one of the following to use this plugin in your CakePHP 2.0 app

	git clone https://fullybaked@github.com/fullybaked/CakeS3.git app/Plugin/CakeS3

or

	git submodule add https://fullybaked@github.com/fullybaked/CakeS3.git app/Plugin/CakeS3

or

Just download the package and unzip it into your app/Plugin directory


Then remember to add the following to your Config/bootstrap.php

	//Add the CakeS3 plugin
	CakePlugin::load('CakeS3');

## Setup

Add the following to your controller $components instance variable

	// in controller components var
	$components = array(
		'CakeS3.CakeS3' => array(
			's3Key' => 'YOUR_AMAZON_S3_KEY',
			's3Secret' => 'YOUR_AMAZON_S3_SECRET_KEY',
			'bucket' => 'BUCKET_NAME',
			'endpoint' => 's3.amazonaws.com' // [optional] Only required if your endpoint is not s3.amazonaws.com
		)
	);

Or add the settings before invoking the Plugin in Config/bootstrap.php

	//Add the CakeS3 plugin
	Configure::write('CakeS3', array(
			's3Key' => 'YOUR_AMAZON_S3_KEY',
			's3Secret' => 'YOUR_AMAZON_S3_SECRET_KEY',
			'bucket' => 'BUCKET_NAME',
			'endpoint' => 's3.amazonaws.com' // [optional] Only required if your endpoint is not s3.amazonaws.com
			)
	);
	CakePlugin::load('CakeS3');

And in your controller, only include the Component name

	$components = array(
		'CakeS3.CakeS3'
	);


## Usage

####List the contents of a bucket

	$contents = $this->CakeS3->listBucketContents();

####List the contents of a path relative to the bucket i.e. a folder

	$contents = $this->CakeS3->listFolderContents('path/relative/to/bucket/');

####Upload a file to S3

	$response = $this->CakeS3->putObject('/path/to/local/file', 'path/relative/to/bucket/', [$permission]);

The response value is an array with the following values

	array(
		'name' => [name of saved file],
		'url' => [path to the resource on S3],
		'size' => [size of the resource on S3]
	)

Allowed $permission Values:

The allowed values for permissions are wrapped by the component and are accessed via a wrapper method

	$this->CakeS3->permission('private');
	$this->CakeS3->permission('public_read');
	$this->CakeS3->permission('public_read_write');
	$this->CakeS3->permission('authenticated_read');

Example:

	$response = $this->CakeS3->putObject('/path/to/local/file', 'path/relative/to/bucket/', $this->CakeS3->permission('authenticated_read'));

####Accessing Files With `authenticated_read` Permission

	$auth_path = $this->CakeS3->authenticateUrl($full_s3_path, [$lifetime]);

If a file is stored on S3 with `authenticated_read` permissions, it is only accessible via a secure token.  This method generates a new URL to
reach the resource with the correct token, and a time to live.  The default is 30 seconds as generally the URL will be recreated on refresh, but
not accessible if copied out of the app

####Delete a file from S3

	$response = $this->CakeS3->deleteObject('path/relative/to/bucket/');

####Retrieve an object from S3 location

	$object = $this->CakeS3->getObject('path/relative/to/bucket/', [$path_to_store_local_copy = false]);

####Retrieve information about an object on S3

	$info = $this->CakeS3->getObjectInfo('path/relative/to/bucket/');

####Change the bucket name on the fly using method chaining

	$response = $this->CakeS3->bucket('new_bucket')->{any_of_the_above_methods};

## Features

* List contents of given bucket
* List folder contents within a bucket
* Push files to a location on S3
* Retrieve an object from S3
* Retrieve information about an object from S3
* Delete files from S3
* Generate authenticated URL's for protected resources

## Attribution

The S3 php class used by this plugin was developed by [tpyo](https://github.com/tpyo/amazon-s3-php-class)

## Changelog

**Version 0.5 - 7th May 2013 BACKWARDS INCOMPATIBLE CHANGES**

* Brought the component code in line with PSR coding standards (which means all method names have changed to camelCase)


**Version 0.4 - 14th November 2012**

* putObject method modified to accept MIME type parameter

**Version 0.3 - 17th October 2012**

* Added better access to permission types via wrapper method
* Added support for accessing protected resources on S3 via authenticated URL's

**Version 0.2 - 6th March 2012**

* Added get_object method
* Added get\_object\_info method
* Made the build\_url\_to\_file method public

## Contributions

Contributions are welcome.  If you think you can improve this plugin, please fork the repo, add to it and send me a pull request.
All accepted enhancement authors will be listed below.

- [Ollie Lawson](https://github.com/ollielawson)
- [motodimago](https://github.com/motodimago)

## License

Released under the [MIT license](http://www.opensource.org/licenses/MIT).
