<?php
/**
 * @package framework
 * @subpackage http
 */

namespace SilverStripe\Framework\Http;

/**
 * Represents a response returned by a controller.
 *
 * @package framework
 * @subpackage http
 */
class Response extends Message {
	
	/**
	 * @var array
	 */
	protected static $status_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Request Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
	);

	/**
	 * @var Int
	 */
	protected $statusCode = 200;
	
	/**
	 * @var String
	 */
	protected $statusDescription = "OK";

	protected $headers = array(
		"Content-Type" => "text/html; charset=utf-8",
	);

	/**
	 * Create a new HTTP response
	 * 
	 * @param $body The body of the response
	 * @param $statusCode The numeric status code - 200, 404, etc
	 * @param $statusDescription The text to be given alongside the status code. 
	 *  See {@link setStatusCode()} for more information.
	 */
	function __construct($body = null, $statusCode = null, $statusDescription = null) {
		$this->setBody($body);
		if($statusCode) $this->setStatusCode($statusCode, $statusDescription);
	}
	
	/**
	 * @param String $code
	 * @param String $description Optional. See {@link setStatusDescription()}.
	 *  No newlines are allowed in the description.
	 *  If omitted, will default to the standard HTTP description
	 *  for the given $code value (see {@link $status_codes}).
	 */
	function setStatusCode($code, $description = null) {
		if(isset(self::$status_codes[$code])) $this->statusCode = $code;
		else user_error("Unrecognised HTTP status code '$code'", E_USER_WARNING);
		
		if($description) $this->statusDescription = $description;
		else $this->statusDescription = self::$status_codes[$code];
	}
	
	/**
	 * The text to be given alongside the status code ("reason phrase").
	 * Caution: Will be overwritten by {@link setStatusCode()}.
	 * 
	 * @param String $description 
	 */
	function setStatusDescription($description) {
		$this->statusDescription = $description;
	}
	
	/**
	 * @return Int
	 */
	function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * @return string Description for a HTTP status code
	 */
	function getStatusDescription() {
		return str_replace(array("\r","\n"), '', $this->statusDescription);
	}
	
	/**
	 * Returns true if this HTTP response is in error
	 */
	function isError() {
		return $this->statusCode && ($this->statusCode < 200 || $this->statusCode > 399);
	}
	
	/**
	 * Returns true if this request or a status code corresponds to a redirect.
	 *
	 * @param int $code
	 * @return bool
	 */
	public function isRedirect($code = null) {
		return substr($code ?: $this->getStatusCode(), 0, 1) == '3';
	}

	function setBody($body) {
		parent::setBody($body);
		
		// Set content-length in bytes. Use mbstring to avoid problems with mb_internal_encoding() and mbstring.func_overload
		$this->setHeader('Content-Length', mb_strlen($body, '8bit'));
	}

	public function redirect($dest, $code = 302) {
		if(!$this->isRedirect($code)) {
			$code = 302;
		}

		$this->setStatusCode($code);
		$this->setHeader('Location', $dest);
	}

	/**
	 * Send this HTTPReponse to the browser
	 */
	function output() {
		if($this->isRedirect() && headers_sent($file, $line)) {
			$url = $this->headers['Location'];
			echo
			"<p>Redirecting to <a href=\"$url\" title=\"Please click this link if your browser does not redirect you\">$url... (output started on $file, line $line)</a></p>
			<meta http-equiv=\"refresh\" content=\"1; url=$url\" />
			<script type=\"text/javascript\">setTimeout('window.location.href = \"$url\"', 50);</script>";
		} else {
			if(!headers_sent()) {
				header($_SERVER['SERVER_PROTOCOL'] . " $this->statusCode " . $this->getStatusDescription());
				foreach($this->headers as $header => $value) {
					header("$header: $value", true, $this->statusCode);
				}
			}

			echo $this->body;
		}
	}
	
	/**
	 * Returns true if this response is "finished", that is, no more script execution should be done.
	 * Specifically, returns true if a redirect has already been requested
	 */
	function isFinished() {
		return in_array($this->statusCode, array(301, 302, 401, 403));
	}
	
}
