<?php
/**
 * Instagram API Wrapper Class
 */

/**
 * Check for cURL and PHP / JSON dependencies. 
 * Ensure that the following "curl_init" and "json_decode" PHP functions are available to the application.
 */
if (!function_exists('curl_init')) {
	throw new Exception('Instagram needs the CURL PHP extension.');
}

if (!function_exists('json_decode')) {
	throw new Exception('Instagram needs the JSON PHP extension.');
}

/**
 * Thrown when an API call returns an exception.
 */
class InstagramApiException extends Exception
{
	/**
	 * The result from the API server that represents the exception information.
	 */
	protected $result;

	/**
	 * Make a new API Exception with the given result.
	 *
	 * @param array $result The result from the API server
	 */
	public function __construct($result) {
		$this->result = $result;

		$code = isset($result['error_code']) ? $result['error_code'] : 0;

		if (isset($result['error_description'])) {
			// OAuth 2.0 Draft 10 style
			$msg = $result['error_description'];
		} else if (isset($result['error']) && is_array($result['error'])) {
			// OAuth 2.0 Draft 00 style
			$msg = $result['error']['message'];
		} else if (isset($result['error_msg'])) {
			// Rest server style
			$msg = $result['error_msg'];
		} else {
		  	$msg = 'Unknown Error. Check getResult()';
		}

		parent::__construct($msg, $code);
	}

	/**
	 * Return the associated result object returned by the API server.
	 *
	 * @return ( mixed ) The result from the API server
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * Returns the associated type for the error. This will default to
	 * 'InstagramAPIException' when a type is not available.
	 *
	 * @return ( str )
	 */
	public function getType() {
		if (isset($this->result['error'])) {
			$error = $this->result['error'];
			
			if (is_string($error)) {
				// OAuth 2.0 Draft 10 style
				return $error;
			} else if (is_array($error)) {
				// OAuth 2.0 Draft 00 style
				if (isset($error['type'])) {
		  			return $error['type'];
				}
			}
		}

    	return 'InstagramAPIException';
	}

	/**
	 * To make debugging easier.
	 *
	 * @return string The string representation of the error
	 */
	public function __toString() {
		$str = $this->getType() . ': ';
		if ($this->code != 0) {
			$str .= $this->code . ': ';
		}
		return $str . $this->message;
	}
}

/**
 * Simple Instagram class used to make requests to the Instagram API
 */
class Instagram {
	/**
  	 * Version.
  	 */
  	const VERSION = '0.1';

	/**
	 * Default options for curl.
	 */
	public static $CURL_OPTS = array(
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 60,
		CURLOPT_USERAGENT      => 'instagram-php-0.1',
	);

	/**
   	 * The Client ID.
   	 *
   	 * @var string
   	 */
  	protected $clientId;

  	/**
   	 * The Client Secret.
   	 *
   	 * @var string
   	 */
  	protected $clientSecret;

  	/**
   	 * The user access token.
   	 *
   	 * @var string
   	 */
  	protected $accessToken = null;

  	/**
  	 *	The base API URL
  	 *
  	 *	@var string
  	 */
  	protected $baseApiUrl = "https://api.instagram.com/v1";

	/**
	 * Initialise an Instagram application
	 * 
	 * Configuration :
	 * - clientId : the application client ID
	 * - clientSecret ( optional ) : the application client secret
	 *
	 * @param ( mixed ) $config
	 * @return void
	 */
	public function __construct($config) {
		if (!session_id()) {
			session_start();
		}

		$this->setClientId($config['clientId']);

		if (isset($config['clientSecret'])) {
			$this->setClientSecret($config['clientSecret']);
		}
	}

	/**
	 * Set the Client ID.
	 *
	 * @param ( str ) $clientId The Client ID
	 * @return ( obj ) Instagram
	 */
	public function setClientId($clientId) {
		$this->clientId = $clientId;
		return $this;
	}

	/**
	 * Set the Client Secret.
	 *
	 * @param ( str ) $clientSecret The Client Secret
	 * @return ( obj ) Instagram
	 */
	public function setClientSecret($clientSecret) {
		$this->clientSecret = $clientSecret;
		return $this;
	}

	/**
	 * Set the access token.
	 *
	 * @param ( str ) $accessToken The Instagram Access Token
	 * @return ( obj ) Instagram
	 */
	public function setAccessToken($accessToken) {
		$this->accessToken = $accessToken;
		return $this;
	}

	/**
	 * Return a users Instagram ID from an access token
	 *
	 * @param ( str ) $accessToken The Instagram Access Token
	 * @return ( str ) $userId The Instagram user ID
	 */
	public function getUserId($accessToken) {
		$tokenParts = explode(".", $accessToken);

		if (!empty($tokenParts)) {
			return $tokenParts[0];	
		} else {
			return null;
		}
	}

	/**
	 * Build the API request parameters and parse the response.
	 *
	 * @param ( str ) $endPoint
	 * @param ( str ) $requestMethod
	 * @param ( mixed ) $params
	 * @return ( mixed ) $result
	 */
	public function api($endPoint, $requestMethod = 'get', $params = array()) {
		// build the request URL from the baseApiUrl and the endpoint passed to the function
		$url = $this->baseApiUrl . $endPoint;
		
		/**
		 * Merge the Access Token and / or Client ID into the request params.
		 * If the access token is set, then only use the access token.
		 * If the access token is NOT set, then only use the Client ID.
		 */ 
		if (!is_null($this->accessToken)) {
			$params = array_merge(
				array(
					'access_token' => $this->accessToken
				), 
				$params
			);
		} else {
			$params = array_merge(
				array(
					'client_id' => $this->clientId
				), 
				$params
			);
		}

		// make the request ad parse the json response 
		$result = json_decode($this->makeRequest($url, $requestMethod, $params), true);

		// throw an exception for any Instagram API errors
		if ($result['meta']['code'] !== 200) {
			$e = new InstagramApiException(
				array(
					'error_code' => $result['meta']['code'],
					'error' => array(
						'message' => $result['meta']['error_message'],
						'type' => $result['meta']['error_type'],
					),
				)
			);

			throw $e;
		}

		return $result;
	}

	/**
	 * Makes an HTTP request via cURL.
	 *
	 * @param ( str ) $url The URL the request should be made to
	 * @param ( str ) $requestMethod The request method to be used [ 'get' || 'post' ]
	 * @param ( mixed ) $params The parameters to use for the request body
 	 * @param ( CurlHandler ) $ch Initialized curl handle
	 *
	 * @return ( str ) The response text [ json ]
	 */
	protected function makeRequest($url, $requestMethod = 'get', $params = array(), $ch = null) {
		if (!$ch) {
			$ch = curl_init();
		}

		$opts = self::$CURL_OPTS;
		if ($requestMethod === 'get') {
			if(!empty($params)) {
				$url .=  '?' . http_build_query($params, null, '&');
			}
		} else if ($requestMethod === 'post') {
			$opts[CURLOPT_POST] = count($params);
			$opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
		}

		$opts[CURLOPT_URL] = $url;

		// disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
		// for 2 seconds if the server does not support this header.
		if (isset($opts[CURLOPT_HTTPHEADER])) {
			$existing_headers = $opts[CURLOPT_HTTPHEADER];
			$existing_headers[] = 'Expect:';
			$opts[CURLOPT_HTTPHEADER] = $existing_headers;
		} else {
			$opts[CURLOPT_HTTPHEADER] = array('Expect:');
		}

		curl_setopt_array($ch, $opts);
		$result = curl_exec($ch);

		if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
			$e = new InstagramApiException(
				array(
					'error_code' => curl_errno($ch),
					'error' => array(
						'message' => curl_error($ch),
						'type' => 'CurlException',
					),
				)
			);
			curl_close($ch);
			throw $e;
		}

		if ($result === false) {
			$e = new InstagramApiException(
				array(
					'error_code' => curl_errno($ch),
					'error' => array(
						'message' => curl_error($ch),
						'type' => 'CurlException',
					),
				)
			);
			curl_close($ch);
			throw $e;
		}

		curl_close($ch);

		return $result;
	}

}

?>