<?php

ini_set('pcre.backtrack_limit', 5000000);

date_default_timezone_set($settings['timezone']);
mb_internal_encoding('UTF-8');

if(!empty($settings['notices-filename'])) {
	function manual_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
		common_logNotice('PHP: error '.$errno.': '.$errstr.' in '.$errfile.' on line '.$errline, false);
		return false;
	}
	set_error_handler('manual_error_handler');
}

function common_fetchContentFromWiki($query, $cache=null) {
	global $settings;
	$URL = 'http://'.(isset($settings['wiki-domain'])?$settings['wiki-domain']:$settings['wiki-default-domain']).'/w/'.$query;
	$cachePath = $settings['cache-folder'].'/'.md5($URL);

	if(file_exists($cachePath) && (filemtime($cachePath) >= time()-$settings['cache-max-age'])) {
		$content = file_get_contents($cachePath);
	} else {
		$content = file_get_contents($URL, null, stream_context_create(array('http' => array('header' => 'User-Agent: User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; WIKI2TEI)'."\r\n"))));
		if(($content !== false) && ($cache || $settings['cache-enabled']) && ($cache !== false)) {
			common_saveFile($cachePath, $content);
		}
	}

	return $content;
}

function common_fetchPageFromWiki($pagetitle, $forceRefresh=false) {
	global $settings;

	$file = common_fetchContentFromWiki('index.php?title='.urlencode($pagetitle).'&action=raw&'.($forceRefresh?time():''), ($forceRefresh?false:null));

	if(is_string($file)) {
		$file = trim($file);

		// follow redirect
		foreach($settings['redirect-magicwords'] as $redirectMagicword) {
			preg_match('/^\#'.preg_quote($redirectMagicword, '/').'\s*\[\[(.*?)\]\]/im', $file, $matches);
			if(!empty($matches)) {
				return common_fetchPageFromWiki($matches[1]);
			}
		}
	}

	return $file;
}

define('WIKI_FALLBACK_SITENAME', 'Wikisource');
define('WIKI_FALLBACK_LANGUAGE', 'en');
define('WIKI_FALLBACK_DOMAIN', 'en.wikisource.org');
function common_fetchWikiSiteinfo() {
	$siteinfoJSON = common_fetchContentFromWiki('api.php?action=query&meta=siteinfo&siprop=general&format=json', true);
	if($siteinfoJSON === false) {return WIKI_GENERAL_SITENAME;}

	$siteinfo = json_decode($siteinfoJSON, true);

	return array(
		'sitename' => (!empty($siteinfo['query']['general']['sitename'])?$siteinfo['query']['general']['sitename']:WIKI_FALLBACK_SITENAME),
		'language' => (!empty($siteinfo['query']['general']['lang'])?$siteinfo['query']['general']['lang']:WIKI_FALLBACK_LANGUAGE),
	);
}

function common_fetchWikiPageCategoriesDeep($pagetitle, $knownCategories=array()) {
	$categories = $knownCategories;

	$pageCategoriesData = common_fetchContentFromWiki('api.php?action=query&prop=categories&titles='.urlencode($pagetitle).'&cllimit=100&clshow=!hidden&format=xml', true);
	if($pageCategoriesData !== false) {
		$DOM = new DOMDocument();
		$DOM->loadXML($pageCategoriesData);
		foreach($DOM->getElementsByTagName('cl') as $categoryDataDOM) {
			$newCategory = $categoryDataDOM->getAttribute('title');
			if(!in_array($newCategory, $categories)) {
				$categories[] = $newCategory;
				$newCategoryCategories = common_fetchWikiPageCategoriesDeep($newCategory, $categories);
				$categories = array_unique(array_merge($categories, $newCategoryCategories));
			}
		}
	}

	return $categories;
}

function common_getTaxonomyByMetadataCategories($taxonomyCategories, $taxonomyParentName=false) {
	$taxonomyByMetadataCategories = array();
	foreach($taxonomyCategories as $taxonomyCategory) {
		if(!empty($taxonomyCategory['categories'])) {
			$newTaxonomyByMetadataCategories = common_getTaxonomyByMetadataCategories($taxonomyCategory['categories'], $taxonomyCategory['id']);
			foreach($newTaxonomyByMetadataCategories as $newMetadataCategory => $newTaxonomyMetadataCategory) {
				foreach($newTaxonomyMetadataCategory as $metadataCategory) {
					$taxonomyByMetadataCategories[$newMetadataCategory][] = $metadataCategory;
				}
			}
		} elseif(!empty($taxonomyCategory['metadata-categories'])) {
			foreach($taxonomyCategory['metadata-categories'] as $metadataCategory) {
				$taxonomyByMetadataCategories[$metadataCategory[0]][] = array($taxonomyCategory['id'], $metadataCategory[1], $taxonomyParentName);
			}
		}
	}
	return $taxonomyByMetadataCategories;
}

function common_replaceSpecialCharacters($string) {
	$specialCharacters = array('\''=>'','č'=>'c','š'=>'s','ž'=>'z','ć'=>'c','đ'=>'d','¡'=>'!','¢'=>'c','£'=>'E','¥'=>'Y','§'=>'S','©'=>'C','ª'=>'a','®'=>'R','°'=>'o','±'=>'+-','²'=>'2','³'=>'3','µ'=>'u','¹'=>'1','º'=>'o','¿'=>'?','×'=>'x','ß'=>'ss','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ę'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','þ'=>'th','ÿ'=>'y','α'=>'alfa','β'=>'beta','γ'=>'gama','δ'=>'delta','ε'=>'epsilon','ζ'=>'zeta','η'=>'eta','θ'=>'theta','ι'=>'jota','κ'=>'kapa','λ'=>'lambda','μ'=>'mi','ν'=>'ni','ξ'=>'ksi','ο'=>'omikron','π'=>'pi','ρ'=>'ro','σ'=>'sigma','ς'=>'sigma','τ'=>'tau','υ'=>'ipsilon','φ'=>'fi','χ'=>'hi','ψ'=>'psi','ω'=>'omega');

	$string = mb_strtolower($string);
	$string = strtr($string, $specialCharacters);
	$string = preg_replace('/[^A-Z0-9]+/ui', '_', $string);
	$string = trim($string, '_');

	return $string;
}

function common_saveFile($filename, $content) {
	$dirname = pathinfo($filename, PATHINFO_DIRNAME);
	if(!file_exists($dirname)) {
		mkdir($dirname, 0774, true);
	}

	file_put_contents($filename, $content);
}

function common_logNotice($message, $error=true, $append=true) {
	global $settings;

	if($settings['notices-output'] == 'file') {
		file_put_contents($settings['notices-filename'], $message.PHP_EOL, ($append?FILE_APPEND:null));
	} elseif($settings['notices-output'] == 'silent') {
	} elseif($error || !$settings['notices-print-only-errors']) {
		print htmlspecialchars($message).'<br />'."\n";
	}
}

function language_getDocumentLanguage($file, $currentLanguage) {
	switch($currentLanguage) {
		case 'sl': return ((substr_count($file, 'Ĺż')/mb_strlen($file))>0.01?'sl-bohoric':'sl'); break;
		default: return $currentLanguage;
	}
}
