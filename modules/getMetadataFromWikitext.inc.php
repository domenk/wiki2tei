<?php

$metadata = array(
	'title' => array(),
	'title-normalised' => array(),
	'author' => array(),
	'year' => array(),
	'publisher' => array(),
	'translator' => array(),
);

foreach($settings['metadata-templates'] as $metadataTemplateName => $metadataTemplateMetadataEntries) {
	$metadataTemplateParameters = Converter::parseWikiTemplate($file, $metadataTemplateName, $file);
	foreach($metadataTemplateMetadataEntries as $metadataTemplateMetadataEntry) {
		if(!empty($metadataTemplateParameters[$metadataTemplateMetadataEntry['parameter']])) {
			$metadataTemplateParameterValue = $metadataTemplateParameters[$metadataTemplateMetadataEntry['parameter']];
			if(!empty($metadataTemplateMetadataEntry['required-templates'])) {
				$metadataTemplateMetadataValues = metadata_extractValuesOfMatchingTemplates($metadataTemplateParameterValue, $metadataTemplateMetadataEntry['required-templates']);
				if(!empty($metadataTemplateMetadataValues)) {
					$metadata[$metadataTemplateMetadataEntry['metadata']] = array_merge((array) $metadata[$metadataTemplateMetadataEntry['metadata']], $metadataTemplateMetadataValues);
				}
			} else {
				$metadataTemplateMetadataValues = metadata_extractValuesOfMatchingTemplates($metadataTemplateParameterValue, (!empty($metadataTemplateMetadataEntry['optional-templates'])?$metadataTemplateMetadataEntry['optional-templates']:array()));
				if(!empty($metadataTemplateMetadataValues)) {
					$metadata[$metadataTemplateMetadataEntry['metadata']] = array_merge((array) $metadata[$metadataTemplateMetadataEntry['metadata']], $metadataTemplateMetadataValues);
				} else {
					$metadata[$metadataTemplateMetadataEntry['metadata']] = $metadataTemplateParameters[$metadataTemplateMetadataEntry['parameter']];
				}
			}
		}
	}
}

if(is_array($metadata['title'])) {
	$metadata['title'] = (!empty($metadata['title'])?$metadata['title'][0]:'');
}

// (text formatting in metadata is not removed)

/*
// extract data about facsimile (currently not used)
$facsimileMetadata = array();
while(isset($templateNaslovMP['vir'])) {
	$parse = Converter::parseWikiTemplate($templateNaslovMP['vir'], 'fc');
	if(empty($parse)) {break;}
	if(isset($parse['s'])) {$parse['s'] = Converter::getPagesFromString($parse['s']);}
	if(isset($parse['si'])) {$parse['si'] = Converter::getPagesFromString($parse['si']);}
	if(isset($parse['s']) && isset($parse['si']) && (count($parse['s']) != count($parse['si']))) {
		trigger_error('Number of pages in @s does not match with @si');
		$parse['error'] = '@s!=@si';
	} else {
		unset($parse['s'], $parse['si']);
	}
	$facsimileMetadata[] = $parse;
}
*/


// set metadata
if(!$selectedWork->hasTitle() && !empty($metadata['title'])) {
	$selectedWork->setTitle(is_array($metadata['title'])?$metadata['title'][0]:$metadata['title']);
}
if(!$selectedWork->hasNormalisedTitle() && !empty($metadata['title-normalised'])) {
	$selectedWork->setNormalisedTitle(is_array($metadata['title-normalised'])?$metadata['title-normalised'][0]:$metadata['title-normalised']);
}
if(!$selectedWork->hasAuthors() && !empty($metadata['author'])) {
	$metadata['author'] = Wiki::getAuthorFullName($metadata['author']);
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


// extract taxonomy from categories
$workTaxonomyMetadata = array();

$workCategories = Wiki::fetchPageCategoriesDeep(urldecode($selectedWork->getLink()));
$taxonomyByMetadataCategories = metadata_getTaxonomyByMetadataCategories($settings['taxonomy-categories']);
foreach($workCategories as $workCategory) {
	$workCategoryNameParts = explode(':', $workCategory, 2);
	$workCategoryName = $workCategoryNameParts[1];
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

foreach($workTaxonomyMetadata as $workTaxonomyMetadataEntryParent => $workTaxonomyMetadataEntry) {
	$selectedWork->addCategory($workTaxonomyMetadataEntry[0], $workTaxonomyMetadataEntryParent, false);
}
