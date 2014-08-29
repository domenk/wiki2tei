<?php

$settings = array();

$settings['timezone'] = 'Europe/Ljubljana';
$settings['facsimile-url-prefix'] = 'http://nl.ijs.si/imp/wikivir/facs/';
$settings['wiki-default-domain'] = 'sl.wikisource.org';
$settings['wiki-url-prefix'] = 'http://*.wikisource.org/wiki/'; // use * for any combination of letters, numbers and hyphen
$settings['relaxng-scheme'] = 'tei_imp.rng'; // set to false to skip validation

$settings['text-data-modules'] = array(
	'FromURL',
	'FromForm',
	// 'FromFile',
);

$settings['redirect-magicwords'] = array('redirect', 'preusmeritev');

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

$settings['metadata'] = array(
	'principal' => '[http://nl.ijs.si/et/ Tomaž Erjavec (IJS)]', // content of teiHeader/fileDesc/titleStmt/principal/name
	'availability' => array( // content of teiHeader/fileDesc/publicationStmt/availability
		'en' => 'This work is licensed under a [http://creativecommons.org/licenses/by-sa/4.0/ Creative Commons Attribution-ShareAlike 4.0 International License].',
		'sl' => 'Besedilo je na razpolago pod dovoljenjem [http://creativecommons.org/licenses/by-sa/4.0/ Creative Commons Priznanje avtorstva-Deljenje pod enakimi pogoji 4.0 mednarodna licenca].',
	),
	'projectDesc' => '<p>Projekt <ref target="http://nl.ijs.si/imp/">IMP</ref>: <q>Jezikovni viri starejše slovenščine</q>.</p><p>Projekt <ref target="http://sl.wikisource.org/wiki/Wikivir:Slovenska_leposlovna_klasika">Wikivir</ref>: <q>Slovenska leposlovna klasika</q>.</p>', // raw XML; content of teiHeader/encodingDesc/projectDesc
	'translation-translator' => array( // content of teiHeader/fileDesc/sourceDesc/bibl/respStmt
		'en' => 'Translator',
		'sl' => 'Prevajalec',
	),
	'pubPlace' => 'Digitalna knjižnica [http://nl.ijs.si/imp/ IMP]', // content of text/front/docImprint/pubPlace
	'signature-prefix' => 'WIKI%05d', // PHP sprintf format
);

$settings['taxonomy-categories'] = array(
	array(
		'id' => 'Text.medium',
		'desc' => array('sl' => 'prenosnik', 'en' => 'medium'),
		'categories' => array(
			array(
				'id' => 'Text.manuscript',
				'desc' => array('sl' => 'rokopis', 'en' => 'manuscript'),
			),
			array(
				'id' => 'Text.book',
				'desc' => array('sl' => 'knjiga', 'en' => 'book'),
			),
			array(
				'id' => 'Text.magazine',
				'desc' => array('sl' => 'revija', 'en' => 'magazine'),
			),
			array(
				'id' => 'Text.newspaper',
				'desc' => array('sl' => 'časopis', 'en' => 'newspaper'),
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
					),
					array(
						'id' => 'Text.drama',
						'desc' => array('sl' => 'gledališka igra', 'en' => 'drama'),
					),
					array(
						'id' => 'Text.poetry',
						'desc' => array('sl' => 'poezija', 'en' => 'poetry'),
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
			),
			array(
				'id' => 'Text.translation',
				'desc' => array('sl' => 'prevod', 'en' => 'translation'),
			),
		),
	),
);
