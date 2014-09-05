<?php

$settings['content-language'] = 'sl';

$settings['metadata']['availability']['sl'] = 'Besedilo je na razpolago pod dovoljenjem [http://creativecommons.org/licenses/by-sa/4.0/ Creative Commons Priznanje avtorstva-Deljenje pod enakimi pogoji 4.0 mednarodna licenca].';
$settings['metadata']['translation-translator']['sl'] = 'Prevajalec';
$settings['metadata']['facsimiles-heading'] = 'Faksimili';

$settings['author-page-prefix'] = false;
$settings['author-template'] = 'avtor';
$settings['author-template-firstname-parameter'] = 'ime';
$settings['author-template-lastname-parameter'] = 'priimek';
$settings['author-fullname-pattern'] = '{lastname}, {firstname}'; // you can use labels {firstname} and {lastname}

$settings['pagebreak-template'] = 'prelom strani';

$settings['spaced-template'] = 'razprto';
$settings['gothic-template'] = 'gotica';
$settings['cursive-template'] = 'pisano';

$settings['templates-to-remove'] = array('rimska poglavja', 'poglavja', 'neoštevilčena poglavja', 'wikipedija', 'drugipomeni');


$settings['metadata-templates'] = array(
	'naslov' => array(
		array(
			'metadata' => 'title',
			'parameter' => 'naslov',
		),
		array(
			'metadata' => 'author',
			'parameter' => 'avtor',
		),
		array(
			'metadata' => 'translator',
			'parameter' => 'prevajalec',
		),
	),
	'naslov-mp' => array(
		array(
			'metadata' => 'title',
			'parameter' => 'naslov',
			'optional-templates' => array(
				array(
					'template' => 'mp',
					'parameters' => array(1 => 'naslov'),
					'metadata-parameter' => 2,
				),
			),
		),
		array(
			'metadata' => 'title-normalised',
			'parameter' => 'normaliziran naslov',
			'optional-templates' => array(
				array(
					'template' => 'mp',
					'parameters' => array(1 => 'naslov'),
					'metadata-parameter' => 2,
				),
			),
		),
		array(
			'metadata' => 'year',
			'parameter' => 'izdano',
			'required-templates' => array(
				array(
					'template' => 'mp',
					'parameters' => array(1 => 'leto'),
					'metadata-parameter' => 2,
				),
			),
		),
		array(
			'metadata' => 'publisher',
			'parameter' => 'izdano',
			'required-templates' => array(
				array(
					'template' => 'mp',
					'parameters' => array(1 => array('založba','zalozba')),
					'metadata-parameter' => 2,
				),
			),
		),
		array(
			'metadata' => 'author',
			'parameter' => 'avtor',
			'optional-templates' => array(
				array(
					'template' => 'mp',
					'parameters' => array(1 => 'avtor'),
					'metadata-parameter' => 2,
				),
			),
		),
		array(
			'metadata' => 'translator',
			'parameter' => 'prevajalec',
			'optional-templates' => array(
				array(
					'template' => 'mp',
					'parameters' => array(1 => 'avtor'),
					'metadata-parameter' => 2,
				),
			),
		),
	),
);
