<?php

$settings['content-language'] = 'en';

$settings['metadata']['facsimiles-heading'] = 'Facsimile';

$settings['author-page-prefix'] = 'Author:';
$settings['author-template'] = 'author';
$settings['author-template-firstname-parameter'] = 'firstname';
$settings['author-template-lastname-parameter'] = 'lastname';
$settings['author-fullname-pattern'] = '{firstname} {lastname}';

$settings['pagebreak-template'] = false;

$settings['spaced-template'] = false;
$settings['gothic-template'] = 'blackletter';
$settings['cursive-template'] = 'cursive';

$settings['templates-to-remove'] = array('parallel reporter', 'USSCcase', 'CaseCaption', 'PD-');


$settings['metadata-templates'] = array(
	'header' => array(
		array(
			'metadata' => 'title',
			'parameter' => 'title',
		),
		array(
			'metadata' => 'author',
			'parameter' => 'author',
		),
		array(
			'metadata' => 'year',
			'parameter' => 'year',
		),
		array(
			'metadata' => 'translator',
			'parameter' => 'translator',
		),
	),
);
