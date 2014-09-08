# Wiki2tei converter

Wiki2tei converts wikitext from any Wikisource to [TEI XML](http://www.tei-c.org/release/doc/tei-p5-doc/en/html/) format.

Converter currently includes translations and metadata support for English and Slovene Wikisource, but you can convert texts from any Wikisource.

Installation
============
Converter requires PHP version 5.3 or higher with enabled mbstring, dom (libxml) and openssl extensions.

For basic installation you should create `cache` folder in the project's root and give PHP read and write rights to it.

If you enabled logging to file (`$settings['notices-output']`), you should create the file `CONVERTLOG` and make it PHP writable.
The filename can be changed in `settings.additional.inc.php` using `$settings['notices-filename']`.

Settings can be set in `settings.additional.inc.php`.
