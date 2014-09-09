<?php

include('config.defaults.inc.php');

function form_printCategoriesElementOptions($categories, $language) {
	foreach($categories as $category) {
		$categoryName = (isset($category['desc'][$language])?$category['desc'][$language]:$category['id']);
		if(!empty($category['categories'])) {
			print '<optgroup label="'.htmlspecialchars($categoryName).'">';
			form_printCategoriesElementOptions($category['categories'], $language);
			print '</optgroup>';
		} else {
			print '<option value="'.htmlspecialchars($category['id']).'">'.htmlspecialchars($categoryName).'</option>';
		}
	}
}

function hsc($string) {
	return htmlspecialchars($string);
}

$indexTranslations = array(
	'en' => array(
		'language' => 'English',
		'title' => 'Conversion of texts from Wikisource to TEI',
		'main-heading-html' => 'Conversion of texts from <a href="http://'.hsc($settings['wiki-default-domain']).'/" target="_blank">Wikisource</a> to TEI',
		'form-url-address-label' => 'URL address of text:',
		'form-url-address-format' => 'Address should be of the following form: '.$settings['wiki-url-prefix'].'Text_title.',
		'form-submit-button' => 'Convert',
		'form-reset-button' => 'Reset',
		'metadata-heading' => 'Metadata',
		'metadata-description' => 'You are not required to enter metadata. Converter will try to extract missing information from metadata on Wikisource.',
		'metadata-title-label' => 'Original title:',
		'metadata-title-normalised-label' => 'Normalised title:',
		'metadata-author-label' => 'Author:',
		'metadata-year-label' => 'Year:',
		'metadata-year-note' => '(separate multiple years with a comma)',
		'metadata-publisher-label' => 'Publisher:',
		'metadata-translator-label' => 'Translator:',
		'metadata-signature-label' => 'Signature:',
		'metadata-categories-label' => 'Categories:',
		'metadata-categories-unspecified-option' => '(not specified)',
	),
	'sl' => array(
		'language' => 'slovenščina',
		'title' => 'Pretvorba del z Wikivira v TEI',
		'main-heading-html' => 'Pretvorba del z <a href="http://'.hsc($settings['wiki-default-domain']).'/" target="_blank">Wikivira</a> v TEI',
		'form-url-address-label' => 'URL-naslov besedila:',
		'form-url-address-format' => 'Naslov naj bo oblike '.$settings['wiki-url-prefix'].'Naslov_dela.',
		'form-submit-button' => 'Pretvori',
		'form-reset-button' => 'Ponastavi',
		'metadata-heading' => 'Metapodatki',
		'metadata-description' => 'Vnos metapodatkov ni obvezen. Manjkajoče podatke bo pretvornik poskušal pridobiti iz metapodatkov na Wikiviru.',
		'metadata-title-label' => 'Izvirni naslov:',
		'metadata-title-normalised-label' => 'Normaliziran naslov:',
		'metadata-author-label' => 'Avtor:',
		'metadata-year-label' => 'Leto:',
		'metadata-year-note' => '(več letnic ločite z vejicami)',
		'metadata-publisher-label' => 'Založba:',
		'metadata-translator-label' => 'Prevajalec:',
		'metadata-signature-label' => 'Signatura:',
		'metadata-categories-label' => 'Kategorije:',
		'metadata-categories-unspecified-option' => '(nedoločeno)',
	),
);

$indexLanguage = (!empty($_GET['language'])?$_GET['language']:$settings['content-language']);
if(!isset($indexTranslations[$indexLanguage])) {$indexLanguage = 'en';}

$indexTranslation = $indexTranslations[$indexLanguage];
?>
<!DOCTYPE html>
<html lang="<?=hsc($indexLanguage)?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=hsc($indexTranslation['title'])?></title>
<style type="text/css">
body {margin: 15px; font: 82% Verdana, Arial, sans-serif;}
th {padding: 4px 10px 4px 0px; vertical-align: top; text-align: left;}
.url-address {font-size: 115%; margin-bottom: 30px;}
.url-address-label {margin-bottom: 5px;}
.url-address input {font-size: 90%;}
ul.languages {display: block; float: right; margin: 0px; padding: 0px;}
ul.languages li {display: inline; margin: 0px; padding: 0px;}
ul.languages li:before {content: ' | ';}
ul.languages li:first-child:before {content: none;}
</style>
</head>
<body>

<ul class="languages">
	<?php foreach($indexTranslations as $indexTranslationLanguage => $indexTranslationStrings) { ?>
	<li><a href="?language=<?=hsc($indexTranslationLanguage)?>"><?=($indexTranslationLanguage==$indexLanguage?'<b>':'').hsc($indexTranslationStrings['language']).($indexTranslationLanguage==$indexLanguage?'</b>':'')?></a></li>
	<?php } ?>
</ul>

<h1><?=$indexTranslation['main-heading-html']?></h1>

<form action="parse.php" method="post">
<div class="url-address">
	<div class="url-address-label"><b><?=hsc($indexTranslation['form-url-address-label'])?></b></div>
	<div><input type="text" name="url" size="60" /> <input type="submit" value="<?=hsc($indexTranslation['form-submit-button'])?>" /> <input type="reset" value="<?=hsc($indexTranslation['form-reset-button'])?>" /></div>
	<div><small><?=hsc($indexTranslation['form-url-address-format'])?></small></div>
</div>

<p><b><?=hsc($indexTranslation['metadata-heading'])?></b><br />
<small><?=hsc($indexTranslation['metadata-description'])?></small></p>

<table>
	<tr><th><?=hsc($indexTranslation['metadata-title-label'])?></th><td><input type="text" name="title" size="40" /></td></tr>
	<tr><th><?=hsc($indexTranslation['metadata-title-normalised-label'])?></th><td><input type="text" name="title-normalised" size="40" /></td></tr>
	<tr><th><?=hsc($indexTranslation['metadata-author-label'])?></th><td><input type="text" name="author" /></td></tr>
	<tr><th><?=hsc($indexTranslation['metadata-year-label'])?></th><td><input type="text" name="year" /> <small><?=hsc($indexTranslation['metadata-year-note'])?></small></td></tr>
	<tr><th><?=hsc($indexTranslation['metadata-publisher-label'])?></th><td><input type="text" name="publisher" /></td></tr>
	<tr><th><?=hsc($indexTranslation['metadata-translator-label'])?></th><td><input type="text" name="translator" /></td></tr>
	<tr><th><?=hsc($indexTranslation['metadata-signature-label'])?></th><td><input type="text" name="signatureprefix" size="10" /></td></tr>
	<tr>
		<th><?=hsc($indexTranslation['metadata-categories-label'])?></th>
		<td>
			<table>
				<?php foreach($settings['taxonomy-categories'] as $workCategory) { ?>
				<tr>
					<td><?=hsc(isset($workCategory['desc'][$indexLanguage])?$workCategory['desc'][$indexLanguage]:$workCategory['id'])?>:</td>
					<td>
						<select name="categories[]">
							<option value=""><?=hsc($indexTranslation['metadata-categories-unspecified-option'])?></option>
							<?php form_printCategoriesElementOptions($workCategory['categories'], $indexLanguage); ?>
						</select>
					</td>
				</tr>
				<?php } ?>
			</table>
		</td>
	</tr>
</table>
</form>

</body>
</html>