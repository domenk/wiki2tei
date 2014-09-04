<?php

include_once('getAuthorMetadata.inc.php');

$metadata = array(
	'title' => array(),
	'title-normalised' => array(),
	'author' => array(),
	'year' => array(),
	'publisher' => array(),
	'translator' => array(),
);

$templateNaslovMP = Converter::parseWikiTemplate($file, 'naslov-mp', $file);
$templateNaslov = Converter::parseWikiTemplate($file, 'naslov', $file);

### remove formatting from metadata (e.g. '')

$metadataTemplateParameters = array(
	'title' => array('parameter' => 'naslov', 'mp' => 'naslov'),
	'title-normalised' => array('parameter' => 'normaliziran naslov', 'mp' => 'naslov'),
	'author' => array('parameter' => 'avtor', 'mp' => 'avtor'),
	'year' => array('parameter' => 'izdano', 'mp' => 'leto', 'skip-if-no-mp' => true),
	'publisher' => array('parameter' => 'izdano', 'mp' => array('založba','zalozba'), 'skip-if-no-mp' => true),
	'translator' => array('parameter' => 'prevajalec', 'mp' => 'avtor'),
);
foreach($metadataTemplateParameters as $metadataParameter => $metadataTemplateParameter) {
	if(!empty($templateNaslovMP[$metadataTemplateParameter['parameter']])) {
		$templateNaslovMPParameter = $templateNaslovMP[$metadataTemplateParameter['parameter']];
		do {
			$templateNaslovMPParameterParse = Converter::parseWikiTemplate($templateNaslovMPParameter, 'mp', $templateNaslovMPParameter);
			if(!empty($templateNaslovMPParameterParse) && in_array($templateNaslovMPParameterParse[1], (array) $metadataTemplateParameter['mp'])) {
				$metadata[$metadataParameter][] = $templateNaslovMPParameterParse[2];
			}
		} while(!empty($templateNaslovMPParameterParse));

		if(empty($metadata[$metadataParameter]) && empty($metadataTemplateParameter['skip-if-no-mp'])) {
			$metadata[$metadataParameter] = $templateNaslovMP[$metadataTemplateParameter['parameter']];
		}
	} elseif(!empty($templateNaslov[$metadataTemplateParameter['parameter']]) && empty($metadataTemplateParameter['skip-if-no-mp'])) {
		$metadata[$metadataParameter] = $templateNaslov[$metadataTemplateParameter['parameter']];
	}
}

if(is_array($metadata['title'])) {
	$metadata['title'] = (!empty($metadata['title'])?$metadata['title'][0]:'');
}


/* ###
$faksimileMP = array();
while(isset($templateNaslovMP['vir'])) {
	$parse = Converter::parseWikiTemplate($templateNaslovMP['vir'], 'fc');
	if(empty($parse)) {break;}
	if(isset($parse['s'])) {$parse['s'] = Converter::getPagesFromString($parse['s']);}
	if(isset($parse['si'])) {$parse['si'] = Converter::getPagesFromString($parse['si']);}
	if(isset($parse['s']) && isset($parse['si']) && (count($parse['s']) != count($parse['si']))) {
		trigger_error('Stevilo strani v @s se ne ujema s @si');
		$parse['error'] = '@s!=@si';
	} else {
		unset($parse['s'], $parse['si']);
	}
	$faksimileMP[] = $parse;
}
*/


// extract taxonomy from categories
$workTaxonomyMetadata = array();

$workCategories = common_fetchWikiPageCategoriesDeep(urldecode($selectedWork->getLink()));
$taxonomyByMetadataCategories = common_getTaxonomyByMetadataCategories($settings['taxonomy-categories']);
foreach($workCategories as $workCategory) {
	$workCategoryName = explode(':', $workCategory, 2);
	$workCategoryName = $workCategoryName[1];
	if(isset($taxonomyByMetadataCategories[$workCategoryName])) {
		$taxonomyMetadataCategories = $taxonomyByMetadataCategories[$workCategoryName];
		foreach($taxonomyMetadataCategories as $taxonomyMetadataCategory) {
			if(empty($taxonomyMetadataCategory[2])) {
				$workTaxonomyMetadata[] = array($taxonomyMetadataCategory[0], false);
			}
			if(!isset($workTaxonomyMetadata[$taxonomyMetadataCategory[2]]) || !empty($workTaxonomyMetadata[$taxonomyMetadataCategory[2]][1])) { // [1] means: taxonomy can be overwritten
				$workTaxonomyMetadata[$taxonomyMetadataCategory[2]] = array($taxonomyMetadataCategory[0], $taxonomyMetadataCategory[1]);
			}
		}
	}
}

$workTaxonomy = array();
foreach($workTaxonomyMetadata as $workTaxonomyMetadataEntry) {
	$workTaxonomy[] = $workTaxonomyMetadataEntry[0];
}
$workTaxonomy = array_unique($workTaxonomy);
$selectedWork->addCategories($workTaxonomy);



if(!$selectedWork->hasTitle() && !empty($metadata['title'])) {
	$selectedWork->setTitle(is_array($metadata['title'])?$metadata['title'][0]:$metadata['title']);
}
if(!$selectedWork->hasNormalisedTitle() && !empty($metadata['title-normalised'])) {
	$selectedWork->setNormalisedTitle(is_array($metadata['title-normalised'])?$metadata['title-normalised'][0]:$metadata['title-normalised']);
}
if(!$selectedWork->hasAuthors() && !empty($metadata['author'])) {
	$metadata['author'] = wiki_getAuthorFullName($metadata['author']);
	$selectedWork->addAuthors($metadata['author']);
}
if(!$selectedWork->hasYears() && !empty($metadata['year'])) {
	$selectedWork->addYears($metadata['year']);
}
if(!$selectedWork->hasPublisher() && !empty($metadata['publisher'])) {
	$selectedWork->setPublisher(is_array($metadata['publisher'])?$metadata['publisher'][0]:$metadata['publisher']);
}
if(!$selectedWork->hasTranslator() && !empty($metadata['translator'])) {
	$selectedWork->setTranslator(is_array($metadata['translator'])?$metadata['translator'][0]:$metadata['translator']);
}
