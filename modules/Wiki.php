<?php

class Wiki {
	const FALLBACK_SITENAME = 'Wikisource';
	const FALLBACK_LANGUAGE = 'en';
	static protected $siteinfo = array();
	static protected $redirectMagicWords = array();
	static protected $categoryNamespaces = array();
	static protected $imageNamespaces = array();

	static public function getDomain() {
		global $settings;
		return (isset($settings['wiki-domain'])?$settings['wiki-domain']:$settings['wiki-default-domain']);
	}

	static public function setDomain($wikiDomain) {
		global $settings;

		$settings['wiki-domain'] = $wikiDomain;

		if(!isset(self::$siteinfo[$wikiDomain])) {
			$siteinfoXML = common_fetchContentFromWiki('api.php?action=query&meta=siteinfo&siprop=general&format=xml', true);
			if($siteinfoXML !== false) {
				try {
					$DOM = new DOMDocument();
					$DOM->loadXML($siteinfoXML);
					$siteinfoElement = $DOM->getElementsByTagName('general')->item(0);
				} catch(DOMException $e) {
					$siteinfoElement = false;
				}
			} else {
				$siteinfoElement = false;
			}

			self::$siteinfo[$wikiDomain] = array(
				'sitename' => ($siteinfoElement&&$siteinfoElement->hasAttribute('sitename')?$siteinfoElement->getAttribute('sitename'):self::FALLBACK_SITENAME),
				'language' => ($siteinfoElement&&$siteinfoElement->hasAttribute('lang')?$siteinfoElement->getAttribute('lang'):self::FALLBACK_LANGUAGE),
			);
		}

		$settingsFilename = 'config.'.preg_replace('/[^A-Za-z_-]?/', '', self::$siteinfo[$wikiDomain]['language']).'.inc.php';
		if(file_exists($settingsFilename)) {
			include($settingsFilename);
		}
	}

	static public function fetchSiteinfo() {
		$wikiDomain = self::getDomain();
		return self::$siteinfo[$wikiDomain];
	}

	static public function getAuthorFullName($author) {
		global $settings;

		$file = common_fetchPageFromWiki((!empty($settings['author-page-prefix'])?$settings['author-page-prefix']:'').$author);
		if($file === false) {
			return $author;
		}

		$authorTemplate = Converter::parseWikiTemplate($file, $settings['author-template']);
		if(!empty($authorTemplate[$settings['author-template-firstname-parameter']]) && !empty($authorTemplate[$settings['author-template-lastname-parameter']])) {
			$authorLabels = array(
				'{firstname}' => $authorTemplate[$settings['author-template-firstname-parameter']],
				'{lastname}' => $authorTemplate[$settings['author-template-lastname-parameter']],
			);
			return str_replace(array_keys($authorLabels), array_values($authorLabels), $settings['author-fullname-pattern']);
		} else {
			return $author;
		}
	}

	static public function fetchPageCategoriesDeep($pagetitle, $knownCategories=array()) {
		$categories = $knownCategories;

		$pageCategoriesData = common_fetchContentFromWiki('api.php?action=query&prop=categories&titles='.urlencode($pagetitle).'&cllimit=100&clshow=!hidden&format=xml', true);
		if($pageCategoriesData !== false) {
			$DOM = new DOMDocument();
			$DOM->loadXML($pageCategoriesData);
			foreach($DOM->getElementsByTagName('cl') as $categoryDataDOM) {
				$newCategory = $categoryDataDOM->getAttribute('title');
				if(!in_array($newCategory, $categories)) {
					$categories[] = $newCategory;
					$newCategoryCategories = self::fetchPageCategoriesDeep($newCategory, $categories);
					$categories = array_unique(array_merge($categories, $newCategoryCategories));
				}
			}
		}

		return $categories;
	}

	static public function getRedirectMagicWords() {
		$wikiDomain = self::getDomain();

		if(!isset(self::$redirectMagicWords[$wikiDomain])) {
			$entries = array_merge(array('#REDIRECT'), self::getAdvancedTranslateData('words', 'redirect'));
			self::$redirectMagicWords[$wikiDomain] = array_unique($entries);
		}

		return self::$redirectMagicWords[$wikiDomain];
	}

	static public function getCategoryNamespaces() {
		$wikiDomain = self::getDomain();

		if(!isset(self::$categoryNamespaces[$wikiDomain])) {
			$entries = array_merge(array('Category'), self::getAdvancedTranslateData('namespace', 'NS_CATEGORY'));
			self::$categoryNamespaces[$wikiDomain] = array_unique($entries);
		}

		return self::$categoryNamespaces[$wikiDomain];
	}

	static public function getImageNamespaces() {
		$wikiDomain = self::getDomain();

		if(!isset(self::$imageNamespaces[$wikiDomain])) {
			$entries = array_merge(array('Image', 'File'), self::getAdvancedTranslateData('namespace', 'NS_FILE'), self::getAdvancedTranslateData('namespace', 'NS_MEDIA'));
			self::$imageNamespaces[$wikiDomain] = array_unique($entries);
		}

		return self::$imageNamespaces[$wikiDomain];
	}

	static public function getTemplateNamePattern($templateName) {
		$templateName = ltrim($templateName);
		$templateNameFirstLetter = mb_substr($templateName, 0, 1);
		$templateNamePattern = '['.mb_strtoupper($templateNameFirstLetter).$templateNameFirstLetter.']'.str_replace(' ', '[ _]', preg_quote(str_replace('_', ' ', mb_substr($templateName, 1))));
		return $templateNamePattern;
	}

	static protected function getAdvancedTranslateData($module, $dataEntry) {
		$entries = array();

		$siteinfo = self::fetchSiteinfo();
		$wikiExport = common_fetchContent('https://translatewiki.net/w/i.php?language='.urlencode($siteinfo['language']).'&module='.urlencode($module).'&export=true&title=Special%3AAdvancedTranslate', true);
		preg_match('/\n\s*[\'"]?'.preg_quote($dataEntry).'[\'"]?\s*=&gt;\s*(.*?)\s*,\n/', $wikiExport, $matches);
		if(!empty($matches[1])) {
			if(preg_match('/array\s*\((.*)\)/', $matches[1], $matches2)) {
				$entriesRaw = explode(',', $matches2[1]);
				unset($entriesRaw[0]);
			} else {
				$entriesRaw = array($matches[1]);
			}

			foreach($entriesRaw as $entryRaw) {
				$entries[] = trim($entryRaw, '\'" ');
			}
		}

		return array_unique($entries);
	}
}
