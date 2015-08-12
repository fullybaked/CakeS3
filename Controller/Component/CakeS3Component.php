<?php

App::import('Lib', 'CakeS3.S3');

/**
 * CakeS3 component acts as an interface between CakePHP controllers
 * and undesigned's S3 class for PHP
 *
 * @author Dave Baker
 * @copyright Dave Baker (Fully Baked) 2012
 * @see https://github.com/fullybaked/CakeS3 - class for the S3 class included in the vendor dir
 */
class CakeS3Component extends Component
{
    // ACL flags - copied from S3 class to be used from here.

    const ACL_PRIVATE = 'private';
    const ACL_PUBLIC_READ = 'public-read';
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    const ACL_AUTHENTICATED_READ = 'authenticated-read';

    /**
     * Amazon S3 key
     * @var string
     */
    private $s3Key = null;

    /**
     * Amazon S3 secret key
     * @var string
     */
    private $s3Secret = null;

    /**
     * Name of bucket on S3
     * @var string
     */
    private $bucket = null;

    /**
     * Endpoint URL for image access - defaults to the Amazon S3 url,
     * but can be changed in settings if using a cname for your own URL
     * @var string
     * @default 's3.amazonaws.com'
     */
    private $endpoint = 's3.amazonaws.com';

    /**
     * Flag to use SSL connection with S3
     * @var boolean
     */
    private $useSsl = true;

    /**
     * component constructor - set up local vars based on settings array in controller
     */
    public function __construct(ComponentCollection $collection, $settings = array())
    {
        parent::__construct($collection, $settings);
        // setup the instance vars
        if (!empty($settings)) {
            foreach ($settings as $var => $val) {
                $this->$var = $val;
            }
        } else if($config = Configure::read('CakeS3')) {
            foreach ($config as $var => $val) {
                $this->$var = $val;
            }
        }

        if (empty($this->s3Key) || empty($this->s3Secret)) {
            throw new Exception('S3 Keys not set up. Unable to connect');
        }
        S3::setAuth($this->s3Key, $this->s3Secret);
        S3::setEndpoint($this->endpoint);
    }

    /**
     * Amazon S3 doesn't support folders as such, so this method spoofs
     * that by fetching the
     * @param string $folder
     * @return array
     */
    public function listFolderContents($folder)
    {
        $contents = $this->listBucketContents($this->bucket);

        $files = array();

        foreach ($contents as $object) {
            if (dirname($object['name']) . '/' == $folder) {
                $files[] = $object;
            }
        }

        return $files;
    }

    /**
     * list the contents of a bucket. catches exceptions as
     * a lack of bucket / bucket contents and returns an empty array
     * @return array
     */
    public function listBucketContents()
    {
        try {
            $contents = S3::getBucket($this->bucket);
        } catch (Exception $e) {
            $contents = array();
        }
        return $contents;
    }

    /**
     * push a file to a location on S3 based on a path from the local server that this plugin
     * is running on.
     * @param string $filePathToUpload - absolute path to local file that needs to be uploaded
     * @param string $locationOnS3 - path the file should have on S3 relative to the current bucket
     * @param string $permission - the access permissions the file should have, defaults to Public Read Acess
     * @param string $mimeType - set the mime type of the object in S3, defaults to autodetect
     * @return mixed - returns an array with details of the uploaded file on S3 for success, FALSE on failure
     */
    public function putObject($filePathToUpload, $locationOnS3, $permission = self::ACL_PUBLIC_READ, $mimeType = null)
    {

        try {
            S3::putObject(S3::inputFile($filePathToUpload), $this->bucket, $locationOnS3, $permission, array(), $mimeType);
            $info = $this->getObjectInfo($locationOnS3);
            return array(
                    'name' => basename($locationOnS3),
                    'url' => $this->buildUrlToFile($locationOnS3),
                    'size' => $info['size']
            );
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * create a new "folder" (prefix) - wrapper for putObject function
     * 
     * @param string $destination - relative path to bucket - where the folder should be created, 
     * 				   if the destination is the bucket itself, false should be provided 
     * @param string $folderName - name of the folder, that should be created
     * @param string $permission - the access permissions the file should have, defaults to Public Read Acess
     * @return mixed - returns an array with details of the uploaded file on S3 for success, FALSE on failure
     */
    public function createFolder($destination = false, $folderName, $permission = self::ACL_PUBLIC_READ) {
    	if (!$folderName) {
    		throw new CakeException('Folder name is required');
    	}
    	
    	if ($destination && $destination != '/') {
    		$destination = trim($destination, "/").'/';
    	} else {
    		$destination = '';
    	}

	$locationOnS3 = $destination . trim($folderName, "/") . '/';
		
	// create temp file
	$filePathToUpload = APP.'tmp/'.md5(microtime(true) . $locationOnS3);
	$f = fopen($filePathToUpload, 'a+');
	fclose($f);

	$result = $this->putObject($filePathToUpload, $locationOnS3, $permission);

	// delete tmp file
	@unlink($filePathToUpload);
		
	return $result;
    }

    /**
     * copy to s3 on the file on s3
     * @param string $sourceLocationOnS3 - path of source file to be copied in the S3 server
     * @param string $copyLocationOnS3 - path to copy
     * @param string $source_bucket - if the bucket of the source is different from the current S3 bucket
     * @param string $copy_bucket -  if the bucket of to copy is different from the current S3 bucket
     * @param string $permission - the access permissions the file should have, defaults to Public Read Acess
     * @param string $mimeType - set the mime type of the object in S3, defaults to autodetect
     * @return mixed - returns an array with details of the uploaded file on S3 for success, FALSE on failure
     */
	public function copyObject($sourceLocationOnS3, $copyLocationOnS3, $sourceBucket = false, $copyBucket = false, $permission = self::ACL_PUBLIC_READ, $mimeType = array()) {

		try {
			if ($sourceBucket == false || $sourceBucket == null) {
				$sourceBucket = $this->bucket;
			}
			if ($copyBucket == false || $copyBucket == null) {
				$copyBucket = $this->bucket;
			}
			S3::copyObject($sourceBucket, $sourceLocationOnS3, $copyBucket, $copyLocationOnS3, $permission, array(), $mimeType);
			$info = S3::getObjectInfo($copyBucket, $copyLocationOnS3);
			return array(
					'name' => basename($copyLocationOnS3),
					'url' => $this->buildUrlToFile($copyLocationOnS3),
					'size' => $info['size']
			);
		} catch (Exception $e) {
			return false;
		}
	}

    /**
     * get information about an object in the current S3 bucket
     * @param string $locationOnS3 - path the file should have on S3 relative to the current bucket
     * @return array
     */
    public function getObjectInfo($locationOnS3)
    {
        return S3::getObjectInfo($this->bucket, $locationOnS3);
    }

    /**
     * delete an object from a location in the current S3 bucket
     * @param string $locationOnS3 - path to the object relative to the bucket
     * @return boolean - TRUE on successful delete, FALSE on failure.
     */
    public function deleteObject($locationOnS3)
    {
        try {
            S3::deleteObject($this->bucket, $locationOnS3);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * retrieve an object from a location in the current S3 bucket
     * @param string $locationOnS3 - path to the object relative to the bucket
     * @return mixed - FALSE on failure, Object array on success
     */
    public function getObject($locationOnS3, $pathToLocalFile = false)
    {
        try {
            $object = S3::getObject($this->bucket, $locationOnS3, $pathToLocalFile);
            return $object;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * setter method for the bucket name
     * @param string $bucket
     * @return CakeS3Component
     */
    public function bucket($bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }

    /**
     * build a url on S3 for a file based on its location and the bucket
     * @param string $file
     * @return string
     */
    public function buildUrlToFile($file)
    {
        $url = ($this->useSsl) ? 'https://' : 'http://';
        $url .= $this->endpoint . '/';
        $url .= $this->bucket . '/';
        $url .= $file;
        return $url;
    }

    /**
     * setter for instance var useSsl
     * @param boolean $flag
     */
    public function useSsl($flag = true)
    {
        $this->useSsl = $flag;
    }

    public static function createSafeFilename($input)
    {
        if ($input) {

            //decode entities and make lowercase
            $input = strtolower(str_replace(' ', '_', html_entity_decode($input)));

            //remove ™ symbols
            $input = str_replace(array('™', '"', "'"), '', $input);

            //remove any unwanted chars
            //$input = preg_replace('/[^a-zA-Z0-9\.]/', '_', $input);
            $input = preg_replace("'\s+'", '_', $input);

            //remove double _
            while (strpos($input, '__')) {
                $input = str_replace('__', '_', $input);
            }
            //remove trailing _
            $input = trim($input, '_');
        }
        return $input;
    }

    /**
     * deconstruct a full S3 resource path to return
     * the path to the file relative to the bucket
     * @param string $url
     * @return string
     * @access public
     * @throws Exception
     */
    public function relativePath($url)
    {
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Badly formatted URL passed to relativePath");
        }
        $remove = array(
            ($this->useSsl) ? 'https://' : 'http://',
            $this->endpoint . '/',
            $this->bucket . '/'
        );
        $relativePath = str_replace($remove, '', $url);
        return $relativePath;
    }


    /**
     * wrapper method for accessing the class constants without
     * actually including the class directly with App::uses()
     * allowed $permission values are
     *  - private
     *  - public_read
     *  - public_read_write
     *  - authenticated_read
     * @param string $permission (see above)
     * @return string
     * @access public
     */
    public function permission($permission)
    {
        $permission = strtoupper("self::ACL_$permission");
        return constant($permission);
    }

    /**
     * generate a url to authenticated content on S3
     * @param string $uri - Full URL to the S3 resource
     * @param integer $lifetime - number of seconds this url will be valid
     * @return string - Authenticated URL to access resource
     * @access public
     */
    public function authenticatedUrl($uri, $lifetime = 60)
    {
        $url = S3::getAuthenticatedURL($this->bucket, $this->relativePath($uri), $lifetime, false, $this->useSsl);
        return $url;
    }

}
