<?php

class ConverterDocument {
	protected $DOM;
	protected static $CONVERT = array(
		'ignore-libxml-errors-elements' => array('poem', 'ref', 'references', 'nowiki'),
	);

	public function __construct($file) {
		$this->DOM = $this->convertWikiToXML($file);
	}

	protected function convertWikiToXML($file) {
		libxml_use_internal_errors(true);

		$DOM = new DOMDocument();
		$DOM->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$file.'</body></html>');

		foreach(libxml_get_errors() as $error) {
			foreach(self::$CONVERT['ignore-libxml-errors-elements'] as $libxmlIgnoreElement) {
				if(trim($error->message) == 'Tag '.$libxmlIgnoreElement.' invalid') {
					continue 2;
				}
			}

			common_logNotice('Error'.($error->level==2?'':' of level '.$error->level).': '.trim($error->message).' in line '.$error->line.' and column '.$error->column);
		}
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		$DOM->encoding = 'UTF-8';

		return $DOM;
	}

	public function getDOMDocument() {
		return $this->DOM;
	}
}

class Converter {
	protected static $CONVERT = array(
		'ignore-libxml-errors-elements' => array('poem', 'ref', 'references', 'nowiki'),
	);
	protected static $replacePairs = array();

	static public function removeUTF8BOM($file) {
		if(substr($file, 0, 3) == chr(0xEF).chr(0xBB).chr(0xBF)) {
			$file = substr($file, 3);
		}

		return $file;
	}

	static public function normaliseWikiText($file) {
		$file = self::removeUTF8BOM($file);
		$file = str_replace('  ', ' ', $file);
		$file = str_replace("\r\n", "\n", $file);
		$file = implode("\n", array_map('trim', explode("\n", $file)));
		$file = str_replace('</br>', '<br>', $file);
		$file = str_replace(' & ', ' &amp; ', $file);

		return $file;
	}

	static public function convertWikiToDOMDocument($file) {
		libxml_use_internal_errors(true);

		$DOM = new DOMDocument();
		$DOM->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'.$file.'</body></html>');

		foreach(libxml_get_errors() as $error) {
			foreach(self::$CONVERT['ignore-libxml-errors-elements'] as $libxmlIgnoreElement) {
				if(trim($error->message) == 'Tag '.$libxmlIgnoreElement.' invalid') {
					continue 2;
				}
			}

			common_logNotice('Error'.($error->level==2?'':' of level '.$error->level).': '.trim($error->message).' in line '.$error->line.' and column '.$error->column);
		}
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		$DOM->encoding = 'UTF-8';

		return $DOM;
	}

	static public function removeComments($DOM) {
		$xpath = new DOMXPath($DOM);
		$commentNodes = $xpath->evaluate('//comment()');
		foreach($commentNodes as $commentNode) {
			$commentNode->parentNode->removeChild($commentNode);
		}

		return $DOM;
	}

	static public function parseWikiTemplate($wikitext, $templateName, &$wikitextWithoutTemplate=null) {
		$templateNameFirstLetter = mb_substr($templateName, 0, 1);
		$file = preg_split('/{{\s*['.mb_strtoupper($templateNameFirstLetter).mb_strtolower($templateNameFirstLetter).']'.mb_substr($templateName, 1).'([\s|}])(.*)}}/us', $wikitext, 4, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_OFFSET_CAPTURE);
		if(count($file) < 3) {return array();}
		$text = $file[1][0].$file[2][0];
		if(trim($text) === '') {return array();}
		$depth = 1;
		$parameters = array(null);
		$newParameterName = null;
		$newParameterValue = null;
		$saveType = 0;
		for($i = 0; ($i < strlen($text)) && ($depth > 0); $i++) {
			if(isset($text[$i+1])) {
				if($text[$i].$text[$i+1] == '{{') {
					$depth++;
				} elseif($text[$i].$text[$i+1] == '}}') {
					$depth--;
					if($depth == 0) {$i++;}
				}
			}
			if(($depth == 1) && ($text[$i] == '|')) {
				if($saveType > 0) {$saveParameter = true;}
				$saveType = 1;
			} elseif(($depth == 1) && ($text[$i] == '=') && ($saveType == 1)) {
				$saveType = 2;
			} elseif(($depth > 0) && ($saveType == 1)) {
				if(is_null($newParameterName)) {$newParameterName = '';}
				$newParameterName .= $text[$i];
			} elseif(($depth > 0) && ($saveType == 2)) {
				if(is_null($newParameterValue)) {$newParameterValue = '';}
				$newParameterValue .= $text[$i];
			}
			if(!empty($saveParameter) || ($depth == 0) || ($i == strlen($text)-1)) {
				if(is_null($newParameterValue)) {
					$parameters[] = trim($newParameterName);
				} else {
					$parameters[trim(str_replace('_', ' ', $newParameterName))] = trim($newParameterValue);
				}
				$newParameterName = null;
				$newParameterValue = null;
				$saveParameter = false;
				if($i == strlen($text)-1) {$i += 2;}
			}
		}
		if(is_null($parameters[0])) {unset($parameters[0]);}

		$wikitextWithoutTemplate = $file[0][0].substr($wikitext, $file[1][1]+$i);

		return $parameters;
	}

	static public function getPagesFromString($string) {
		$pages = array();

		$pageIntervals = explode(',', $string);
		foreach($pageIntervals as $pageInterval) {
			if(strstr($pageInterval, '-')) {
				$pageInterval = explode('-', $pageInterval);
				$pageRange = range($pageInterval[0], $pageInterval[1]);
				foreach($pageRange as $page) {
					$pages[] = $page;
				}
			} else {
				$pages[] = (int) $pageInterval;
			}
		}

		return $pages;
	}

	static public function trimDOMElement($DOMElement, $alsoLB=true) {
		$removedElements = array();

		for($i = 0; $i < $DOMElement->childNodes->length; $i++) {
			$DOMElementChild = $DOMElement->childNodes->item($i);
			if((($DOMElementChild->nodeName == "#text") && (trim($DOMElementChild->nodeValue) === "")) || (($alsoLB && $DOMElementChild->nodeName == "lb")) || (in_array($DOMElementChild->nodeName, array("p","hi","l","TEI_LG_BREAK")) && ($DOMElementChild->childNodes->length == 0))) {
				if(!isset($removedElements[$DOMElementChild->nodeName])) {$removedElements[$DOMElementChild->nodeName] = 0;}
				$removedElements[$DOMElementChild->nodeName]++;
				$DOMElement->removeChild($DOMElementChild);
				$i--;
			} else {break;}
		}
		for($i = $DOMElement->childNodes->length-1; $i >= 0; $i--) {
			$DOMElementChild = $DOMElement->childNodes->item($i);
			if((($DOMElementChild->nodeName == "#text") && (trim($DOMElementChild->nodeValue) === "")) || (($alsoLB && $DOMElementChild->nodeName == "lb")) || (in_array($DOMElementChild->nodeName, array("p","hi","l","TEI_LG_BREAK")) && ($DOMElementChild->childNodes->length == 0))) {
				if(!isset($removedElements[$DOMElementChild->nodeName])) {$removedElements[$DOMElementChild->nodeName] = 0;}
				$removedElements[$DOMElementChild->nodeName]++;
				$DOMElement->removeChild($DOMElementChild);
			} else {break;}
		}

		return $removedElements;
	}

	static public function validateByRelaxNG($DOM, $scheme) {
		libxml_use_internal_errors(true);
		$DOM->relaxNGValidate($scheme);
		foreach(libxml_get_errors() as $error) {
			common_logNotice('Error RelaxNG'.($error->level==2?'':' level '.$error->level).': '.trim($error->message).' in line '.$error->line.' and column '.$error->column);
		}
		libxml_clear_errors();
		libxml_use_internal_errors(false);
	}

	static public function checkUnconvertedSyntax($TEIfile) {
		if(strstr($TEIfile, '{{')) {common_logNotice('Contains {{');}
		if(strstr($TEIfile, '}}')) {common_logNotice('Contains }}');}
		if(strstr($TEIfile, '&lt;')) {common_logNotice('Contains &lt;');}
		if(strstr($TEIfile, '==')) {common_logNotice('Contains ==');}
		if(strstr($TEIfile, "''")) {common_logNotice("Contains ''");}
		if(strstr($TEIfile, '::')) {common_logNotice('Contains ::');}
		if(stristr($TEIfile, 'align=')) {common_logNotice('Contains align=');}

		$fileBodyPart = explode('</teiHeader>', $TEIfile, 2);
		$fileBodyPart = $fileBodyPart[1];
		if(preg_match('/\S&amp;\S/', $fileBodyPart)) {common_logNotice('Contains &amp; without spaces');}
	}

	static public function getReplacePairs() {
		return self::$replacePairs;
	}

	static public function addReplacePair($string1, $string2) {
		self::$replacePairs[] = array($string1, $string2);
	}

	static public function parseTable($string) {
		$string = explode("\n", $string, 2);
		$string = stripslashes($string[1]);
		$string = str_replace('||', "\n|", $string);
		$string = str_replace('!!', "\n!", $string);
		$string = str_replace("\n|-\n|-\n", "\n|-\n", $string);
		$string = explode("\n|-\n", $string);
		$cellsNum = 0;
		foreach($string as $num1 => $row) {
			$row = "\n".$row;
			$row = preg_replace('/\n([\!\|])[ \t]*/', "\n$1 ", $row);
			$row = str_replace("\n! ", "\n| __TEI_STYLE_HEADING__ ", $row);
			$row = explode("\n|", $row);
			unset($row[0]);
			foreach($row as $num2 => $cell) {
				foreach(self::$replacePairs as $replace) {$cell = (is_string($replace[1])?preg_replace($replace[0], $replace[1], $cell):preg_replace_callback($replace[0], $replace[1], $cell));}
				$cellIsHeading = strstr($cell, '__TEI_STYLE_HEADING__');
				if(!strstr($cell, '{{') && strstr($cell, '|')) {$cell = explode('|', $cell, 2); $cell = $cell[1];}
				if($cellIsHeading) {$cell = trim(str_replace('__TEI_STYLE_HEADING__', '', $cell));}
				$row[$num2] = '<cell role="'.($cellIsHeading?'label':'data').'">___TEI_LEVEL_HIGHER____'.trim($cell).'___TEI_LEVEL_LOWER_____</cell>';
			}
			if(count($row) > $cellsNum) {$cellsNum = count($row);}
			$string[$num1] = '<row role="data">'.implode('', $row).'</row>';
		}
		$string = '<table rows="'.count($string).'" cols="'.$cellsNum.'">'.implode('', $string).'</table>';

		return $string;
	}

	static public function doBasicElementProcessing(&$node) {
		// process attributes
		if($node->getAttribute('align') == 'center') {
			self::markProperty($node, '___TEI_CENTERED_NEW____', '___TEI_CENTERED_END____');
			$node->removeAttribute('align');
		}
		if($node->getAttribute('align') == 'right') {
			self::markProperty($node, '___TEI_RIGHT_NEW_______', '___TEI_RIGHT_END_______');
			$node->removeAttribute('align');
		}
		if($node->attributes->length > 0) {
			common_logNotice('There are unprocessed attributes (element '.$node->tagName.')');
			foreach($node->attributes as $attr) {
				common_logNotice('  '.$attr->name.'="'.$attr->value.'", ');
			}
		}

		// process element
		switch($node->tagName) {
			case "p": self::markProperty($node, "___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___\n", "\n___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___"); break;
			case "div": self::markProperty($node, "___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___\n", "\n___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___"); break;
			case "poem": self::markProperty($node, "___TEI_POEM_NEW___________TEI_PARAGRAPH_NEW___\n", "\n___TEI_PARAGRAPH_END______TEI_POEM_END___________TEI_PARAGRAPH_NEW___"); break;
			case "blockquote": self::markProperty($node, "___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW______TEI_INDENT_NEW______", "___TEI_INDENT_END_________TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___"); break;
			case "center": self::markProperty($node, "___TEI_CENTERED_NEW____", "___TEI_CENTERED_END_______TEI_PARAGRAPH_NEW___"); break;
			case "b": self::markProperty($node, "___TEI_BOLD_NEW________", "___TEI_BOLD_END________"); break;
			case "i": self::markProperty($node, "___TEI_ITALIC_NEW______", "___TEI_ITALIC_END______"); break;
			case "small": self::markProperty($node, "___TEI_SMALL_NEW_______", "___TEI_SMALL_END_______"); break;
			case "big": self::markProperty($node, "___TEI_BIG_NEW_________", "___TEI_BIG_END_________"); break;
			default: common_logNotice('I cannot recognise element '.$node->tagName);
		}

		// move nodes out of the element
		while($node->childNodes->length) {
			$node->parentNode->insertBefore($node->firstChild, $node);
		}
		$node->parentNode->removeChild($node);
	}

	static protected function markProperty(&$node, $start, $end) {
		$node->insertBefore($node->ownerDocument->createTextNode($start), $node->firstChild);
		$node->appendChild($node->ownerDocument->createTextNode($end));
	}

	static public function generateHeadingID($string) {
		static $DATAheadings = array();

		$string = preg_replace('/___TEI_.{13}___/', '', $string);
		foreach(self::getReplacePairs() as $replace) {$string = preg_replace($replace[0], '$1', $string);}
		$string = strip_tags(htmlspecialchars_decode($string));
		$string = str_replace(' ', '_', trim($string));
		$string = urlencode($string);
		$string = str_replace('%', '.', $string);
		$string = mb_strimwidth($string, 0, 90, '...');

		$stringNew = $string;
		for($i = 2; in_array($stringNew, $DATAheadings); $i++) {$stringNew = $string.$i;}
		$DATAheadings[] = $stringNew;

		return $stringNew;
	}

	static public function parseImage($string, $imageFolder) {
		global $settings;
		$wikitextImagePositions = array('right','left','center','thumb','border','frameless','frame','thumb','thumbnail','upright','none','baseline','sub','super','top','text-top','middle','bottom','text-bottom');

		$string = stripslashes($string);
		$string = explode('|', $string);
		$imageData = array('name' => $string[0], 'caption' => null);
		unset($string[0]);
		$imageCaption = end($string);
		if(!in_array($imageCaption, $wikitextImagePositions) && (substr($imageCaption, -2) != 'px')) {
			$imageData['caption'] = $imageCaption;
		}

		$imageDataFilename = $settings['cache-folder'].'/'.$imageData['name'].'.dat';
		if(file_exists($imageDataFilename)) {
			$imageURL = file_get_contents($imageDataFilename);
		} else {
			$MWimageData = unserialize(file_get_contents('http://'.$settings['wiki-domain'].'/w/api.php?action=query&titles=File:'.urlencode($imageData['name']).'&prop=imageinfo&iiprop=url&format=php', null, stream_context_create(array('http' => array('header' => 'User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; WIKI2TEI)')))));
			if(isset($MWimageData['query']['pages'])) {
				$MWimageDataPages = array_values($MWimageData['query']['pages']);
				if(isset($MWimageDataPages[0]['imageinfo'][0]['url'])) {
					$imageURL = $MWimageDataPages[0]['imageinfo'][0]['url'];
					if($settings['cache-enabled']) {
						mkdirine($settings['cache-folder']);
						file_put_contents($imageDataFilename, $imageURL);
					}
				}
			}
		}
		if(empty($imageURL)) {
			common_logNotice('Could not find source of the image '.$imageData['name']);
		}

		if($settings['save-images']) {
			$imageFolder = rtrim($imageFolder, '/');
			$imageFolderLocal = $settings['facsimile-folder'].'/'.$imageFolder;
			$imageName = pathinfo($imageURL, PATHINFO_BASENAME);
			if(!file_exists($imageFolderLocal.'/'.$imageName)) {
				mkdirine($imageFolderLocal);
				file_put_contents($imageFolderLocal.'/'.$imageName, file_get_contents($imageURL, null, stream_context_create(array('http' => array('header' => 'User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; WIKI2TEI)')))));
			}
			$imageNewURL = $settings['facsimile-url-prefix'].$imageFolder.'/'.$imageName;
		} else {
			$imageNewURL = $imageURL;
		}

		$string = '<figure>'.(!is_null($imageData['caption'])?'<FIGURE_HEAD_TEI>'.htmlspecialchars($imageData['caption']).'</FIGURE_HEAD_TEI>':'').'<graphic url="'.htmlspecialchars($imageNewURL).'" /></figure>';
		return $string;
	}

	static public function populateTaxonomyCategories($element, $categories) {
		$DOM = $element->ownerDocument;
		foreach($categories as $category) {
			$categoryDOM = $DOM->createElement('category');
			$categoryDOM->setAttribute('xml:id', $category['id']);

			$catDescDOM = $DOM->createElement('catDesc');
			$categoryDescNumber = 0;
			foreach($category['desc'] as $categoryDescLanguage => $categoryDescTranslation) {
				$termDOM = $DOM->createElement('term', $categoryDescTranslation);
				$termDOM->setAttribute('xml:lang', $categoryDescLanguage);
				$catDescDOM->appendChild($termDOM);

				$categoryDescNumber++;
				if($categoryDescNumber < count($category['desc'])) {
					$catDescDOM->appendChild($DOM->createTextNode(' / '));
				}
			}
			$categoryDOM->appendChild($catDescDOM);

			if(!empty($category['categories'])) {
				$subcategoriesDOM = self::populateTaxonomyCategories($categoryDOM, $category['categories']);
			}

			$element->appendChild($categoryDOM);
		}
	}

	static public function addTaxonomyCategories($DOM, array $categories) {
		$DOMXPath = new DOMXPath($DOM);
		$categoriesDOM = $DOMXPath->query('/TEI/teiHeader/profileDesc/textClass')->item(0);
		foreach($categories as $category) {
			$categoryDOM = $DOM->createElement('catRef');
			$categoryDOM->setAttribute('target', '#'.$category);
			$categoriesDOM->appendChild($categoryDOM);
			
			if(is_null($DOM->getElementById($category))) {
				common_logNotice('Taxonomy category "'.$category.'" does not have definition');
			}
		}
	}
}
