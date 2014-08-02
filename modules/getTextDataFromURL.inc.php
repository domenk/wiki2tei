<?php

if(empty($selectedWork) && !empty($_GET['url'])) {
	$selectedWork = new Work(99999);
	$selectedWork->setLink(urlencode(trim($_GET['url'])));
}

if(!empty($selectedWork) && $selectedWork->hasLink()) {

	$file = @common_fetchPageFromWiki(urldecode($selectedWork->getLink()));
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
