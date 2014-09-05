<?php

class Wiki {
	const FALLBACK_SITENAME = 'Wikisource';
	const FALLBACK_LANGUAGE = 'en';
	static protected $siteinfo = array();
	static protected $redirectMagicWords = array();

	static public function getDomain() {
		global $settings;
		return (isset($settings['wiki-domain'])?$settings['wiki-domain']:$settings['wiki-default-domain']);
	}

	static public function getAuthorFullName($author) {
		global $settings;

		$file = @common_fetchPageFromWiki($author);
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

	static public function fetchSiteinfo() {
		$wikiDomain = self::getDomain();

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

		return self::$siteinfo[$wikiDomain];
	}

	static public function getRedirectMagicWords() {
		$wikiDomain = self::getDomain();

		if(!isset(self::$redirectMagicWords[$wikiDomain])) {
			$redirectEntries = array('#REDIRECT');

			$magicWordsExport = common_fetchContent('http://translatewiki.net/w/i.php?language='.urlencode($language).'&module=words&export=true&title=Special%3AAdvancedTranslate', true);
			preg_match('/[\'"]redirect[\'"]\s*=&gt;\s*array\s*\((.*?)\)/', $magicWordsExport, $matches);
			if(!empty($matches[1])) {
				$redirectEntriesRaw = explode(',', $matches[1]);
				unset($redirectEntriesRaw[0]);

				foreach($redirectEntriesRaw as $redirectEntryRaw) {
					$redirectEntries[] = trim($redirectEntryRaw, '\'" ');
				}
			}

			self::$redirectMagicWords[$wikiDomain] = array_unique($redirectEntries);
		}

		return self::$redirectMagicWords[$wikiDomain];
	}
}
