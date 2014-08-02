<?php

function language_getDocumentLanguage($file) {
	return ((substr_count($file, 'Ĺż')/mb_strlen($file))>0.01?'sl-bohoric':'sl');
}
