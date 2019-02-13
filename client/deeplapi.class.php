<?php

abstract class DeepLApi extends DeepLData {
	protected $endPoint		= '';
	private $endPointURL	= false;
	protected $authKey 	= '';
	protected $log 	= false;
	protected $headers 	= array();

	protected $cache_dir 	= false;

	protected $request 		= array();
	protected $uniqid 		= false;
	protected $cache_file 	= false;
	protected $response 	= false;
	public $cache_prefix 	= false;
	protected $cache_id 	= false;

	public $allow_cache 	= true;
	public $response_type 	= 'fresh';
	public $request_cache_file	= '';
	public $response_cache_file	= '';

	public $start_microtime = 0;
	public $end_microtime 	= 0;
	public $request_microtime = 0;

	const TIMEOUT = 15;

	public function __construct( $languages = array(), $log = false ) {
		$this->authKey = DeepLConfiguration::getAPIKey();
		//$this->request['auth_key'] = $auth_key;
		$this->log = $log;

		$this->languages = DeepLConfiguration::DefaultsAllLanguages();

		if( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->doing_cron = true;
		}
		else {
			$this->doing_cron = false;
		}

		$this->instance = $this;

		$this->cache_dir = $this->getCacheDirectory();
	}

	public function wasItCached() {
		return $this->response_type === 'cache';
	}

	public function getTimeElapsed() {
		return $this->request_microtime;
	}

	protected function buildQuery( $mode = 'POST' ) {
		$args = array();
		if( count( $this->headers ) )
			$args['headers'] = $this->headers;

		$request = array( 'auth_key' => $this->authKey );
		if( $this->request ) {
			foreach( $this->request as $key => $value ) {
				$request[$key] = $value;
			}
		}

		if( $mode == 'POST' ) {
			// fixed in 0.3 by schalipp https://wordpress.org/support/topic/translate-button-not-doing-anything/#post-10825822
			// modified in 1.0 to send all strings in one request

			$bits = array();
			foreach( $request as $key => $value ) {
				if( is_array( $value ) ) {
					foreach( $value as $sub_key => $sub_value ) {
						$bits[] = "$key=$sub_value";
					}
				}
				else {
					$bits[] = "$key=$value";
				}
			}

			$args['body'] = implode( '&', $bits );
			//$args['body'] = http_build_query( $request );
		}
		else {
			$args['query'] = http_build_query( $request );
 }

		$args['timeout'] = self::TIMEOUT;

		$this->final_request = $args;
		$this->saveRequest();
		return $args;
	}

	public function getRequestUniqueID() {
		if( $this->cache_id ) {
			$this->uniqid = $this->cache_id;
		}
		elseif( !$this->uniqid ) {
			$this->uniqid = microtime();
		}
		return $this->uniqid;
	}

	public function isValidRequest() {
		return true;
	}

/*
	protected function maybeLimitRequestSize() {
	}
*/
	protected function saveRequest() {
		if( isset( $this->request_cache_file ) ) {
			file_put_contents( $this->request_cache_file, json_encode( $this->final_request ) );
		}
	}
	protected function doRequest( $mode = 'POST' ) {
		if( $mode == 'GET' ) {
			$response = $this->doGETRequest();
		}
		else {
			$response = $this->doPOSTRequest();
		}

		return $response;
	}

	static function getInstance() {
		return $this->instance;
	}

	protected function setCacheID( $idstring ) {
		$this->cache_id = $idstring;
	}

	public function setCachePrefix( $prefix = '' ) {
		if( !empty( $prefix ) ) {
			$this->cache_prefix = $prefix;
		}
		else {
			$this->cache_prefix = '';
		}
	}

	public function getCacheFile( $type ) {
		if( $type == 'response' ) {
			return $this->response_cache_file;
		}
		elseif( $type == 'request' ) {
			return $this->request_cache_file;
		}
	}

	public function setCacheNames() {
		$cache_name = '';
		if( !empty( $this->cache_prefix ) ) {
			$cache_name .= $this->cache_prefix .'-';
		}
		$cache_name .= $this->cache_id;
		$this->request_cache_file	= trailingslashit( $this->cache_dir ) . $cache_name . '-request';
		$this->response_cache_file	= trailingslashit( $this->cache_dir ) . $cache_name . '-response';
	}

	public function request() {

		$log_bits = array('class' => get_class($this));

		if( $this->allow_cache )
			$this->uniqid = $this->getRequestUniqueID();

		$log_bits['uniqid'] = $this->uniqid;

		$this->setCacheNames();
		$this->result = false;
		//plouf( $this );		die( 'opzirjizerj' );

		if( $this->isCacheValid( $this->response_cache_file ) ) {
			$this->response_type = 'cache';
			$this->why_no_cache = false;
			$this->result = file_get_contents( $this->response_cache_file );
			$log_bits['type'] = 'cached';
			
			//echo "\nIS CACHE";			//plouf( $this->result );
		}
		else {
			$log_bits['type'] = 'fresh';
			$log_bits['mode'] = $this->http_mode;
			$this->start_microtime = microtime( true );
			//echo "\nIS FRESH";
			$this->response_type = 'fresh';

			if( $this->http_mode == 'GET' ) {
				$response = $this->doRequest( 'GET' );
			}
			else {
				$response = $this->doRequest( 'POST' );
			}

			$this->end_microtime = microtime( true );
			$this->request_microtime = $this->end_microtime - $this->start_microtime;

			if( $response['response']['code'] > 299 ) {
				$log_bits['response_error'] = $response['response']['code'];
				$log_bits['response'] = $response['response'];
				if( $response['response']['code'] == 429 ) {
					$log_bits['error_cause'] = 'Excessive request size';
				}
				if( DeepLConfiguration::getLogLevel() ) wpdeepl_log($log_bits, "operation");
				return new WP_Error( 'deeplerror', implode( ' : ', $response['response'] ) );
			}

			if( array_key_exists( 'body', $response ) && strlen( $response['body'] ) )
				$this->result = $response['body'];
			else
				$this->result = false;

			if( $this->result ) {
				//echo " putting " . strlen( serialize( $this->result ) ) ." in "
				file_put_contents( $this->response_cache_file, $this->result );
			}

			$log_bits['request_microtime'] = $this->request_microtime;
		}

		if( DeepLConfiguration::getLogLevel() ) {
			if( DeepLConfiguration::getLogLevel() > 1 ) {
				$log_bits['args'] = $this->request;
			}
			else {
				foreach( array ('source_lang', 'target_lang') as $key ) {
					if( isset( $this->request[$key] ) ) {
						$log_bits[$key] = $this->request[$key];
					}
				}
			}
			if( isset( $this->request['text'] ) ) {
				$text_size = 0;
				foreach( $this->request['text'] as $i => $string ) {
					$text_size += strlen($string);
				}
			$log_bits['text_size'] = $text_size;
			}
		}

		if($this->response_type == 'fresh') {
			// test if JSON
			json_decode($this->result);
			if(json_last_error() == JSON_ERROR_NONE) {
				$log_bits['response'] = json_decode($this->result, 1 );
				if( DeepLConfiguration::getLogLevel() ) {
					if(isset( $log_bits['response']['translations'] ) ) {
						$translated_text_size = 0;
						foreach( $log_bits['response']['translations'] as $i => $translation ) {
							$translated_text_size += strlen( $translation['text'] );
						}
						$log_bits['translated_text_size'] = $translated_text_size;
					}
				}
			}
			else {
				$log_bits['response'] = $this->result;
			}
			
		}
		else {
			$log_bits['response'] = '--hidden response because cached request--';

		}
		$log_bits['response_length'] = strlen($this->result);

		if( $this->result ) {
			$this->result = json_decode( $this->result );
			$this->result = ( array ) $this->result;
			$this->result['response_type'] = $this->response_type;
			if( $this->why_no_cache ) {
				$log_bits['why_no_cache'] = $this->why_no_cache;
				$this->result['why_no_cache'] = $this->why_no_cache;
			}
			if( DeepLConfiguration::getLogLevel() ) wpdeepl_log($log_bits, "operation");
			return $this->result;
		}
		else {
			if( DeepLConfiguration::getLogLevel() ) wpdeepl_log($log_bits, "operation");
			return $this->response['response']['message'];
		}
	}

	public function getRequestTime( $result_in_milliseconds = false ) {
		if( !$this->request_microtime ) {
			return false;
		}

		if( $result_in_milliseconds ) {
			return floatval( $this->request_microtime );
		}
		else {
			return floatval( $this->request_microtime/1000 );
		}
	}

	public function getEndPointURL() {
		if( !$this->endPointURL ) {
			if( $this->endPoint === '' ) {
				$this->endPointURL = DEEPL_API_URL;
			}
			else {
				$this->endPointURL = trailingslashit( DEEPL_API_URL ) . $this->endPoint;
			}
		}

		return $this->endPointURL;
	}
	/*
	* send a POST request
	*
	* @since 0.1
	*/
	public function doPOSTRequest( $args	= array() ) {
		$this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
//			$this->headers['Content-Length'] = strlen( $this->request['text'] );
		$args = $this->buildQuery( 'POST' );

		//plouf( $args, "ARGS POSTS" ); 		plouf( $this );		//die( 'ok izpepiezrir' );

		$response = wp_remote_post( $this->getEndPointURL(), $args );

		if ( is_wp_error( $response ) ) {
			$this->response = $response->get_error_message();
				plouf( $args, $this->getEndPointURL() );
				die( 'do post requestijezpoirer' );
		} else {
			$this->response = $response;
		}
		return $response;
	}

	/*
	* send a GET request
	*
	* @since 0.1
	*/
	public function doGETRequest() {
		$args = $this->buildQuery( 'GET' );
		$url = $this->getEndPointURL() . '?' . $args['query'];
		unset( $args['query'] );

		//plouf( $args, "ARGS GET" ); die( 'ok' );
		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			$this->response = $response->get_error_message();
		} else {
			$this->response = $response;
		}
		return $response;
	}
}