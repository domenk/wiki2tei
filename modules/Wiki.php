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
			self::$siteinfo[$wikiDomain]['sitename'] = self::FALLBACK_SITENAME;
			self::$siteinfo[$wikiDomain]['language'] = self::FALLBACK_LANGUAGE;

			$siteinfoXML = common_fetchContentFromWiki('api.php?action=query&meta=siteinfo&siprop=general|namespacealiases|namespaces|magicwords&format=xml', true);

			if($siteinfoXML !== false) {
				try {

					$DOM = new DOMDocument();
					$DOM->loadXML($siteinfoXML);
					$DOMXPath = new DOMXPath($DOM);

					// general
					$generalDOM = $DOMXPath->query('query/general')->item(0);
					if($generalDOM->hasAttribute('sitename')) {
						self::$siteinfo[$wikiDomain]['sitename'] = $generalDOM->getAttribute('sitename');
					}
					if($generalDOM->hasAttribute('lang')) {
						self::$siteinfo[$wikiDomain]['language'] = $generalDOM->getAttribute('lang');
					}

					// redirect magic words
					$redirectMagicWords = array();
					$redirectMagicWordsDOMs = $DOMXPath->query('query/magicwords/magicword[@name="redirect"]//alias');
					foreach($redirectMagicWordsDOMs as $redirectMagicWordsDOM) {
						$redirectMagicWords[] = $redirectMagicWordsDOM->textContent;
					}
					self::$redirectMagicWords[$wikiDomain] = array_unique($redirectMagicWords);

					// all namespaces
					$namespaces = array();
					$namespaceDOMs = $DOMXPath->query('query/namespaces/ns');
					foreach($namespaceDOMs as $namespaceDOM) {
						if($namespaceDOM->hasAttribute('canonical')) {
							$namespaces[$namespaceDOM->getAttribute('id')][] = $namespaceDOM->getAttribute('canonical');
						}
						$namespaces[$namespaceDOM->getAttribute('id')][] = $namespaceDOM->textContent;
					}

					$namespaceAliasDOMs = $DOMXPath->query('query/namespacealiases/ns');
					foreach($namespaceAliasDOMs as $namespaceAliasDOM) {
						$namespaces[$namespaceAliasDOM->getAttribute('id')][] = $namespaceAliasDOM->textContent;
					}

					// specific namespaces
					self::$categoryNamespaces[$wikiDomain] = array_unique($namespaces[14]);
					self::$imageNamespaces[$wikiDomain] = array_unique($namespaces[6]);

				} catch(DOMException $e) {}
			}
		}

		$settingsFilename = 'config.'.preg_replace('/[^A-Za-z_-]?/', '', self::$siteinfo[$wikiDomain]['language']).'.inc.php';
		if(file_exists($settingsFilename) && (self::$siteinfo[$wikiDomain]['language'] != $settings['content-language'])) { // check if file exists and do not include file if it is already included
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
		return self::$redirectMagicWords[self::getDomain()];
	}

	static public function getCategoryNamespaces() {
		return self::$categoryNamespaces[self::getDomain()];
	}

	static public function getImageNamespaces() {
		return self::$imageNamespaces[self::getDomain()];
	}

	static public function getTemplateNamePattern($templateName) {
		$templateName = ltrim($templateName);
		$templateNameFirstLetter = mb_substr($templateName, 0, 1);
		$templateNamePattern = '['.mb_strtoupper($templateNameFirstLetter).$templateNameFirstLetter.']'.str_replace(' ', '[ _]', preg_quote(str_replace('_', ' ', mb_substr($templateName, 1))));
		return $templateNamePattern;
	}
}
