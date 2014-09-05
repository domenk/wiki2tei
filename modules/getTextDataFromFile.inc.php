<?php

if(!empty($_GET['id'])) {

	include('data.php');

	$selectedWorkID = (int) $_GET['id'];

	if(!empty($works[$selectedWorkID])) {

		$selectedWorkData = $works[$selectedWorkID];
		$selectedWork = new Work($selectedWorkID);

		$selectedWork->setTitle($selectedWorkData['title']);
		$selectedWork->addAuthors($selectedWorkData['author']);
		$selectedWork->addYears($selectedWorkData['year']);
		$selectedWork->setLink($selectedWorkData['link']);
		$selectedWork->setDjvu(!empty($selectedWorkData['djvu']));
		$selectedWork->setFacsimile(empty($selectedWorkData['no-facsimile']));
		if(!empty($selectedWorkData['opomba'])) {$selectedWork->setNote($selectedWorkData['note']);}

		$selectedWorkFilename = $settings['wikitext-folder'].'/'.$selectedWork->getID().'.txt';
		if(!file_exists($selectedWorkFilename) && !$selectedWork->isDjvu() && empty($selectedWorkData['manually-put-together'])) {
			$file = common_fetchPageFromWiki(urldecode($selectedWork->getLink()), true);
			common_saveFile($selectedWorkFilename, $file);
		} else {
			$file = file_get_contents($selectedWorkFilename);
		}

		// set data for other parts of the script
		if(!isset($settings['save-xml'])) {
			$settings['save-xml'] = true;
		}
		if(!isset($settings['save-images'])) {
			$settings['save-images'] = true;
		}

	}

}
