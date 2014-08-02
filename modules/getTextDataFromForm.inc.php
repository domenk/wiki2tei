<?php

if(!empty($_POST['url'])) {

	$url = trim($_POST['url']);

	common_logNotice('Converting URL '.$url.' submitted by form', false);

	$url = str_replace('https://', 'http://', $url);
	if(substr($url, 0, strlen($settings['wiki-url-prefix'])) != $settings['wiki-url-prefix']) {
		exit('URL address should start with "'.$settings['wiki-url-prefix'].'".');
	}
	$url = substr($url, strlen($settings['wiki-url-prefix']));
	if(strstr($url, '#') !== false) {
		$url = strstr($url, '#', true);
	}


	$selectedWork = new Work(99999);
	$selectedWork->setLink($url);


	$metadataFormFields = array('id', 'title', 'title-normalised', 'author', 'year', 'publisher', 'translator');
	foreach($metadataFormFields as $metadataFormField) {
		if(isset($_POST[$metadataFormField]) && (trim($_POST[$metadataFormField]) !== '')) {
			$metadataFormFieldValue = trim($_POST[$metadataFormField]);

			if($metadataFormField == 'year') {
				$metadataFormFieldValue = array_map('trim', explode(',', $metadataFormFieldValue));
			}

			switch($metadataFormField) {
				case 'id': $selectedWork->setID($metadataFormFieldValue); break;
				case 'title': $selectedWork->setTitle($metadataFormFieldValue); break;
				case 'title-normalised': $selectedWork->setNormalisedTitle($metadataFormFieldValue); break;
				case 'author': $selectedWork->addAuthors($metadataFormFieldValue); break;
				case 'year': $selectedWork->addYears($metadataFormFieldValue); break;
				case 'publisher': $selectedWork->setPublisher($metadataFormFieldValue); break;
				case 'translator': $selectedWork->setTranslator($metadataFormFieldValue); break;
			}
		}
	}

	$metadataFormCategories = (!empty($_POST['categories'])&&is_array($_POST['categories'])?$_POST['categories']:array());
	$metadataFormCategories = array_map('trim', $metadataFormCategories);
	foreach($metadataFormCategories as $metadataFormCategory) {
		if(!empty($metadataFormCategory)) {
			$selectedWork->addCategories($metadataFormCategory);
		}
	}

	require('modules/getTextDataFromURL.inc.php');

}
