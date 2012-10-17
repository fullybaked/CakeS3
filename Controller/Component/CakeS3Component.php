<?php
App::import('Lib', 'CakeS3.S3');
/**
 * CakeS3 component acts as an interface between CakePHP controllers
 * and undesigned's S3 class for PHP
 * 
 * @author Dave Baker
 * @copyright Dave Baker (Fully Baked) 2012
 * @see http://undesigned.org.za/2007/10/22/amazon-s3-phppublic - class for the S3 class included in the vendor dir
 */	
class CakeS3Component extends Component {
	
	// ACL flags - copied from S3 class to be used from here.
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';
			
	/**
	 * Amazon S3 key 
	 * @var string
	 */
	private $s3_key = null;
	
	/**
	 * Amazon S3 secret key
	 * @var string
	 */
	private $s3_secret = null;
	
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
	private $use_ssl = true;
	

	/**
	 * component constructor - set up local vars based on settings array in controller
	 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		// setup the instance vars		
		if (!empty($settings)) {
			foreach ($settings as $var => $val) {
				$this->$var = $val;
			}
		}		

		if (empty($this->s3_key) || empty($this->s3_secret)) {
			throw new Exception ('S3 Keys not set up. Unable to connect');
		}
		S3::setAuth($this->s3_key, $this->s3_secret);		
	}
	
	
	/**
	 * Amazon S3 doesn't support folders as such, so this method spoofs
	 * that by fetching the 
	 * @param string $folder 
	 * @return array 
	 */
	public function list_folder_contents($folder) {
		$contents = $this->list_bucket_contents($this->bucket);
		
		$files = array();
		
		foreach ($contents as $object)	{ 					
			if (dirname($object['name']) .'/' == $folder) {
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
	public function list_bucket_contents() {		
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
	 * @param string $file_path_to_upload - absolute path to local file that needs to be uploaded
	 * @param string $location_on_s3 - path the file should have on S3 relative to the current bucket
	 * @param string $permission - the access permissions the file should have, defaults to Public Read Acess
	 * @return mixed - returns an array with details of the uploaded file on S3 for success, FALSE on failure
	 */
	public function put_object($file_path_to_upload, $location_on_s3, $permission = self::ACL_PUBLIC_READ) {		
		try {
			S3::putObject(S3::inputFile($file_path_to_upload), $this->bucket, $location_on_s3, $permission);
			$info = $this->get_object_info($location_on_s3);
			return array(
				'name' => basename($location_on_s3),
				'url' => $this->build_url_to_file($location_on_s3),
				'size' => $info['size']
			);
		} catch (Exception $e) {
			return false;
		}		
	}
	
	/**
	 * get information about an object in the current S3 bucket
	 * @param string $location_on_s3 - path the file should have on S3 relative to the current bucket
	 * @return array 
	 */
	public function get_object_info($location_on_s3) {
		return S3::getObjectInfo($this->bucket, $location_on_s3);
	}
	
	/**
	 * delete an object from a location in the current S3 bucket
	 * @param string $location_on_s3 - path to the object relative to the bucket
	 * @return boolean - TRUE on successful delete, FALSE on failure.
	 */
	public function delete_object($location_on_s3) {
		try {
			S3::deleteObject($this->bucket, $location_on_s3);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}
	
	/**
	 * retrieve an object from a location in the current S3 bucket
	 * @param string $location_on_s3 - path to the object relative to the bucket
	 * @return mixed - FALSE on failure, Object array on success
	 */
	public function get_object($location_on_s3, $path_to_local_file = false) {
		try {
			$object = S3::getObject($this->bucket, $location_on_s3, $path_to_local_file);
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
	public function bucket($bucket) {
		$this->bucket = $bucket;
		return $this;
	}
	
	
	/**
	 * build a url on S3 for a file based on its location and the bucket
	 * @param string $file
	 * @return string
	 */
	public function build_url_to_file($file) {
		$url = ($this->use_ssl) ? 'https://' : 'http:';
		$url .= $this->endpoint . '/';
		$url .= $this->bucket . '/';
		$url .= $file;
		return $url;
	}

	/**
	 * wrapper method for accessing the class constants without 
	 * actually including the class directly with App::uses()
	 * allowed $permission values are
	 * 	- private
	 * 	- public_read
	 * 	- public_read_write
	 *  - authenticated_read
	 * @param string $permission (see above)
	 * @return string
	 * @access public
	 */
	public function permission($permission) {
		$permission = strtoupper("self::ACL_$permission");
		return constant($permission);
	}

}
?>