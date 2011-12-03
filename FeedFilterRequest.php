<?php

require_once('FeedFilter.php');

class FeedFilterRequest
{
	protected $debug = false;
	protected $url = '';
	protected $filter;
	
	public function __construct($url)
	{
		$this->url = $url;
	}

	public function setFilter(FeedFilter $filter)
	{
		$this->filter = $filter;
	}

	public function handleRequest()
	{
		$cacheFile = '/tmp/feedfilter_' . md5($this->url) . '.tmp';

		$allowCaching = true;

		if (!empty($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache') {
			$allowCaching = false;
		}

		if (!empty($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') {
			$allowCaching = false;
		}

		if (file_exists($cacheFile)) {
			$cacheData = unserialize(file_get_contents($cacheFile));
		} else {
			$cacheData = false;
		}

		$request = new HttpRequest($this->url);

		if ($allowCaching && isset($cacheData['etag'])) {
			$request->addHeaders(array('If-None-Match' => $cacheData['etag']));
		}

		$response = $request->send();

		if ($response->getResponseCode() == 304) {
			$this->debugInfo('got 304 from remote, sending from cache');

			if (isset($cacheData['content-type'])) {
				header('Content-type: ' . $cacheData['content-type']);
			}

			echo $this->getBody($cacheData['body']);
			return;
		}

		if ($response->getResponseCode() == 200) {
			if ($etag = $response->getHeader('Etag')) {
				$cacheData = array();
				$cacheData['etag'] = $etag;

				if ($ct = $response->getHeader('Content-type')) {
					$cacheData['content-type'] = $ct;
				}

				$cacheData['body'] = $response->getBody();

				header('Etag: ' . $etag);

				file_put_contents($cacheFile, serialize($cacheData));
			}

			if ($ct = $response->getHeader('Content-type')) {
				header('Content-type: ' . $ct);
			}
			
			$this->debugInfo('got 200 from remote, passing thru');

			echo $this->getBody($response->getBody());
			return;
		}

		header('HTTP/1.1 500 Error');
		header('Content-type: text/plain');

		echo 'Got status code ' . $response->getResponseCode() . ' from remote server.';
	}

	protected function getBody($body)
	{
		if ($this->filter) {
			$this->filter->setContent($body);
			return $this->filter->filter();
		}

		return $body;
	}

	protected function debugInfo($msg)
	{
		if (!$this->debug) {
			return;
		}

		header('X-Debug-Info: ' . $msg);
		error_log('Debug-Info: ' . $msg);
	}

}

