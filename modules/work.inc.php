<?php

class Work {
	protected $id;
	protected $prefix;
	protected $title;
	protected $titleNormalised;
	protected $authors = array();
	protected $years = array();
	protected $publisher;
	protected $translator;
	protected $link;
	protected $note;
	protected $categories = array();
	protected $isDjvu = false;
	protected $hasFacsimile = false;

	public function __construct($id) {
		$this->setID($id);
	}

	public function getID() {
		return $this->id;
	}

	public function getPrefix() {
		return $this->prefix;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getNormalisedTitle() {
		return ($this->hasNormalisedTitle()?$this->titleNormalised:$this->title);
	}

	public function getFirstAuthor() {
		return (isset($this->authors[0])?$this->authors[0]:false);
	}

	public function getAuthors() {
		return $this->authors;
	}

	public function getYears() {
		return $this->years;
	}

	public function getPublisher() {
		return $this->publisher;
	}

	public function getTranslator() {
		return $this->translator;
	}

	public function getLink() {
		return $this->link;
	}

	public function getNote() {
		return $this->note;
	}

	public function getCategories() {
		return $this->categories;
	}

	public function getSignature() {
		return $this->prefix.'-'.intval(isset($this->years[0])?$this->years[0]:0); // intval is used for cases as 1938/1939
	}

	public function isDjvu() {
		return $this->isDjvu;
	}

	public function hasFacsimile() {
		return $this->hasFacsimile;
	}

	public function setID($id) {
		global $settings;
		$this->id = (int) $id;
		$this->prefix = sprintf($settings['metadata']['signature-prefix'], $this->id);
	}

	public function setTitle($title) {
		$this->title = trim($title);
	}

	public function setNormalisedTitle($title) {
		$this->titleNormalised = trim($title);
	}

	public function addAuthors($author) {
		$this->authors = array_merge($this->authors, array_map('trim', (array) $author));
	}

	public function addYears($year) {
		$this->years = array_merge($this->years, array_map('trim', (array) $year));
	}

	public function addCategories($category) {
		$this->categories = array_unique(array_merge($this->categories, array_map('trim', (array) $category)));
	}

	public function setPublisher($publisher) {
		$this->publisher = trim($publisher);
	}

	public function setTranslator($translator) {
		$this->translator = trim($translator);
	}

	public function setLink($link) {
		$this->link = trim($link);
	}

	public function setNote($note) {
		$this->note = trim($note);
	}

	public function setDjvu($isDjvu) {
		$this->isDjvu = (bool) $isDjvu;
	}

	public function setFacsimile($hasFacsimile) {
		$this->hasFacsimile = (bool) $hasFacsimile;
	}

	public function hasTitle() {
		return !empty($this->title);
	}

	public function hasNormalisedTitle() {
		return !empty($this->titleNormalised);
	}

	public function hasAuthors() {
		return !empty($this->authors);
	}

	public function hasYears() {
		return !empty($this->years);
	}

	public function hasPublisher() {
		return !empty($this->publisher);
	}

	public function hasTranslator() {
		return !empty($this->translator);
	}

	public function hasLink() {
		return !empty($this->link);
	}

	public function hasNote() {
		return !empty($this->note);
	}
}
