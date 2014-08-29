<?php

function wiki_getAuthorFullName($author) {
	global $settings;

	$file = @common_fetchPageFromWiki($author);
	if($file === false) {
		return $author;
	}

	$templateAvtor = Converter::parseWikiTemplate($file, 'avtor');
	if(!empty($templateAvtor['ime']) && !empty($templateAvtor['priimek'])) {
		return $templateAvtor['priimek'].', '.$templateAvtor['ime'];
	} else {
		return $author;
	}
}
