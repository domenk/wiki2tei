<?php

$settings = array();

$settings['timezone'] = 'Europe/Ljubljana';
$settings['facsimile-url-prefix'] = 'http://nl.ijs.si/imp/wikivir/facs/';
$settings['wiki-domain'] = 'sl.wikisource.org';
$settings['wiki-url-prefix'] = 'http://'.$settings['wiki-domain'].'/wiki/';
$settings['relaxng-scheme'] = 'tei_imp.rng'; // set to false to skip validation

$settings['text-data-modules'] = array(
	'FromForm',
	'FromURL',
	// 'FromFile',
);

$settings['redirect-magicwords'] = array('redirect', 'preusmeritev');

$settings['download-xml'] = true;
// $settings['save-xml'] = false; // uncomment only if you want to override modules' default setting
// $settings['save-images'] = false; // uncomment only if you want to override modules' default setting
$settings['wikitext-folder'] = 'originals';
$settings['xml-folder'] = 'xml';
$settings['facsimile-folder'] = 'facsimile';
$settings['cache-folder'] = 'cache';

$settings['cache-enabled'] = false;

$settings['notices-output'] = 'file'; // print | file | silent
$settings['notices-filename'] = 'CONVERTLOG';
$settings['notices-print-only-errors'] = true;

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
