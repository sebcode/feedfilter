<?php

class FeedFilter
{
	protected $content = '';
	protected $filters = array();

	public function setContent($content)
	{
		$this->content = $content;
	}

	public function setFilters($filters)
	{
		$this->filters = $filters;
	}

	public function filter()
	{
		$d = new DOMDocument;
		$d->loadXML($this->content);

		$x = new DOMXPath($d);

		foreach ($x->query('/rss/channel/item') as $itemNode) {
			if (!(($nodes = $x->query('title', $itemNode)) && ($titleNode = $nodes->item(0)))) {
				continue;
			}

			if (!(($nodes = $x->query('description', $itemNode)) && ($descNode = $nodes->item(0)))) {
				continue;
			}

			if (!$this->filterMatch($titleNode->nodeValue)
				&& !$this->filterMatch($descNode->nodeValue)) {

				$itemNode->parentNode->removeChild($itemNode);
			}
		}

		return $d->saveXML();
	}

	protected function filterMatch($text)
	{
		foreach ($this->filters as $regex) {
			if (preg_match($regex, $text)) {
				return true;
			}
		}

		return false;
	}

}

