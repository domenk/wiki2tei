<?php

$settings = array();

$settings['content-language'] = 'en';
$settings['timezone'] = 'Europe/London';
$settings['wiki-default-domain'] = 'en.wikisource.org';
$settings['wiki-url-prefix'] = 'http://*.wikisource.org/wiki/'; // use * for any combination of letters, numbers and hyphen

$settings['text-data-modules'] = array(
	'FromURL',
	'FromForm',
	// 'FromFile',
);

$settings['download-xml'] = false;
// $settings['save-xml'] = false; // uncomment only if you want to override modules' default setting
// $settings['save-images'] = false; // uncomment only if you want to override modules' default setting

$settings['wikitext-folder'] = 'originals';
$settings['xml-folder'] = 'xml';
$settings['facsimile-folder'] = 'facsimile';
$settings['cache-folder'] = 'cache';

$settings['cache-enabled'] = true;
$settings['cache-max-age'] = 30*60; // in seconds

$settings['notices-output'] = 'file'; // print | file | silent
$settings['notices-filename'] = 'CONVERTLOG';
$settings['notices-print-only-errors'] = true;

$settings['facsimile-url-prefix'] = '';
$settings['tei-enable-anchor-references'] = true;
$settings['relaxng-schema'] = false; // path to file; set to false to skip validation


// general metadata
$settings['metadata'] = array( // set to false to remove parent element; where array is the metadata value, this array is of form lang_code => content
	'principal' => false, // string; content of teiHeader/fileDesc/titleStmt/principal/name
	'application' => array(), // array; additional content of teiHeader/encodingDesc/appInfo/application
	'availability' => array('en' => 'This work is licensed under a [http://creativecommons.org/licenses/by-sa/4.0/ Creative Commons Attribution-ShareAlike 4.0 International License].'), // array; content of teiHeader/fileDesc/publicationStmt/availability
	'projectDesc' => false, // raw XML string; content of teiHeader/encodingDesc/projectDesc
	'translation-translator' => array('en' => 'Translator'), // array; content of teiHeader/fileDesc/sourceDesc/bibl/respStmt
	'pubPlace' => false, // string; content of text/front/docImprint/pubPlace
	'facsimiles-heading' => 'Facsimiles',
	'facsimile-surface-desc-facsimile' => 'Facsimile',
	'facsimile-surface-desc-cover' => 'cover',
	'facsimile-surface-desc-page' => 'page',
	'signature-prefix' => 'WIKI%05d', // string in PHP sprintf format
);


// metadata extraction from templates
$settings['author-page-prefix'] = false;
$settings['author-template'] = false;
$settings['author-template-firstname-parameter'] = false;
$settings['author-template-lastname-parameter'] = false;
$settings['author-fullname-pattern'] = '{firstname} {lastname}'; // you can use labels {firstname} and {lastname}

$settings['unclear-template'] = false; // template for unclear text should have the following syntax: {{unclear [ | transcript of barely readable text [ | transcript suggestion ] ] }}
$settings['redaction-template'] = false; // template for redacted text should have the following syntax: {{redaction | old text | new text }}
$settings['pagebreak-template'] = false;

// text formatting templates should accept text as the first parameter
$settings['spaced-template'] = false;
$settings['gothic-template'] = false;
$settings['cursive-template'] = false;

$settings['templates-to-remove'] = array(); // template names or template name prefixes; first letter is case-insensitive, spaces and underscores are treated equal


/***
 * Structure of metadata templates array:
 *
 * metadataTemplates = array(templateName => array[] parameterWithMetadata)
 * parameterWithMetadata = array(
 * 	'metadata' => name of metadata,
 * 	'parameter' => name of the template parameter in which metadata will be searched,
 * 	'optional-templates' => array[] parameterTemplate; if this templates exist in the parameter, only data in them will be used,
 * 	'required-templates' => array[] parameterTemplate; extract metadata only from this templates; if they do not exist, skip parameter
 * )
 * parameterTemplate = array(
 * 	'template' => template name,
 * 	'parameters' => array(parameter name => parameter content, ...); array of parameters and their content that should match (parameter content can be specified as an array; if that's the case, any value in the array should match),
 * 	'metadata-parameter' => name of this template parameter that contains metadata,
 * )
 */
$settings['metadata-templates'] = array();


include('settings.en.inc.php');


// taxonomy
$settings['taxonomy-categories'] = array( // value of key metadata-categories is array[] of array(string category_name, bool category_can_be_overwritten)
	array(
		'id' => 'Text.medium',
		'desc' => array('sl' => 'prenosnik', 'en' => 'medium'),
		'categories' => array(
			array(
				'id' => 'Text.manuscript',
				'desc' => array('sl' => 'rokopis', 'en' => 'manuscript'),
				'metadata-categories' => array(array('Rokopisi', false)),
			),
			array(
				'id' => 'Text.book',
				'desc' => array('sl' => 'knjiga', 'en' => 'book'),
				'metadata-categories' => array(array('Knjige', true), array('Knjižne zbirke', true)),
			),
			array(
				'id' => 'Text.magazine',
				'desc' => array('sl' => 'revija', 'en' => 'magazine'),
				'metadata-categories' => array(array('Revije', false)),
			),
			array(
				'id' => 'Text.newspaper',
				'desc' => array('sl' => 'časopis', 'en' => 'newspaper'),
				'metadata-categories' => array(array('Časopisi', false), array('Podlistki', false)),
			),
		),
	),
	array(
		'id' => 'Text.type',
		'desc' => array('sl' => 'zvrst besedila', 'en' => 'text type'),
		'categories' => array(
			array(
				'id' => 'Text.fiction',
				'desc' => array('sl' => 'umetnostno', 'en' => 'fiction'),
				'categories' => array(
					array(
						'id' => 'Text.prose',
						'desc' => array('sl' => 'proza', 'en' => 'prose'),
						'metadata-categories' => array(array('Proza', false)),
					),
					array(
						'id' => 'Text.drama',
						'desc' => array('sl' => 'gledališka igra', 'en' => 'drama'),
						'metadata-categories' => array(array('Igre', false)),
					),
					array(
						'id' => 'Text.poetry',
						'desc' => array('sl' => 'poezija', 'en' => 'poetry'),
						'metadata-categories' => array(array('Pesmi', false)),
					),
				),
			),
			array(
				'id' => 'Text.nonfiction',
				'desc' => array('sl' => 'stvarno', 'en' => 'non-fiction'),
				'categories' => array(
					array(
						'id' => 'Text.bees',
						'desc' => array('sl' => 'čebelarstvo', 'en' => 'beekeeping'),
					),
					array(
						'id' => 'Text.cooking',
						'desc' => array('sl' => 'kuharice', 'en' => 'cookbooks'),
					),
				),
			),
			array(
				'id' => 'Text.religious',
				'desc' => array('sl' => 'nabožno', 'en' => 'religious'),
				'metadata-categories' => array(array('Verstvo', false)),
			),
		),
	),
	array(
		'id' => 'Text.status',
		'desc' => array('sl' => 'status', 'en' => 'status'),
		'categories' => array(
			array(
				'id' => 'Text.original',
				'desc' => array('sl' => 'izvirnik', 'en' => 'original'),
				'metadata-categories' => array(array('Wikivir', true)),
			),
			array(
				'id' => 'Text.translation',
				'desc' => array('sl' => 'prevod', 'en' => 'translation'),
				'metadata-categories' => array(array('Prevodi', false)),
			),
		),
	),
);

if(file_exists('settings.additional.inc.php')) {
	require('settings.additional.inc.php');
}
