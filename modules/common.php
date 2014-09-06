<?php

ini_set('pcre.backtrack_limit', 5000000);

date_default_timezone_set($settings['timezone']);
mb_internal_encoding('UTF-8');

function common_error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
	common_logNotice('PHP error '.$errno.': '.$errstr.' in '.$errfile.' on line '.$errline, false);
	return true;
}
set_error_handler('common_error_handler');

function __autoload($className) {
	$classParts = explode('_', $className);
	$classPath = 'modules/'.$classParts[0].'.php';
	if(file_exists($classPath)) {
		require_once($classPath);
	}
}

function common_fetchContent($URL, $cache=null) {
	global $settings;

	$cachePath = $settings['cache-folder'].'/'.md5($URL);
	if(file_exists($cachePath) && (filemtime($cachePath) >= time()-$settings['cache-max-age'])) {
		$content = file_get_contents($cachePath);
	} else {
		$content = file_get_contents($URL, null, stream_context_create(array('http' => array('header' => 'User-Agent: User-Agent: Mozilla/4.0 (compatible; wiki2tei)'."\r\n"))));
		if(($content !== false) && ($cache || $settings['cache-enabled']) && ($cache !== false)) {
			common_saveFile($cachePath, $content);
		}
	}

	return $content;
}

function common_fetchContentFromWiki($query, $cache=null) {
	return common_fetchContent('http://'.Wiki::getDomain().'/w/'.$query, $cache);
}

function common_fetchPageFromWiki($pagetitle, $forceRefresh=false) {
	$file = common_fetchContentFromWiki('index.php?title='.urlencode($pagetitle).'&action=raw&'.($forceRefresh?time():''), ($forceRefresh?false:null));

	if(is_string($file)) {
		$file = trim($file);

		// follow redirect
		$redirectMagicWords = Wiki::getRedirectMagicWords();
		foreach($redirectMagicWords as $redirectMagicword) {
			preg_match('/^'.preg_quote($redirectMagicword, '/').'\s*\[\[(.*?)\]\]/im', $file, $matches);
			if(isset($matches[1])) {
				return common_fetchPageFromWiki($matches[1]);
			}
		}
	}

	return $file;
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
		file_put_contents($settings['notices-filename'], ($message !== ''?date('[Y-m-d H:i:s] '):'').$message.PHP_EOL, ($append?FILE_APPEND:null));
	} elseif($settings['notices-output'] == 'silent') {
	} elseif($error || !$settings['notices-print-only-errors']) {
		print htmlspecialchars($message).'<br />'."\n";
	}
}

function language_getDocumentLanguage($file, $currentLanguage) {
	switch($currentLanguage) {
		case 'sl': return ((substr_count($file, 'ſ')/mb_strlen($file)) > 0.01?'sl-bohoric':'sl'); break;
		default: return $currentLanguage;
	}
}

function metadata_getTaxonomyByMetadataCategories($taxonomyCategories, $taxonomyParentName=false) {
	$taxonomyByMetadataCategories = array();
	foreach($taxonomyCategories as $taxonomyCategory) {
		if(!empty($taxonomyCategory['categories'])) {
			$newTaxonomyByMetadataCategories = metadata_getTaxonomyByMetadataCategories($taxonomyCategory['categories'], (!empty($taxonomyParentName)?$taxonomyParentName:$taxonomyCategory['id']));
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

function metadata_getParentTaxonomyByTaxonomy($taxonomyCategories, $taxonomyParentName=false) {
	$parentTaxonomyByTaxonomy = array();
	$taxonomyByMetadataCategories = array();
	foreach($taxonomyCategories as $taxonomyCategory) {
		if(!empty($taxonomyCategory['categories'])) {
			$taxonomyByMetadataCategories = array_merge($taxonomyByMetadataCategories, metadata_getParentTaxonomyByTaxonomy($taxonomyCategory['categories'], (!empty($taxonomyParentName)?$taxonomyParentName:$taxonomyCategory['id'])));
		} elseif(!empty($taxonomyParentName)) {
			$taxonomyByMetadataCategories[$taxonomyCategory['id']] = $taxonomyParentName;
		}
	}
	return $taxonomyByMetadataCategories;
}

function metadata_extractValuesOfMatchingTemplates($wikitext, $templates) {
	$values = array();
	foreach($templates as $template) {
		$wikitextTemp = $wikitext;
		do {
			$templateParsedParameters = Converter::parseWikiTemplate($wikitextTemp, $template['template'], $wikitextTemp);
			if(isset($template['parameters'])) {
				foreach($template['parameters'] as $templateRequiredParameterName => $templateRequiredParameterValue) {
					if(!isset($templateParsedParameters[$templateRequiredParameterName]) || !in_array($templateParsedParameters[$templateRequiredParameterName], (array) $templateRequiredParameterValue)) {
						continue 2;
					}
				}
			}
			if(isset($templateParsedParameters[$template['metadata-parameter']])) {
				$values[] = $templateParsedParameters[$template['metadata-parameter']];
			}
		} while(!empty($templateParsedParameters));
	}

	return $values;
}
