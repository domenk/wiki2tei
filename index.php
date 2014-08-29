<?php
include('settings.inc.php');

function form_printCategoriesElementOptions($categories) {
	foreach($categories as $category) {
		$categoryName = (isset($category['desc']['sl'])?$category['desc']['sl']:$category['id']);
		if(!empty($category['categories'])) {
			print '<optgroup label="'.htmlspecialchars($categoryName).'">';
			form_printCategoriesElementOptions($category['categories']);
			print '</optgroup>';
		} else {
			print '<option value="'.htmlspecialchars($category['id']).'">'.htmlspecialchars($categoryName).'</option>';
		}
	}
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="sl">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pretvorba del z Wikivira v TEI</title>
<style type="text/css">
body {font: 82% Verdana, Arial, sans-serif;}
th {text-align: left; padding: 4px 10px 4px 0px; vertical-align: top;}
hr {height: 1px; border: none; background-color: gray;}
form.pretvornik {margin-bottom: 40px;}
.url-naslov {font-size: 115%; margin-bottom: 30px;}
.url-naslov-label {margin-bottom: 5px;}
.url-naslov input {font-size: 90%;}
.opis {margin-top: 15px;}
</style>
</head>
<body>

<h1>Pretvorba del z <a href="http://sl.wikisource.org/" target="_blank">Wikivira</a> v TEI</h1>

<form action="parse.php" method="post" class="pretvornik">
<div class="url-naslov">
	<div class="url-naslov-label"><b>URL-naslov besedila:</b></div>
	<div><input type="text" name="url" size="60" /> <input type="submit" value="Pretvori" /></div>
	<div><small>Naslov naj bo oblike <?=htmlspecialchars($settings['wiki-url-prefix'])?>Naslov_dela.</small></div>
</div>

<p><b>Metapodatki</b><br />
<small>Vnos metapodatkov ni obvezen. Manjkajoče podatke bo pretvornik poskušal pridobiti iz metapodatkov na Wikiviru.</small></p>

<table>
	<tr><th>Izvirni naslov:</th><td><input type="text" name="title" size="40" /></td></tr>
	<tr><th>Normaliziran naslov:</th><td><input type="text" name="title-normalised" size="40" /></td></tr>
	<tr><th>Avtor:</th><td><input type="text" name="author" /></td></tr>
	<tr><th>Leto:</th><td><input type="text" name="year" /> <small>(več letnic ločite z vejicami)</small<</td></tr>
	<tr><th>Založba:</th><td><input type="text" name="publisher" /></td></tr>
	<tr><th>Prevajalec:</th><td><input type="text" name="translator" /></td></tr>
	<tr><th>Št. signature:</th><td><input type="text" name="id" size="5" /></td></tr>
	<tr>
		<th>Kategorije:</th>
		<td>
			<table>
				<?php foreach($settings['taxonomy-categories'] as $workCategory) { ?>
				<tr>
					<td><?=htmlspecialchars(isset($workCategory['desc']['sl'])?$workCategory['desc']['sl']:$workCategory['id'])?>:</td>
					<td>
						<select name="categories[]">
							<option value="">(nedoločeno)</option>
							<?php form_printCategoriesElementOptions($workCategory['categories']); ?>
						</select>
					</td>
				</tr>
				<?php } ?>
			</table>
		</td>
	</tr>
</table>
</form>

<?php /*
<hr />

<div class="opis">
	<p>Pretvornik je v preizkusni dobi.</p>
</div>
*/ ?>

</body>
</html>