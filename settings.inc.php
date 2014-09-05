<?php

$settings = array();

$settings['timezone'] = 'Europe/London';
$settings['facsimile-url-prefix'] = '';
$settings['wiki-default-domain'] = 'en.wikisource.org';
$settings['wiki-url-prefix'] = 'http://*.wikisource.org/wiki/'; // use * for any combination of letters, numbers and hyphen
$settings['relaxng-schema'] = false; // path to file; set to false to skip validation

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


// general metadata
$settings['metadata'] = array( // where array is the metadata value, this array is of form lang_code => content
	'principal' => '', // content of teiHeader/fileDesc/titleStmt/principal/name
	'application' => array(), // additional content of teiHeader/encodingDesc/appInfo/application
	'availability' => array( // content of teiHeader/fileDesc/publicationStmt/availability
		'en' => 'This work is licensed under a [http://creativecommons.org/licenses/by-sa/4.0/ Creative Commons Attribution-ShareAlike 4.0 International License].',
		'sl' => 'Besedilo je na razpolago pod dovoljenjem [http://creativecommons.org/licenses/by-sa/4.0/ Creative Commons Priznanje avtorstva-Deljenje pod enakimi pogoji 4.0 mednarodna licenca].',
	),
	'projectDesc' => '', // raw XML; content of teiHeader/encodingDesc/projectDesc
	'translation-translator' => array( // content of teiHeader/fileDesc/sourceDesc/bibl/respStmt
		'en' => 'Translator',
		'sl' => 'Prevajalec',
	),
	'pubPlace' => '', // content of text/front/docImprint/pubPlace
	'signature-prefix' => 'WIKI%05d', // PHP sprintf format
);


// metadata extraction from templates
$settings['author-template'] = 'avtor';
$settings['author-template-firstname-parameter'] = 'ime';
$settings['author-template-lastname-parameter'] = 'priimek';
$settings['author-fullname-pattern'] = '{lastname}, {firstname}'; // you can use labels {firstname} and {lastname}


// taxonomy
$settings['taxonomy-categories'] = array( // key metadata-categories is array[] of array(string category_name, bool category_can_be_overwritten)
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
