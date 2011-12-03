<?php

require_once('FeedFilterRequest.php');

/* CHANGEME: your rss 2.0 feed */
$url = 'http://www.example.com/your-rss-feed-here.rss';

$filter = new FeedFilter;

/* CHANGEME: whitelist filter.
 * list of regexes applied to title and description */
$filter->setFilters(array(
	'@köln@i',
	'@cologne@i',
));

$ffr = new FeedFilterRequest($url);
$ffr->setFilter($filter);
$ffr->handleRequest();

