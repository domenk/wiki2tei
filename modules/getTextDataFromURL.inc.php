<?php

if(empty($selectedWork) && !empty($_GET['url'])) {
	$selectedWork = new Work(99999);
	$selectedWork->setLink(urlencode(trim($_GET['url'])));

	$settings['wiki-domain'] = $settings['wiki-default-domain'];
	$settings['wiki-url-prefix'] = str_replace(parse_url($settings['wiki-url-prefix'], PHP_URL_HOST), $settings['wiki-domain'], $settings['wiki-url-prefix']);
}

if(!empty($selectedWork) && $selectedWork->hasLink()) {

	$file = @common_fetchPageFromWiki(urldecode($selectedWork->getLink()), true);
	if($file === false) {
		exit('There is no page at the entered URL address.');
	}

	$selectedWork->setFacsimile(false);

	// set data for other parts of the script
	if(!isset($settings['save-xml'])) {
		$settings['save-xml'] = false;
	}
	if(!isset($settings['save-images'])) {
		$settings['save-images'] = false;
	}

}
