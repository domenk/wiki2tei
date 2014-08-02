<?php

function wiki_getAuthorFullName($author) {
	global $settings;

	$authorDataFilename = $settings['cache-folder'].'/'.md5($author).'.dat';
	if(file_exists($authorDataFilename)) {
		$file = file_get_contents($authorDataFilename);
	} else {
		$file = @common_fetchPageFromWiki($author);
		if($file === false) {
			return $author;
		}

		if($settings['cache-enabled']) {
			mkdirine($settings['cache-folder']);
			file_put_contents($authorDataFilename, $file);
		}
	}

	$templateAvtor = Converter::parseWikiTemplate($file, 'avtor');
	if(!empty($templateAvtor['ime']) && !empty($templateAvtor['priimek'])) {
		return $templateAvtor['priimek'].', '.$templateAvtor['ime'];
	} else {
		return $author;
	}
}
