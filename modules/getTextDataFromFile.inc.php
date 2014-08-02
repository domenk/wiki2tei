<?php

if(!empty($_GET['id'])) {

	include('data.php');

	$selectedWorkID = (int) $_GET['id'];

	if(!empty($works[$selectedWorkID])) {

		$selectedWorkData = $works[$selectedWorkID];
		$selectedWork = new Work($selectedWorkID);

		$selectedWork->setTitle($selectedWorkData['naslov']);
		$selectedWork->addAuthors($selectedWorkData['avtor']);
		$selectedWork->addYears($selectedWorkData['leto']);
		$selectedWork->setLink($selectedWorkData['povezava']);
		$selectedWork->setDjvu(!empty($selectedWorkData['djvu']));
		$selectedWork->setFacsimile(empty($selectedWorkData['ni-faksimila']));
		if(!empty($selectedWorkData['opomba'])) {$selectedWork->setNote($selectedWorkData['opomba']);}

		$selectedWorkFilename = $settings['wikitext-folder'].'/'.$selectedWork->getID().'.txt';
		if(!file_exists($selectedWorkFilename) && !$selectedWork->isDjvu() && empty($selectedWorkData['sestavljeno'])) {
			$file = common_fetchContentFromWiki('index.php?title='.$selectedWork->getLink().'&action=raw&'.time());
			mkdirine($settings['wikitext-folder']);
			file_put_contents($selectedWorkFilename, $file);
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
