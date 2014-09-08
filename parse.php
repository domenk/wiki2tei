<?php

require('settings.inc.php');
require('modules/common.php');

common_logNotice('', false);

$selectedWork = array();

foreach($settings['text-data-modules'] as $textDataModuleFilenamePostfix) {
	require('modules/getTextData'.$textDataModuleFilenamePostfix.'.inc.php');
}

if(empty($selectedWork)) {
	exit('Could not find selected text.');
}


common_logNotice('Converting '.$selectedWork->getPrefix(), false);


// get missing metadata from wikitext
require('modules/getMetadataFromWikitext.inc.php');

$siteinfo = Wiki::fetchSiteinfo();


Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($settings['unclear-template']).'\s*}}/sU', '<gap reason="illegible" />');
Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($settings['unclear-template']).'\s*\|([^\|]*)}}/sU', '<unclear>$1</unclear>');
Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($settings['unclear-template']).'\s*\|([^\|]*)\|([^\|]*)}}/sU', '<choice><unclear>$1</unclear><corr>$2</corr></choice>');
Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($settings['redaction-template']).'\s*\|([^\|]*)\|([^\|]*)}}/sU', '<choice><sic>$1</sic><corr>$2</corr></choice>');

// mark page breaks
if(!empty($settings['pagebreak-template'])) {
	Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($settings['pagebreak-template']).'(.*)}}/sU', '___TEI_PAGEBREAK_MANUAL___');
	Converter::addReplacePair('/\n___TEI_PAGEBREAK_MANUAL___\n/sU', "\n\n___TEI_PAGEBREAK_MANUAL___\n\n");
}

// replace text formatting templates
$textFormattingTemplates = array('spaced' => 'SPACED', 'gothic' => 'GOTHIC', 'cursive' => 'CURSIVE');
foreach($textFormattingTemplates as $textFormattingName => $textFormattingParserLabel) {
	if(!empty($settings[$textFormattingName.'-template'])) {
		Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($settings[$textFormattingName.'-template']).'\s*\|(.*)}}/sU', str_pad('___TEI_'.$textFormattingParserLabel.'_NEW___', 23, '_').'$1'.str_pad('___TEI_'.$textFormattingParserLabel.'_END___', 23, '_'));
	}
}

// remove unnecessary templates
foreach($settings['templates-to-remove'] as $templateNamePrefix) {
	Converter::addReplacePair('/{{\s*'.Wiki::getTemplateNamePattern($templateNamePrefix).'(.*)}}/sU', '');
}

// parse image tags
Converter::addReplacePair('/\[\[\s*('.implode('|', array_map('preg_quote', Wiki::getImageNamespaces())).'):(.*)\]\]/sUi', function($matches) {global $selectedWork; return Converter::parseImage($matches[2], $selectedWork->getPrefix().'/images');});

// mark other syntax
Converter::addReplacePair('/\[\[([^\|]*)\]\]/sU', '$1');
Converter::addReplacePair('/\[\[(.*)\|(.*)\]\]/sU', '$2');
Converter::addReplacePair('/__TOC__/sU', '');
Converter::addReplacePair('/__FORCETOC__/sU', '');
Converter::addReplacePair('/\n[\:]+\x20*(.*)/', '___TEI_INDENT_NEW______$1___TEI_INDENT_END______');
Converter::addReplacePair('/<\s*ins\s*>(.*)<\/ins\s*>/sUi', '<corr>$1</corr>');
Converter::addReplacePair('/<\s*s\s*>(.*)<\/s\s*>/sUi', '<hi rend="strike">$1</hi>');
Converter::addReplacePair('/<\s*u\s*>(.*)<\/u\s*>/sUi', '<hi rend="underline">$1</hi>');
Converter::addReplacePair('/<\s*sup\s*>(.*)<\/sup\s*>/sUi', '<hi rend="sup">$1</hi>');
Converter::addReplacePair('/<\s*sub\s*>(.*)<\/sub\s*>/sUi', '<hi rend="sub">$1</hi>');
Converter::addReplacePair('/\n{\|(.*)\n\|}\n/sU', function($matches) {return Converter::parseTable($matches[1]);});
Converter::addReplacePair('/\'\'\'(.*)\'\'\'/U', '___TEI_BOLD_NEW________$1___TEI_BOLD_END________');
Converter::addReplacePair('/\'\'(.*)\'\'/U', '___TEI_ITALIC_NEW______$1___TEI_ITALIC_END______');
Converter::addReplacePair('/\'\'\'(.*)$/mU', '___TEI_BOLD_NEW________$1___TEI_BOLD_END________');
Converter::addReplacePair('/\'\'(.*)$/mU', '___TEI_ITALIC_NEW______$1___TEI_ITALIC_END______');


$file = Converter::normaliseWikiText($file);

$file = preg_replace_callback('/<\s*nowiki.*?>(.*?)<\/nowiki\s*>/si', function($matches) {return '<nowiki>'.base64_encode($matches[1]).'</nowiki>';}, $file);
$file = preg_replace('/\[\[\s*('.implode('|', array_map('preg_quote', Wiki::getCategoryNamespaces())).'):(.*)\]\]/Ui', '', $file);

// some manual checks
if(strstr($file, '#')) {common_logNotice('Contains #');}

$converterDocument = new ConverterDocument($file);
$DOM = Converter::convertWikiToDOMDocument($file);
$DOM = Converter::removeComments($DOM);



// process elements ref and references
$documentRefs = array(1 => array(), 'editorial' => array());
$documentRefsCount = array(1 => 0, 'editorial' => 0);
$DOMXPath = new DOMXPath($DOM);
$referenceDOMs = $DOMXPath->query('//ref | //references');
for($a = 0; $a < $referenceDOMs->length; $a++) {
	$referenceDOM = $referenceDOMs->item($a);
	$referenceGroup = (trim($referenceDOM->getAttribute('group'))=='editorial'?'editorial':1);

	if($referenceDOM->nodeName == 'ref') {
		$referenceNumber = ++$documentRefsCount[$referenceGroup];
		$referenceID = 'ref'.$referenceNumber.($referenceGroup=='editorial'?'-editor':'');

		$noteDOM = $DOM->createElement('note');
		$noteDOM->setAttribute('xml:id', $referenceID);
		$noteDOM->setAttribute('type', ($referenceGroup=='editorial'?'editorial':'authorial'));
		$noteDOM->setAttribute('place', 'foot');
		$noteDOM->appendChild($DOM->createTextNode('___TEI_LEVEL_HIGHER____'));
		while($referenceDOM->childNodes->length) {
			if(($referenceDOM->firstChild->nodeName == '#text') && strstr($referenceDOM->firstChild->nodeValue, "\n")) {
				$nodeValue = explode("\n", $referenceDOM->firstChild->nodeValue);
				for($i = 0; $i < count($nodeValue); $i++) {
					if($i != 0) {$noteDOM->appendChild($DOM->createElement('lb'));}
					$noteDOM->appendChild($DOM->createTextNode($nodeValue[$i]));
				}
				$referenceDOM->removeChild($referenceDOM->firstChild);
			} else {
				$noteDOM->appendChild($referenceDOM->firstChild);
			}
		}
		$noteDOM->appendChild($DOM->createTextNode('___TEI_LEVEL_LOWER_____'));

		if(!$settings['tei-enable-anchor-references'] || $selectedWork->isDjvu()) {
			$refDOM->parentNode->replaceChild($noteDOM, $refDOM);
			$a--;
		} else {
			$documentRefs[$referenceGroup][] = $noteDOM;
			$referenceNewDOM = $DOM->createElement('ref');
			$referenceNewDOM->setAttribute('target', '#'.$referenceID);
			$referenceNewDOM->setAttribute('type', 'noteAnchor');
			$referenceNewDOM->appendChild($DOM->createTextNode('['.$referenceNumber.']'));
			$referenceDOM->parentNode->replaceChild($referenceNewDOM, $referenceDOM);
		}
	} elseif($referenceDOM->nodeName == 'references') {
		$pDOM = $DOM->createElement('p');
		$referenceDOM->parentNode->replaceChild($pDOM, $referenceDOM);
		foreach($documentRefs[$referenceGroup] as $noteDOM) {
			$pDOM->appendChild($noteDOM);
		}
		$documentRefs[$referenceGroup] = array();
	}
}

// check if we have any ref that has not been written
foreach($documentRefs as $documentRefGroup => $documentRefEntries) {
	if(!empty($documentRefEntries)) {
		common_logNotice('Some ref elements were not written (group '.$documentRefGroup.')');
	}
}

// element processing
$excludedElements = array('ref','note','lb','nowiki','sub','sup','ins','del','u','s');
$elementsForBasicProcessing = array('p','div','center','b','i','small','big','blockquote');
foreach($elementsForBasicProcessing as $elementForBasicProcessing) {
	while($DOM->getElementsByTagName($elementForBasicProcessing)->length) {
		$elementDOM = $DOM->getElementsByTagName($elementForBasicProcessing)->item(0);
		Converter::doBasicElementProcessing($elementDOM);
	}
}

while($DOM->getElementsByTagName('br')->length) {
	$brDOM = $DOM->getElementsByTagName('br')->item(0);
	$brDOM->parentNode->replaceChild($DOM->createTextNode('___TEI_LINE_BREAK______'), $brDOM);
}
while($DOM->getElementsByTagName('hr')->length) {
	$hrDOM = $DOM->getElementsByTagName('hr')->item(0);
	$hrDOM->parentNode->replaceChild($DOM->createTextNode('___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___---------___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___'), $hrDOM);
}
while($DOM->getElementsByTagName('poem')->length) {
	$poemDOM = $DOM->getElementsByTagName('poem')->item(0);
	for($i = 0; $i < $poemDOM->childNodes->length; $i++) {
		$poemDOMchild = $poemDOM->childNodes->item($i);
		if($poemDOMchild->nodeName == '#text') {
			$poemText = $poemDOMchild->nodeValue;
			$poemText = str_replace("\n\n", "___TEI_PARAGRAPH_END______TEI_PARAGRAPH_NEW___\n", $poemText);
			$poemText = str_replace("\n", "\n___TEI_STYLE_STOP______<TEI_LG_BREAK />___TEI_STYLE_RESTART___\n", $poemText);
			$poemDOM->replaceChild($DOM->createTextNode($poemText), $poemDOMchild);
		} elseif(!in_array($poemDOMchild->nodeName, $excludedElements)) { // !!! it does not parse if there are new lines in sub, sup, ins ...
			common_logNotice('Child of poem is some unexcluded element (element '.$poemDOMchild->nodeName.')');
		}
	}
	Converter::doBasicElementProcessing($poemDOM);
}

$allElements = $DOM->getElementsByTagName('body')->item(0)->getElementsByTagName('*');
$excludedElementsCount = 0;
foreach($excludedElements as $excludedElement) {$excludedElementsCount += $DOM->getElementsByTagName('body')->item(0)->getElementsByTagName($excludedElement)->length;}
if($allElements->length-$excludedElementsCount > 0) {
	common_logNotice('There are unprocessed elements');
	foreach($allElements as $elemDOM) {
		if(in_array($elemDOM->tagName, $excludedElements)) {continue;}
		common_logNotice($elemDOM->tagName.', ');
	}
}

$file = $DOM->saveXML($DOM->getElementsByTagName('body')->item(0));
$file = substr_replace($file, '', 0, 6); // remove <body>
$file = substr_replace($file, '', -7, 7); // remove </body>
$file = str_replace(htmlspecialchars('<TEI_LG_BREAK />'), '<TEI_LG_BREAK />', $file);
$file = str_replace(array('___TEI_INDENT_NEW______','___TEI_INDENT_END______'), array('<hi rend="indent">','</hi>'), $file);

$file = preg_replace_callback('/=====(.*)=====/', function($matches) {return '<div4 xml:id="wv-'.Converter::generateHeadingID($matches[1]).'"><head>'.trim(str_replace('___TEI_LINE_BREAK______', "\n", $matches[1])).'</head>';}, $file);
$file = preg_replace_callback('/====(.*)====/', function($matches) {return '<div3 xml:id="wv-'.Converter::generateHeadingID($matches[1]).'"><head>'.trim(str_replace('___TEI_LINE_BREAK______', "\n", $matches[1])).'</head>';}, $file);
$file = preg_replace_callback('/===(.*)===/', function($matches) {return '<div2 xml:id="wv-'.Converter::generateHeadingID($matches[1]).'"><head>'.trim(str_replace('___TEI_LINE_BREAK______', "\n", $matches[1])).'</head>';}, $file);
$file = preg_replace_callback('/==(.*)==/', function($matches) {return '<div1 xml:id="wv-'.Converter::generateHeadingID($matches[1]).'"><head>'.trim(str_replace('___TEI_LINE_BREAK______', "\n", $matches[1])).'</head>';}, $file);
$file = preg_replace('/<\/head>\s+/m', "</head>\n\n", $file);
$file = str_replace('<lb/>', "\n", $file);
$file = str_replace('___TEI_LINE_BREAK______', "\n\n", $file);
$file = str_replace("\n\n\n\n", "\n\n<TEI_BIGGAP_NONSTANDARD />\n\n", trim($file));
while(strstr($file, "\n\n\n")) {$file = str_replace("\n\n\n", "\n\n", $file);}


// process every page
if($selectedWork->isDjvu()) {
	$origFile = explode('___TEI_FACSIMILE_PAGE_', $file);
	unset($origFile[0]);
	foreach($origFile as $fileNum => $file) {$origFile[$fileNum] = '___TEI_FACSIMILE_PAGE_'.$file;}
} else {
	$origFile = array($file);
}

foreach($origFile as $fileNum => $file) {
	foreach(Converter::getReplacePairs() as $replace) {
		if(is_string($replace[1])) {
			$file = preg_replace($replace[0], $replace[1], "\n".trim($file)."\n");
		} else {
			$file = preg_replace_callback($replace[0], $replace[1], "\n".trim($file)."\n");
		}
	}
	$origFile[$fileNum] = $file;
}
$file = implode('', $origFile);


// process sections
$file = explode('<div1 ', $file);
foreach($file as $num1 => $chapter1) {
	if(strstr($file[$num1], '<div2 ')) {
		$file[$num1] = explode('<div2 ', $file[$num1]);
		foreach($file[$num1] as $num2 => $chapter2) {
			if(strstr($file[$num1][$num2], '<div3 ')) {
				$file[$num1][$num2] = explode('<div3 ', $file[$num1][$num2]);
				foreach($file[$num1][$num2] as $num3 => $chapter3) {
					if(strstr($file[$num1][$num2][$num3], '<div4 ')) {
						$file[$num1][$num2][$num3] = explode('<div4 ', $file[$num1][$num2][$num3]);
						foreach($file[$num1][$num2][$num3] as $num4 => $chapter4) {
							if($num4 == 0) {continue;}
							$file[$num1][$num2][$num3][$num4] .= '</div3>';
						}
						$file[$num1][$num2][$num3] = implode('<div4 ', $file[$num1][$num2][$num3]).'</div3>';
					} else {
						if($num3 == 0) {continue;}
						$file[$num1][$num2][$num3] .= '</div3>';
					}
				}
				$file[$num1][$num2] = implode('<div3 ', $file[$num1][$num2]).'</div2>';
			} else {
				if($num2 == 0) {continue;}
				$file[$num1][$num2] .= '</div2>';
			}
		}
		$file[$num1] = implode('<div2 ', $file[$num1]).'</div1>';
	} else {
		if($num1 == 0) {continue;}
		$file[$num1] .= '</div1>';
	}
}
$file = implode('<div1 ', $file);

$file = str_replace(array('<div1 ','<div2 ','<div3 ','<div4 '), '<div ', $file);
$file = str_replace(array('</div1>','</div2>','</div3>','</div4>'), '</div>', $file);

// parse text
$file = str_replace("\n\n", '___TEI_PARAGRAPH_NEW___', $file);
$file = preg_replace('/<head(.*)>/sU', '<head$1>___TEI_PARAGRAPH_NEW___', $file);
$file = preg_replace('/<div(.*)>/sU', '___TEI_PARAGRAPH_END___<div$1>', $file);
$file = preg_replace('/<p(.*)>/sU', '___TEI_PARAGRAPH_END___<p$1>___TEI_PARAGRAPH_NEW___', $file);
$file = preg_replace('/<lg(.*)>/sU', '___TEI_PARAGRAPH_END___<lg$1>___TEI_PARAGRAPH_NEW___', $file);
$file = str_replace('</head>', '___TEI_PARAGRAPH_END___</head>___TEI_PARAGRAPH_NEW___', $file);
$file = str_replace('</div>', '___TEI_PARAGRAPH_END___</div>___TEI_PARAGRAPH_NEW___', $file);
$file = str_replace('</p>', '___TEI_PARAGRAPH_END___</p>___TEI_PARAGRAPH_NEW___', $file);
$file = str_replace('</lg>', '___TEI_PARAGRAPH_END___</lg>___TEI_PARAGRAPH_NEW___', $file);
$file = str_replace('___TEI_LEVEL_HIGHER____', '___TEI_LEVEL_HIGHER_______TEI_PARAGRAPH_NEW___', $file);
$file = str_replace('___TEI_LEVEL_LOWER_____', '___TEI_PARAGRAPH_END______TEI_LEVEL_LOWER_____', $file);
$file = '___TEI_PARAGRAPH_NEW___'.trim($file).'___TEI_PARAGRAPH_END___';
while(strstr($file, '___TEI_PARAGRAPH_NEW______TEI_PARAGRAPH_END___')) {$file = str_replace('___TEI_PARAGRAPH_NEW______TEI_PARAGRAPH_END___', '___TEI_PARAGRAPH_END___', $file);}
while(strstr($file, '___TEI_PARAGRAPH_NEW______TEI_PARAGRAPH_NEW___')) {$file = str_replace('___TEI_PARAGRAPH_NEW______TEI_PARAGRAPH_NEW___', '___TEI_PARAGRAPH_NEW___', $file);}
$file = str_replace("\n", '<lb />', $file);

$paraOpened = array(false);
$paraType = array(false);
$paraOpenTag = array('p' => '<p>', 'center' => '<p rend="center">', 'right' => '<p rend="align(right)">', 'poem' => '<lg>', 'l' => '<l>');
$paraCloseTag = array('p' => '</p>', 'center' => '</p>', 'right' => '</p>', 'poem' => '</lg>', 'l' => '</l>');
$poemOpened = array(false);
$hiOpenTag = array('I' => '<hi rend="italic">', 'B' => '<hi rend="bold">', 'GOT' => '<hi rend="gothic">', 'CUR' => '<hi rend="cursive">', 'IND' => '<hi rend="indent">', 'SPC' => '<hi rend="spaced">', 'SML' => '<hi rend="small">', 'BIG' => '<hi rend="large">');
$hiStartTEI = array('___TEI_INDENT_NEW______' => 'IND', '___TEI_BOLD_NEW________' => 'B', '___TEI_SPACED_NEW______' => 'SPC', '___TEI_SMALL_NEW_______' => 'SML', '___TEI_BIG_NEW_________' => 'BIG', '___TEI_ITALIC_NEW______' => 'I', '___TEI_GOTHIC_NEW______' => 'GOT', '___TEI_CURSIVE_NEW_____' => 'CUR');
$hiEndTEI   = array('___TEI_INDENT_END______' => 'IND', '___TEI_BOLD_END________' => 'B', '___TEI_SPACED_END______' => 'SPC', '___TEI_SMALL_END_______' => 'SML', '___TEI_BIG_END_________' => 'BIG', '___TEI_ITALIC_END______' => 'I', '___TEI_GOTHIC_END______' => 'GOT', '___TEI_CURSIVE_END_____' => 'CUR');

$fileNew = '';

$Popened = array(false);
$CENopened = array(false);
$RIGHTopened = array(false);
$STYLEopened = array(array());
$parserLevel = 0;

$filestrlen = strlen($file);
for($i = 0; $i < $filestrlen; $i++) {
	if($file[$i] != '_') {
		$fileNew .= $file[$i];
		continue;
	}

	$substr = substr($file, $i, 23);

	$appendToNewFile = false;
	if($substr == '___TEI_PARAGRAPH_NEW___' || $substr == '___TEI_CENTERED_NEW____' || $substr == '___TEI_RIGHT_NEW_______') {
		$styleOpenTags = '';
		foreach($STYLEopened[$parserLevel] as $styleTag) {$styleOpenTags .= $hiOpenTag[$styleTag];}
		switch($substr) {
			case '___TEI_PARAGRAPH_NEW___': if(!empty($paraType[$parserLevel]) && ($paraType[$parserLevel] != 'p')) {$paraTypeNew = $paraType[$parserLevel];} else {$paraTypeNew = 'p';} if(!empty($CENopened[$parserLevel])) {$paraTypeNew = 'center';} elseif(!empty($RIGHTopened[$parserLevel])) {$paraTypeNew = 'right';} break;
			case '___TEI_CENTERED_NEW____': $paraTypeNew = 'center'; $CENopened[$parserLevel] = true; break;
			case '___TEI_RIGHT_NEW_______': $paraTypeNew = 'right'; $RIGHTopened[$parserLevel] = true; break;
		}
		$appendToNewFile = (!empty($paraOpened[$parserLevel])?str_repeat('</hi>', count($STYLEopened[$parserLevel])).$paraCloseTag[$paraType[$parserLevel]]:'').$paraOpenTag[$paraTypeNew].$styleOpenTags;
		$paraOpened[$parserLevel] = true;
		$paraType[$parserLevel] = $paraTypeNew;
	} elseif($substr == '___TEI_PARAGRAPH_END___' || $substr == '___TEI_CENTERED_END____' || $substr == '___TEI_RIGHT_END_______') {
		switch($substr) {
			case '___TEI_PARAGRAPH_END___': $paraTypeTemp = 'p'; break;
			case '___TEI_CENTERED_END____': $paraTypeTemp = 'center'; $paraType[$parserLevel] = false; $CENopened[$parserLevel] = false; break;
			case '___TEI_RIGHT_END_______': $paraTypeTemp = 'right'; $paraType[$parserLevel] = false; $RIGHTopened[$parserLevel] = false; break;
		}
		$appendToNewFile = (!empty($paraOpened[$parserLevel])?str_repeat('</hi>', count($STYLEopened[$parserLevel])).$paraCloseTag[$paraTypeTemp]:'');
		$paraOpened[$parserLevel] = false;
	} elseif($substr == '___TEI_POEM_NEW________') {
		$appendToNewFile = ($paraOpened[$parserLevel]?str_repeat('</hi>', count($STYLEopened[$parserLevel])).$paraCloseTag[$paraType[$parserLevel]]:'').$paraOpenTag['poem'];
		$paraType[$parserLevel] = 'p';
		$poemOpened[$parserLevel] = true;
		$paraOpened[$parserLevel] = false;
	} elseif($substr == '___TEI_POEM_END________') {
		$appendToNewFile = (!empty($paraOpened[$parserLevel])||!empty($poemOpened[$parserLevel])?$paraCloseTag['poem']:'');
		$paraType[$parserLevel] = false;
		$paraOpened[$parserLevel] = false;
		$poemOpened[$parserLevel] = false;
	} elseif($substr == '___TEI_STYLE_RESTART___') {
		$styleOpenTags = '';
		foreach($STYLEopened[$parserLevel] as $styleTag) {$styleOpenTags .= $hiOpenTag[$styleTag];}
		$appendToNewFile = $styleOpenTags;
	} elseif($substr == '___TEI_STYLE_STOP______') {
		$appendToNewFile = str_repeat('</hi>', count($STYLEopened[$parserLevel]));
	} elseif(in_array($substr, array_keys($hiStartTEI))) { // any style - start
		$HIcurrent = $hiStartTEI[$substr];
		$HIopened = array_search($HIcurrent, $STYLEopened[$parserLevel]);
		$appendToNewFile = ($HIopened!==false&&!empty($paraOpened[$parserLevel])?'':$hiOpenTag[$HIcurrent]);
		if($HIopened === false) {$STYLEopened[$parserLevel][] = $HIcurrent;}
	} elseif(in_array($substr, array_keys($hiEndTEI))) { // any style - end
		$HIcurrent = $hiEndTEI[$substr];
		$HIopened = array_search($HIcurrent, $STYLEopened[$parserLevel]);
		$appendToNewFile = ($HIopened!==false&&$paraOpened[$parserLevel]?'</hi>':'');
		if($HIopened !== false) {unset($STYLEopened[$parserLevel][$HIopened]);}
	} elseif($substr == '___TEI_LEVEL_HIGHER____') {
		$appendToNewFile = '';
		$parserLevel++;
		$Popened[$parserLevel] = false;
		$CENopened[$parserLevel] = false;
		$RIGHTopened[$parserLevel] = false;
		$STYLEopened[$parserLevel] = array();
	} elseif($substr == '___TEI_LEVEL_LOWER_____') {
		$appendToNewFile = '';
		$parserLevel--;
	} else {
		$fileNew .= $substr[0];
	}

	if($appendToNewFile !== false) {
		$fileNew .= $appendToNewFile;
		$i += 22;
	}
}

$file = $fileNew;

// replace ___TEI_FACSIMILE_PAGE_***___ with references to page facsimile
$documentHasManualPageBreaks = (strstr($file, '___TEI_PAGEBREAK_MANUAL___') !== false);
$file = str_replace('___TEI_PAGEBREAK_MANUAL___', '<pb />', $file);
$file = preg_replace('/___TEI_FACSIMILE_PAGE_(\d+)___/', '<pb page="$1" />', $file);


// it should read: <TEI xmlns="http://www.tei-c.org/ns/1.0" xml:lang="en"> (this is solved below)
$file = '<TEI xml:lang="'.$siteinfo['language'].'">
	<teiHeader>
		<fileDesc>
			<titleStmt>
				<title wiki2tei-metadata="title-rich">Author: Title of work. (9999) [Wikisource]</title>
				<principal></principal>
			</titleStmt>
			<editionStmt>
				<edition>1.0</edition>
			</editionStmt>
			<publicationStmt>
				<distributor>???</distributor>
				<idno wiki2tei-metadata="idno"></idno>
        <availability></availability>
				<date wiki2tei-metadata="dategenerated">9999-99-99</date>
			</publicationStmt>
			<sourceDesc>
				<bibl>
					<title xml:lang="'.$siteinfo['language'].'" type="orig" wiki2tei-metadata="title-original">Title of work</title>
					<title xml:lang="'.$siteinfo['language'].'" type="reg" wiki2tei-metadata="title-normalised">Title of work, normalised</title>
					<author wiki2tei-metadata="author">Author</author>
					<respStmt>
						<name wiki2tei-metadata="translator"></name>
					</respStmt>
					<date wiki2tei-metadata="date">9999</date>
					<publisher wiki2tei-metadata="publisher"></publisher>
					<pubPlace>
						'.$siteinfo['sitename'].': <ref wiki2tei-metadata="ref" target="http://en.wikisource.org/wiki/Text.djvu">http://en.wikisource.org/wiki/Text.djvu</ref>
					</pubPlace>
				</bibl>
			</sourceDesc>
		</fileDesc>
		<encodingDesc>
			<projectDesc></projectDesc>
			<appInfo>
				<application version="0.1" ident="wiki2tei">
					<label xml:lang="en">Converter from Wikisource MediaWiki format to TEI P5</label>
					<p xml:lang="en">Converter for the <ref target="http://www.wikisource.org/">Wikisource library</ref> into <ref target="http://www.tei-c.org/">TEI P5</ref>.</p>
					<p xml:lang="en">Source code can be found on <ref target="http://github.com/domenk/wiki2tei">Github</ref>.</p>
				</application>
			</appInfo>
			<editorialDecl>
				<p wiki2tei-metadata="note">Note</p>
			</editorialDecl>
			<tagsDecl>
				<namespace name="http://www.tei-c.org/ns/1.0"></namespace>
			</tagsDecl>
			<classDecl>
				<taxonomy xml:id="Text.taxonomy"></taxonomy>
			</classDecl>
		</encodingDesc>
		<profileDesc>
			<textClass></textClass>
		</profileDesc>
		<revisionDesc>
			<change>
				<date wiki2tei-metadata="dategenerated"></date>
				<name>wiki2tei</name>: conversion to TEI P5.
			</change>
		</revisionDesc>
	</teiHeader>
	<facsimile>
	</facsimile>
	<text>
		<front xml:lang="'.$siteinfo['language'].'">
			<titlePage>
				<titlePart type="reg" wiki2tei-metadata="title-normalised">Title of work</titlePart>
				<docAuthor wiki2tei-metadata="author">Author</docAuthor>
				<docDate wiki2tei-metadata="date-short">9999</docDate>
			</titlePage>
			<docImprint>
				<idno wiki2tei-metadata="idno">WIKI00999-9999</idno>
			</docImprint>
			<divGen type="teiHeader"/>
			<divGen type="toc-pages"/>
			<divGen type="toc"/>
		</front>
		<body>
'.$file.'
		</body>
	</text>
</TEI>';

$DOM = new DOMDocument();
$DOM->loadXML($file);
$DOM->encoding = 'UTF-8';

$headDOMs = $DOM->getElementsByTagName('head');
for($a = 0; $a < $headDOMs->length; $a++) {
	$headDOM = $headDOMs->item($a);
	Converter::trimDOMElement($headDOM);
	if(($headDOM->childNodes->length == 1) && $headDOM->firstChild->hasAttribute('rend')) {
		while($headDOM->firstChild->childNodes->length) {
			$headDOM->appendChild($headDOM->firstChild->childNodes->item(0));
		}
		$headDOM->setAttribute('rend', trim($headDOM->getAttribute('rend').' '.$headDOM->firstChild->getAttribute('rend')));
		$headDOM->removeChild($headDOM->firstChild);
	}
	for($i = 0; $i < $headDOM->childNodes->length; $i++) {
		if($headDOM->childNodes->item($i)->nodeName == 'p') {
			$headDOMpDOM = $headDOM->childNodes->item($i);
			while($headDOMpDOM->childNodes->length) {
				$headDOM->insertBefore($headDOMpDOM->childNodes->item(0), $headDOMpDOM);
			}
			$headDOM->replaceChild($DOM->createElement('lb'), $headDOMpDOM);
		}
	}

	// if there are multiple lb in head, keep only one
	for($i = 0; $i < $headDOM->childNodes->length; $i++) {
		if(in_array($headDOM->childNodes->item($i)->nodeName, array('lb','TEI_BIGGAP_NONSTANDARD')) && $headDOM->childNodes->item($i+1) && in_array($headDOM->childNodes->item($i+1)->nodeName, array('lb','TEI_BIGGAP_NONSTANDARD'))) {
			$headDOM->removeChild($headDOM->childNodes->item($i+1));
			$i--;
		}
	}
	Converter::trimDOMElement($headDOM);
}

// separate elements p in element lg - preprocessing
$DOMXPath = new DOMXPath($DOM);
$LGDOMs = $DOMXPath->query('//lg');
foreach($LGDOMs as $LGDOM) {
	Converter::trimDOMElement($LGDOM);
}

// separate elements p in element lg
$DOMXPath = new DOMXPath($DOM);
do {
	$LGDOMs = $DOMXPath->query('//lg[count(p)=count(*)]');

	$changesWereMade = false;

	foreach($LGDOMs as $LGDOM) {
		Converter::trimDOMElement($LGDOM);

		foreach($LGDOM->childNodes as $LGDOMchild) {
			if(($LGDOMchild->nodeName != 'p') && (($LGDOMchild->nodeName != '#text') || (trim($LGDOMchild->nodeValue) !== ''))) {
				continue 2;
			}
		}

		$changesWereMade = true;

		$newLGDOMs = $DOM->createDocumentFragment();
		$LGDOMchildren = $LGDOM->childNodes;
		for($i = 0; $i < $LGDOMchildren->length; $i++) {
			$LGDOMchild = $LGDOMchildren->item($i);
			if($LGDOMchild->nodeName != 'p') {continue;}

			$newLG = $LGDOM->cloneNode();
			$newLGrend = trim($LGDOM->getAttribute('rend').' '.$LGDOMchild->getAttribute('rend'));
			if(!empty($newLGrend)) {$newLG->setAttribute('rend', $newLGrend);}

			while($LGDOMchild->childNodes->length) {
				$LGDOMchildFirstChild = $LGDOMchild->childNodes->item(0);
				if($LGDOMchildFirstChild->nodeName == 'lb') { // remove lb because we have TEI_LG_BREAK intead
					$LGDOMchild->removeChild($LGDOMchildFirstChild);
				}
				$newLG->appendChild($LGDOMchildFirstChild);
			}
			$newLGDOMs->appendChild($newLG);
		}
		$LGDOM->parentNode->replaceChild($newLGDOMs, $LGDOM);
	}
} while($changesWereMade);

// process content of element lg
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query('//lg');
foreach($PDOMs as $PDOM) {
	$PDOMnew = $PDOM->cloneNode();

	// move lines that are separated by TEI_LG_BREAK to l
	$newL = false;
	foreach($PDOM->childNodes as $PDOMchild) {
		if(empty($newL)) {
			$newL = $DOM->createElement('l');
			$PDOMnew->appendChild($newL);
		}

		if($PDOMchild->nodeName != 'TEI_LG_BREAK') {
			$newL->appendChild($PDOMchild->cloneNode(true));
		} else {
			$newL = false;
		}
	}

	for($i = 0; $i < $PDOMnew->childNodes->length; $i++) {
		$PDOMnewChild = $PDOMnew->childNodes->item($i);
		Converter::trimDOMElement($PDOMnewChild);
		if($PDOMnewChild->childNodes->length == 0) {
			$PDOMnew->removeChild($PDOMnewChild);
			$i--;
		}
	}

	$PDOM->parentNode->replaceChild($PDOMnew, $PDOM);
}

// if p are children of l, move content of each p to its own l
$DOMXPath = new DOMXPath($DOM);
do {
	$PDOMs = $DOMXPath->query('//l/p');
	if($PDOMs->length == 0) {break;}
	$PDOM = $PDOMs->item(0)->parentNode;

	$newLDOMs = array();
	$newLnum = 0;
	foreach($PDOM->childNodes as $PDOMchild) {
		if($PDOMchild->nodeName == 'p') {
			$newLnum++;
			$newLDOMs[$newLnum] = $DOM->createElement('l');
			if($PDOMchild->hasAttribute('rend')) {$newLDOMs[$newLnum]->setAttribute('rend', $PDOMchild->getAttribute('rend'));}
			while($PDOMchild->childNodes->length) {
				$newLDOMs[$newLnum]->appendChild($PDOMchild->firstChild);
			}
			$newLnum++;
		} else {
			if(!isset($newLDOMs[$newLnum])) {$newLDOMs[$newLnum] = $DOM->createElement('l');}
			$newLDOMs[$newLnum]->appendChild($PDOMchild->cloneNode(true));
		}
	}
	foreach($newLDOMs as $newL) {
		$PDOM->parentNode->insertBefore($newL, $PDOM);
	}
	$PDOM->parentNode->removeChild($PDOM);
} while($PDOMs->length);

// organise nested elements hi - preprocessing
$DOMXPath = new DOMXPath($DOM);
$HIDOMs = $DOMXPath->query('//hi');
foreach($HIDOMs as $HIDOM) {
	$HIDOMparent = $HIDOM->parentNode;
	if($HIDOMparent) {
		Converter::trimDOMElement($HIDOMparent);
		if($HIDOMparent->childNodes->length == 1) {
			$HIDOMparent->setAttribute('HASONLYHI', '1'); // performance
		}
	}
}

// organise nested elements hi
$DOMXPath = new DOMXPath($DOM);
do {
	$PDOMs = $DOMXPath->query('//*[@HASONLYHI]');
	foreach($PDOMs as $PDOM) {
		if(!$PDOM->firstChild) {continue;}

		$PDOM->setAttribute('rend', trim($PDOM->getAttribute('rend').' '.$PDOM->firstChild->getAttribute('rend')));
		while($PDOM->firstChild->childNodes->length) {
			$PDOM->appendChild($PDOM->firstChild->childNodes->item(0));
		}
		if(!$PDOM->firstChild->hasAttribute('HASONLYHI')) {
			$PDOM->removeAttribute('HASONLYHI');
		}
		$PDOM->removeChild($PDOM->firstChild);
		Converter::trimDOMElement($PDOM);
	}
} while($PDOMs->length);

// organise @rend=indent (this block of code should be located before moving same attributes from hi to p)
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query("//hi[contains(concat(' ', @rend, ' '), ' indent ')]");
foreach($PDOMs as $PDOM) {
	$pDOM = $DOM->createElement('p');
	$pDOM->setAttribute('rend', $PDOM->getAttribute('rend'));
	while($PDOM->childNodes->length) {
		$pDOM->appendChild($PDOM->firstChild);
	}
	$PDOM->parentNode->replaceChild($pDOM, $PDOM);
}

// if all l have the same style, move style to lg; same for hi in p
$DOMXPath = new DOMXPath($DOM);
foreach(array('lg','p') as $targetElement) {
	$PDOMs = $DOMXPath->query('//'.$targetElement);
	foreach($PDOMs as $PDOM) {
		Converter::trimDOMElement($PDOM);
		$Lstyles = null;
		foreach($PDOM->childNodes as $lDOM) {
			if(($lDOM->nodeName == 'lb') || (($lDOM->nodeName == '#text') && (trim($lDOM->nodeValue) == ''))) {continue;}
			if(($lDOM->nodeName == '#text') || !$lDOM->hasAttribute('rend')) {$Lstyles = false; break;}
			$lDOMrend = explode(' ', trim($lDOM->getAttribute('rend')));
			if(!is_null($Lstyles)) {
				$Lstyles = array_intersect($Lstyles, $lDOMrend);
				if(empty($Lstyles)) {break;}
			} else {
				$Lstyles = $lDOMrend;
			}
		}
		if(!empty($Lstyles)) {
			$PDOM->setAttribute('rend', trim($PDOM->getAttribute('rend').' '.implode(' ', $Lstyles)));
			foreach($PDOM->childNodes as $lDOM) {
				if($lDOM->hasAttributes() && $lDOM->hasAttribute('rend')) {
					$lDOM->setAttribute('rend', implode(' ', array_diff(explode(' ', $lDOM->getAttribute('rend')), $Lstyles)));
				}
			}
		}
	}
}

// remove TEI_LG_BREAK that are possibly left over
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query('//TEI_LG_BREAK');
foreach($PDOMs as $PDOM) {
	if(($PDOM->nextSibling->nodeName == 'lb') && ($PDOM->previousSibling->nodeName == 'lb')) {
		$PDOM->parentNode->removeChild($PDOM->nextSibling);
	}
	$PDOM->parentNode->removeChild($PDOM);
}

// replace FIGURE_HEAD_TEI with head
$PDOMs = $DOM->getElementsByTagName('FIGURE_HEAD_TEI');
for($a = 0; $a < $PDOMs->length; $a++) {
	$PDOM = $PDOMs->item($a);
	if(!$PDOM) {continue;}
	$PDOMparent = $PDOM->parentNode;
	$headDOM = $DOM->createElement('head');
	$PDOMparent->insertBefore($headDOM, $PDOM);
	$headDOM->setAttribute('rend', $PDOM->getAttribute('rend'));
	foreach($PDOM->childNodes as $PDOMchildDOM) {
		$headDOM->appendChild($PDOMchildDOM->cloneNode(true));
	}
	$PDOMparent->removeChild($PDOM);
	$a--;
}

// if p is child of hi, move it out
$DOMXPath = new DOMXPath($DOM);
do {
	$PDOMs = $DOMXPath->query('//hi/p');
	if($PDOMs->length == 0) {break;}
	$PDOM = $PDOMs->item(0)->parentNode;

	$curPDOM = null;
	while($PDOM->childNodes->length) {
		if(is_null($curPDOM)) {
			$curPDOM = $PDOM->cloneNode();
			$PDOM->parentNode->insertBefore($curPDOM, $PDOM);
		}
		if($PDOM->firstChild->nodeName == 'p') {
			$PDOM->firstChild->setAttribute('rend', trim($PDOM->getAttribute('rend').' '.$PDOM->firstChild->getAttribute('rend')));
			$PDOM->parentNode->insertBefore($PDOM->firstChild, $PDOM);
			$curPDOM = null;
		} else {
			$curPDOM->appendChild($PDOM->firstChild);
		}
	}
	$PDOM->parentNode->removeChild($PDOM);
} while($PDOMs->length);

// if p is child of p, separate them
$DOMXPath = new DOMXPath($DOM);
do {
	$PDOMs = $DOMXPath->query('//p/p');
	if($PDOMs->length == 0) {break;}

	$PDOM = $PDOMs->item(0)->parentNode;
	Converter::trimDOMElement($PDOM);

	$curPDOM = null;
	while($PDOM->childNodes->length) {
		if(is_null($curPDOM)) {
			$curPDOM = $PDOM->cloneNode();
			$PDOM->parentNode->insertBefore($curPDOM, $PDOM);
		}
		if($PDOM->firstChild->nodeName == 'p') {
			$PDOM->firstChild->setAttribute('rend', trim($PDOM->getAttribute('rend').' '.$PDOM->firstChild->getAttribute('rend')));
			$PDOM->parentNode->insertBefore($PDOM->firstChild, $PDOM);
			$curPDOM = null;
		} else {
			$curPDOM->appendChild($PDOM->firstChild);
		}
	}
	$PDOM->parentNode->removeChild($PDOM);
} while($PDOMs->length);

// if specific element is first child of p, move it in front of p
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query('//p');
foreach($PDOMs as $PDOM) {
	for($i = 0; $i < $PDOM->childNodes->length; $i++) {
		Converter::trimDOMElement($PDOM);
		if($PDOM->childNodes->length == 0) {break;}
		if(in_array($PDOM->firstChild->nodeName, array('figure','lg','table','pb'))) {
			$PDOM->parentNode->insertBefore($PDOM->firstChild, $PDOM);
			$i--;
		} else {
			break;
		}
	}
}

// if element[1] is the only child of element[0], move content to parent
$DOMXPath = new DOMXPath($DOM);
foreach(array(array('p','hi'), array('note','p'), array('cell','p')) as $elementsPair) {
	$PDOMs = $DOMXPath->query('//'.$elementsPair[0].'[count(*)=1]['.$elementsPair[1].']');
	foreach($PDOMs as $PDOM) {
		Converter::trimDOMElement($PDOM);
		if($PDOM->childNodes->length == 1) {
			$PDOMChild = $PDOM->firstChild;
			while($PDOMChild->childNodes->length) {
				$PDOM->appendChild($PDOMChild->firstChild);
			}
			$PDOM->setAttribute('rend', trim($PDOM->getAttribute('rend').' '.$PDOMChild->getAttribute('rend')));
			$PDOM->removeChild($PDOMChild);
		}
		Converter::trimDOMElement($PDOM);
	}
}

// trim and remove some elements if they are empty
$DOMXPath = new DOMXPath($DOM);
foreach(array('hi','p','l','lg','row') as $elementEmpty) {
	$PDOMs = $DOMXPath->query('//'.$elementEmpty);
	foreach($PDOMs as $PDOM) {
		Converter::trimDOMElement($PDOM);
		if($PDOM->childNodes->length == 0) {
			$PDOM->parentNode->removeChild($PDOM);
		}
	}
}

// insert page break at the beginning of the text if text has manual breaks
if($documentHasManualPageBreaks) {
	$bodyDOM = $DOM->getElementsByTagName('body')->item(0);
	$bodyDOM->insertBefore($DOM->createElement('pb'), $bodyDOM->firstChild);
}

// process pb elements
$pageCount = 0;
$PDOMs = $DOM->getElementsByTagName('pb');
foreach($PDOMs as $PDOM) {
	$pageCount++;

	$pbPage = ($PDOM->hasAttribute('page')?$PDOM->getAttribute('page'):$pageCount);
	$PDOM->setAttribute('facs', '#'.$selectedWork->getPrefix().'-'.sprintf('%03d', $pbPage));
	$PDOM->setAttribute('n', $pbPage);
	$PDOM->setAttribute('xml:id', 'pb.'.sprintf('%03d', $pbPage));
	$PDOM->removeAttribute('page');
}

// trim text in elements l
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query('//l');
foreach($PDOMs as $PDOM) {
	if($PDOM->firstChild->nodeName == '#text') {
		if($PDOM->childNodes->length == 1) {
			$PDOM->firstChild->nodeValue = trim($PDOM->firstChild->nodeValue);
			continue; // performance
		} else {
			$PDOM->firstChild->nodeValue = ltrim($PDOM->firstChild->nodeValue);
		}
	}
	if($PDOM->lastChild->nodeName == '#text') {$PDOM->lastChild->nodeValue = rtrim($PDOM->lastChild->nodeValue);}
}

// replace TEI_BIGGAP_NONSTANDARD with lb
$PDOMs = $DOM->getElementsByTagName('TEI_BIGGAP_NONSTANDARD');
while($PDOMs->length > 0) {
	$PDOMs->item(0)->parentNode->replaceChild($DOM->createElement('lb'), $PDOMs->item(0));
}

// remove lb that are children of div or body
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query('//body/lb | //div/lb');
foreach($PDOMs as $PDOM) {
	$PDOM->parentNode->removeChild($PDOM);
}

// replace all nowiki with their content
$PDOMs = $DOM->getElementsByTagName('nowiki');
while($PDOMs->length > 0) {
	$PDOM = $PDOMs->item(0);
	$PDOM->parentNode->replaceChild($DOM->createTextNode(base64_decode($PDOM->firstChild->nodeValue)), $PDOM);
}



/* metadata */

// metadata from settings
$DOMXPath = new DOMXPath($DOM);
Converter::appendMetadata($DOM, $settings['metadata']['principal'], 'name', $DOMXPath->query('teiHeader/fileDesc/titleStmt/principal')->item(0));
Converter::appendMetadata($DOM, $settings['metadata']['application'], 'p', $DOMXPath->query('teiHeader/encodingDesc/appInfo/application')->item(0));
Converter::appendMetadata($DOM, $settings['metadata']['availability'], 'p', $DOMXPath->query('teiHeader/fileDesc/publicationStmt/availability')->item(0));
Converter::appendMetadata($DOM, $settings['metadata']['translation-translator'], 'resp', $DOMXPath->query('teiHeader/fileDesc/sourceDesc/bibl/respStmt')->item(0));
Converter::appendMetadata($DOM, $settings['metadata']['pubPlace'], 'pubPlace', $DOMXPath->query('text/front/docImprint')->item(0));
Converter::appendXMLMetadata($DOM, $settings['metadata']['projectDesc'], $DOMXPath->query('teiHeader/encodingDesc/projectDesc')->item(0));

// basic data about the text
$selectedWorkYears = $selectedWork->getYears();
$metadataManual = array(
	'idno' => $selectedWork->getSignature(),
	'title-rich' => ($selectedWork->hasAuthors()?$selectedWork->getFirstAuthor():'?').': '.($selectedWork->hasTitle()?$selectedWork->getNormalisedTitle():'?').'. ('.($selectedWork->hasYears()?(count($selectedWorkYears)>1?intval($selectedWorkYears[0]).'–'.intval($selectedWorkYears[count($selectedWorkYears)-1]):$selectedWorkYears[0]):'?').') ['.$siteinfo['sitename'].']',
	'title-original' => ($selectedWork->hasTitle()?$selectedWork->getTitle():'?'),
	'title-normalised' => ($selectedWork->hasTitle()?$selectedWork->getNormalisedTitle():'?'),
	'author' => ($selectedWork->hasAuthors()?$selectedWork->getAuthors():array('?')),
	'translator' => $selectedWork->getTranslator(),
	'date' => ($selectedWork->hasYears()?$selectedWork->getYears():array('?')),
	'date-short' => implode(', ', ($selectedWork->hasYears()?$selectedWork->getYears():array('?'))),
	'publisher' => $selectedWork->getPublisher(),
	'note' => $selectedWork->getNote(),
	'ref' => $settings['wiki-url-prefix'].$selectedWork->getLink(), // also @target
	'dategenerated' => date('Y-m-d'),
);

$metadataElementsDOM = $DOMXPath->query('//*[@wiki2tei-metadata]');
for($i = $metadataElementsDOM->length-1; $i >= 0; $i--) {
	$metadataElementDOM = $metadataElementsDOM->item($i);
	$metadataElementMetadata = $metadataElementDOM->getAttribute('wiki2tei-metadata');
	$metadataElementDOM->removeAttribute('wiki2tei-metadata');

	$metadataValue = $metadataManual[$metadataElementMetadata];

	if(is_array($metadataValue)) {
		foreach($metadataValue as $metadataValueEntry) {
			$newElDOM = $metadataElementDOM->cloneNode();
			$newElDOM->appendChild($DOM->createTextNode($metadataValueEntry));
			$metadataElementDOM->parentNode->insertBefore($newElDOM, $metadataElementDOM);
		}
		$metadataElementDOM->parentNode->removeChild($metadataElementDOM);
	} else {
		if(($metadataElementMetadata == 'date') && strstr($metadataValue, '/')) {
			$metadataValue = explode('/', $metadataValue);
			$metadataElementDOM->setAttribute('notBefore', (int) $metadataValue[0]);
			$metadataElementDOM->setAttribute('notAfter', (int) $metadataValue[1]);
			$metadataValue = implode('-', $metadataValue);
		}
		if($metadataElementMetadata == 'ref') {
			$metadataElementDOM->setAttribute('target', $metadataValue);
		}
		if(in_array($metadataElementMetadata, array('publisher')) && is_null($metadataValue)) {
			$metadataElementDOM->parentNode->removeChild($metadataElementDOM);
			continue;
		}
		if(in_array($metadataElementMetadata, array('translator','note')) && is_null($metadataValue)) {
			$metadataElementDOM->parentNode->parentNode->removeChild($metadataElementDOM->parentNode);
			continue;
		}
		while($metadataElementDOM->childNodes->length) {
			$metadataElementDOM->removeChild($metadataElementDOM->firstChild);
		}
		$metadataElementDOM->appendChild($DOM->createTextNode($metadataValue));
	}
}


// prepare back for surfaces that do not have pb
$divDOM = $DOM->getElementsByTagName('text')->item(0)->appendChild($DOM->createElement('back'))->appendChild($DOM->createElement('div'));
$headDOM = $DOM->createElement('head');
$headDOM->appendChild($DOM->createTextNode($settings['metadata']['facsimiles-heading']));
$divDOM->appendChild($headDOM);

// writes facsimiles
$facsimileList = array(); // list of facsimiles in surface
$facsDOM = $DOM->getElementsByTagName('facsimile')->item(0);
$facsDOM->setAttribute('xml:id', $selectedWork->getPrefix().'-facs');
$workTitleCleanSC = common_replaceSpecialCharacters($selectedWork->getNormalisedTitle());
$facsDir = 'faksimili/'.$selectedWork->getPrefix().'/';
if(file_exists($facsDir)) {
	$facsFiles = scandir($facsDir);
	foreach($facsFiles as $facsFile) {
		if(!is_file($facsDir.$facsFile)) {continue;}
		$facsFileExt = pathinfo($facsFile, PATHINFO_EXTENSION);
		if(!in_array(mb_strtolower($facsFileExt), array('png','jpg','jp2','tif'))) {continue;}
		$facsFilename = pathinfo($facsFile, PATHINFO_FILENAME);
		$facsFilenameData = array_map('strrev', explode('-', strrev($facsFilename), 2));

		// <surface xml:id="WIKI00338-001">
		$surfaceDOM = $DOM->createElement('surface');
		$surfaceDOM->setAttribute('xml:id', $facsFilename);
		$facsDOM->appendChild($surfaceDOM);
		$facsimileList[$facsFilename] = true;

		// <desc xml:lang="en">Facsimile work_title, page 1</desc>
		$descDOM = $DOM->createElement('desc');
		$descDOM->setAttribute('xml:lang', $siteinfo['language']);
		$facsSurfacePage = intval($facsFilenameData[0]);
		$descDOM->appendChild($DOM->createTextNode($settings['metadata']['facsimile-surface-desc-facsimile'].' '.$workTitleCleanSC.', '.($facsSurfacePage==0?$settings['metadata']['facsimile-surface-desc-cover']:$settings['metadata']['facsimile-surface-desc-page'].' '.$facsSurfacePage)));
		$surfaceDOM->appendChild($descDOM);

		foreach(array('orig','medium','small','thumb') as $graphicType) {
			// <graphic n="thumb" url="facs/WIKI00338/WIKI00338-001_t.jpg"/>
			$graphicDOM = $DOM->createElement('graphic');
			$graphicDOM->setAttribute('n', $graphicType);
			$graphicDOM->setAttribute('url', $settings['facsimile-url-prefix'].$selectedWork->getPrefix().'/'.($graphicType=='orig'?$facsFile:$facsFilename.'_'.mb_substr($graphicType, 0, 1).'.jpg'));
			$surfaceDOM->appendChild($graphicDOM);
		}

		if(!$DOM->getElementById('pb.'.sprintf('%03d', $facsSurfacePage)) || ($facsSurfacePage == 0)) {
			// <pb facs="#WIKI00338-001" n="1" xml:id="pb.001"/>
			$pbDOM = $DOM->createElement('pb');
			$pbDOM->setAttribute('facs', '#'.$facsFilename);
			$pbDOM->setAttribute('n', $facsSurfacePage);
			$pbDOM->setAttribute('xml:id', 'pb.'.sprintf('%03d', $facsSurfacePage));
			if($facsSurfacePage == 0) {
				$DOM->getElementsByTagName('body')->item(0)->insertBefore($pbDOM, $DOM->getElementsByTagName('body')->item(0)->firstChild);
			} else {
				$divDOM->appendChild($pbDOM);
			}
		}
	}
}
Converter::trimDOMElement($facsDOM);
if($facsDOM->childNodes->length == 0) {
	$facsDOM->parentNode->removeChild($facsDOM);
} else {
	$facsDOM->setAttribute('n', $facsDOM->getElementsByTagName('surface')->length);
}
if($divDOM->getElementsByTagName('pb')->length == 0) {
	$divDOM->parentNode->parentNode->removeChild($divDOM->parentNode);
} else {
	common_logNotice('We have to create back');
}

// clear vicinity of pb
$changesWereMade = true;
for($i = 1; ($i < 100) && $changesWereMade; $i++) {
	$changesWereMade = false;

	$pbDOMs = $DOM->getElementsByTagName('body')->item(0)->getElementsByTagName('pb'); // to exclude back//pb
	foreach($pbDOMs as $pbDOM) {
		Converter::trimDOMElement($pbDOM->parentNode, false);

		// remove lb if it is located after pb
		if($pbDOM->nextSibling && ($pbDOM->nextSibling->nodeName == 'lb')) {
			$pbDOM->parentNode->removeChild($pbDOM->nextSibling);
			$changesWereMade = true;
		}

		// if pb is the last element of div or p, move it after the parent element
		if(!$pbDOM->nextSibling && (($pbDOM->parentNode->nodeName == 'div') || ($pbDOM->parentNode->nodeName == 'p'))) {
			if($pbDOM->parentNode->nextSibling) {
				$pbDOM->parentNode->parentNode->insertBefore($pbDOM, $pbDOM->parentNode->nextSibling);
			} else {
				$pbDOM->parentNode->parentNode->appendChild($pbDOM);
			}
			$changesWereMade = true;
		}

		// if pb is the only child of l or lg, replace parent element with pb
		if((($pbDOM->parentNode->nodeName == 'l') || ($pbDOM->parentNode->nodeName == 'lg')) && ($pbDOM->parentNode->childNodes->length == 1)) {
			$pbDOM->parentNode->parentNode->replaceChild($pbDOM, $pbDOM->parentNode);
			$changesWereMade = true;
		}
	}
}

// count number of pb and surface elements
if(($DOM->getElementsByTagName('body')->item(0)->getElementsByTagName('pb')->length == 0) && $selectedWork->hasFacsimile()) {
	common_logNotice('There are no pb');
} elseif(($DOM->getElementsByTagName('pb')->length != $DOM->getElementsByTagName('surface')->length) && $selectedWork->hasFacsimile()) {
	common_logNotice('Number of pb and surface elements does not match');
}
foreach($DOM->getElementsByTagName('pb') as $pbDOM) {
	if(!$DOM->getElementById(substr($pbDOM->getAttribute('facs'), 1)) && $selectedWork->hasFacsimile()) {
		common_logNotice('Facsimile '.$pbDOM->getAttribute('facs').' does not have its surface');
	}
}

// write links to facsimiles (currently used only for getTextDataFromFile module)
$facsURLs = array_merge(array(isset($selectedWorkData['dlib-urn'])?$selectedWorkData['dlib-urn']:''), (!empty($selectedWorkData['faksimili'])?$selectedWorkData['faksimili']:array()));
$sourcePubPlaceDOM = $DOM->getElementsByTagName('sourceDesc')->item(0)->getElementsByTagName('pubPlace')->item(0);
if(empty($selectedWorkData['dlib-urn']) && empty($selectedWorkData['faksimili']) && !$selectedWork->isDjvu() && $settings['save-xml']) {
	common_logNotice('There is no facsimile');
}
$facsLastHeader = false;
foreach($facsURLs as $facsURL) {
	if(empty($facsURL)) {continue;}
	$facsURLtext = '';
	if(strlen($facsURL) == 8) {
		$facsHeader = 'dLib.si';
		$facsURLtext = 'URN:NBN:SI:DOC-'.$facsURL;
		$facsURL = 'http://www.dlib.si/?URN=URN:NBN:SI:DOC-'.$facsURL;
	} elseif(strstr($facsURL, 'archive.org')) {
		$facsHeader = 'archive.org';
	} elseif(strstr($facsURL, 'books.google.com')) {
		$facsHeader = 'Google Books';
	} else {
		$facsHeader = '';
	}
	$sourcePubPlaceDOM->appendChild($DOM->createTextNode("\n"));
	if(!empty($facsHeader)) {
		$sourcePubPlaceDOM->appendChild($DOM->createTextNode($facsHeader!=$facsLastHeader?$facsHeader.': ':', '));
	}
	$refDOM = $DOM->createElement('ref');
	$refDOM->setAttribute('target', $facsURL);
	$refDOM->appendChild($DOM->createTextNode(!empty($facsURLtext)?$facsURLtext:$facsURL));
	$sourcePubPlaceDOM->appendChild($refDOM);
	$facsLastHeader = $facsHeader;
}


$skipElementsForNumbering = array('p','lb');
$usedElementsBody = array();
$DOMXPath = new DOMXPath($DOM);
$PDOMs = $DOMXPath->query('//text//*');
foreach($PDOMs as $PDOM) {
	$PDOM->removeAttribute('TEITRIMMED1');

	// remove @rend of p that has only lb as a child
	if(($PDOM->nodeName == 'p') && ($PDOM->childNodes->length == 1) && ($PDOM->firstChild->nodeName == 'lb')) {
		$PDOM->removeAttribute('rend');
	}

	// insert space before lb if necessary
	if(($PDOM->nodeName == 'lb') && $PDOM->previousSibling) {
		$PDOM->parentNode->insertBefore($DOM->createTextNode(' '), $PDOM);
	}

	// process @rend - remove duplicated values and sort alphabetically
	if($PDOM->hasAttribute('rend')) {
		$attrRend = trim($PDOM->getAttribute('rend'));
		if($attrRend === '') {
			$PDOM->removeAttribute('rend');
		} else {
			$attrRend = array_unique(explode(' ', $attrRend));
			sort($attrRend);
			$attrRend = trim(implode(' ', $attrRend));
			$PDOM->setAttribute('rend', $attrRend);
		}
	}

	// count elements that appear in the text
	if(!isset($usedElementsBody[$PDOM->nodeName])) {
		$usedElementsBody[$PDOM->nodeName] = 0;
	}
	$usedElementsBody[$PDOM->nodeName]++;

	// number elements
	if(!$PDOM->hasAttribute('xml:id') && !in_array($PDOM->nodeName, $skipElementsForNumbering)) {
		$PDOM->setAttribute('xml:id', $PDOM->nodeName.'.'.$usedElementsBody[$PDOM->nodeName]);
	}
}
$DOM->normalize();
ksort($usedElementsBody);

// count elements
$elementsCount = array();
foreach(array('facsimile','surface') as $elementToCount) {
	$elementsCount[$elementToCount] = $DOM->getElementsByTagName($elementToCount)->length;
}
foreach(array_keys($usedElementsBody) as $elementToCount) {
	$elementsCount[$elementToCount] = $DOM->getElementsByTagName('text')->item(0)->getElementsByTagName($elementToCount)->length;
}

$tagsDeclDOM = $DOM->getElementsByTagName('tagsDecl')->item(0)->getElementsByTagName('namespace')->item(0);
foreach($elementsCount as $elementName => $elementCount) {
	if($elementCount == 0) {continue;}
	$tagUsageDOM = $DOM->createElement('tagUsage');
	$tagUsageDOM->setAttribute('gi', $elementName);
	$tagUsageDOM->setAttribute('occurs', $elementCount);
	$tagsDeclDOM->appendChild($tagUsageDOM);
}

// set document language
$documentLanguage = language_getDocumentLanguage($file, $siteinfo['language']);
$textDOM = $DOM->getElementsByTagName('text')->item(0);
$textDOM->setAttribute('xml:lang', $documentLanguage);
$textDOM->getElementsByTagName('body')->item(0)->setAttribute('xml:lang', $documentLanguage);

// taxonomy
Converter::populateTaxonomyCategories($DOM->getElementByID('Text.taxonomy'), $settings['taxonomy-categories']);
Converter::addTaxonomyCategories($DOM, $selectedWork->getCategories());

// set root element attributes
$TEIDOM = $DOM->getElementsByTagName('TEI')->item(0);
$TEIDOM->setAttribute('xml:id', $metadataManual['idno']);
$TEIDOM->setAttribute('xmlns', 'http://www.tei-c.org/ns/1.0');


$file = $DOM->saveXML();
$DOM = new DOMDocument();
$DOM->preserveWhiteSpace = false;
$DOM->loadXML($file);
$DOM->formatOutput = true;
$file = $DOM->saveXML();

// validation
if(!empty($settings['dtd-schema'])) {
	if(file_exists($settings['dtd-schema'])) {
		Converter::validateByDTD($DOM, $settings['dtd-schema']);
	} else {
		common_logNotice('DTD schema "'.$settings['relaxng-schema'].'" does not exist - validation skipped');
	}
}

if(!empty($settings['relaxng-schema'])) {
	if(file_exists($settings['relaxng-schema'])) {
		Converter::validateByRelaxNG($DOM, $settings['relaxng-schema']);
	} else {
		common_logNotice('RelaxNG schema "'.$settings['relaxng-schema'].'" does not exist - validation skipped');
	}
}

Converter::checkUnconvertedSyntax($file);

if($settings['save-xml']) {
	common_saveFile($settings['xml-folder'].'/'.$selectedWork->getSignature().'.xml', $file);
}

if(!empty($settings['download-xml'])) {
	header('Content-Disposition: attachment; filename="'.common_replaceSpecialCharacters(urldecode($selectedWork->getLink())).'.xml"');
}

header('Content-Type: text/xml');
print $file;


if(!empty($selectedWorkData['dlib-urn'])) {
	include('modules/getFacsimileFromDLib.inc.php');
}

common_logNotice('Conversion finished', false);
