<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Ext\Views\Helpers;

/**
 * Responsibility - given text content processing to escape whitespace before/after selected words.
 * - Single syllable conjunctions to escape whitespace after (configurable).
 * - Common language shortcuts containing whitespaces to escape whitespace inside (configurable).
 * - Units to escape whitespace after number and before unit (configurable).
 * @method \MvcCore\Ext\Views\Helpers\LineBreaksHelper GetInstance()
 */
class LineBreaksHelper extends \MvcCore\Ext\Views\Helpers\AbstractHelper {

	/**
	 * MvcCore Extension - View Helper - Line Breaks - version:
	 * Comparison by PHP function version_compare();
	 * @see http://php.net/manual/en/function.version-compare.php
	 */
	const VERSION = '5.0.2';

	/**
	 * Weak words by international language code as array key.
	 * Weak words are words, where you don't want to have a line break after.
	 * Weak words have to be configured as string with all weak words separated
	 * by comma character without any spaces.
	 * @var array
	 */
	public static $WeekWordsDefault = [
		// single syllable conjunctions for English
		'en'	=> 'a,an,the,for,and,nor,but,or,yet,so,if,than,then,as,once,till,when,shy,who,how,of,in,to,with',
		// single syllable conjunctions for Deutsch
		'de'	=> 'der,die,das,ein,an,in,am,zu,und,doch,als,ob,bis,da,daß',
		// single syllable conjunctions for Czech/Slovak:-)
		'cs'	=> "a,ač,aj,ak,ať,ba,co,či,do,i,k,ke,ku,o,od,pro,při,s,sa,se,si,sú,v,ve,z,za,ze,že",
	];

	/**
	 * Special shortcuts to not have any line break inside, keyed by language.
	 * @var array
	 */
	public static $ShortcutsDefault = [
		'cs'	=> ['př. kr.', 'př. n. l.', 's. r. o.', 'a. s.', 'v. o. s.', 'o. s. ř.',],
	];

	/**
	 * Units are very short words, where you don't want to have a line break before,
	 * where is any digit before caught unit word and space before caught unit word.
	 * Units have to be configured as string with all units separated
	 * by comma character without any spaces.
	 * @var string
	 */
	public static $UnitsDefault = "%,‰,px,pt,in,ft,yd,mi,mm,cm,dm,m,km,g,dkg,kg,t,ar,ha,ml,dcl,l,cm²,m²,km²,cm³,m³,°C,°F,K";

	// 

	/**
	 * Singleton instance.
	 * @var \MvcCore\Ext\Views\Helpers\LineBreaksHelper
	 */
	protected static $instance;

	/**
	 * Language used for text processing as default.
	 * All text processing should be called with custom language value.
	 * @var string
	 */
	protected $lang = "";

	/**
	 * Source text currently processed.
	 * @var string
	 */
	protected $text = "";

	/**
	 * Store with weak words exploded into single strings, keyed by language.
	 * @var array
	 */
	protected $weekWords = [];

	/**
	 * Exploded units as array of string to be processed in source text.
	 * @var \string[]
	 */
	protected $units = [];

	/**
	 * Prepared shortcuts with no-line breaking spaces, keyed by language.
	 * @var array
	 */
	protected $shortcuts = [];

	/**
	 * Create view helper instance as singleton.
	 * To configure view helper instance, create it by this method
	 * in your base controller in `PreDispatch();` method.
	 * After this singleton instance is created, then you can configure
	 * anything you want.
	 *
	 * Example:
	 *	`\MvcCore\Ext\View\Helpers\LineBreaksHelper::GetInstance()
	 *		->SetView($this->view)
	 *		->SetWeekWords(...)
	 *		->SetShortcuts(...)
	 *		->SetUnits(...);`
	 * @static
	 * @return \MvcCore\Ext\Views\Helpers\LineBreaksHelper
	 */
	public static function GetInstance () {
		if (!static::$instance) static::$instance = new static();
		return static::$instance;
	}

	/**
	 * Create view helper instance.
	 * To configure view helper instance, create it by this method
	 * in your $baseController->preDispatch(); method, after view
	 * instance inside controller is created, then you can configure
	 * anything you want. If Controller contains static property 'Lang',
	 * language for this view helper will be loaded from this property.
	 * @param \MvcCore\View $view
	 */
	public function SetView (\MvcCore\IView $view = NULL) {
		parent::SetView($view);
		$this->lang = $this->request->GetLang();
		return $this;
	}

	/**
	 * Set weak words, where you need to place a HTML space entity,
	 * to not break line after each configured weak word in processing text.
	 * All words has to be configured as single string with all weak words
	 * separated by comma character without any space.
	 * @param \string[]|string $weekWords all weak words as array of strings or string separated by comma character
	 * @param string $lang optional, international language code
	 * @return \MvcCore\Ext\Views\Helpers\LineBreaksHelper
	 */
	public function SetWeekWords ($weekWords, $lang = '') {
		if (!$lang) $lang = $this->lang;
		if (is_array($weekWords)) {
			$this->weekWords[$lang] = $weekWords;
		} else {
			$this->weekWords[$lang] = explode(',', (string) $weekWords);
		}
		return $this;
	}

	/**
	 * Set special shortcuts for specific language to not have any line break inside.
	 * If language is not specified, there is used default language from controller instance.
	 * @param \string[] $shortcuts short cuts as array of strings
	 * @param string $lang optional, international language code
	 * @return \MvcCore\Ext\Views\Helpers\LineBreaksHelper
	 */
	public function SetShortcuts (array $shortcuts, $lang = '') {
		if (!$lang) $lang = $this->lang;
		$this->shortcuts[$lang] = $shortcuts;
		return $this;
	}

	/**
	 * Set units, where you need to place a HTML space entity,
	 * to not break line before each configured unit where is founded digit
	 * character before unit and white space before in source text.
	 * All units has to be configured as single string with all units
	 * separated by comma character without any space.
	 * @param \string[]|string $units all units as array of strings or string separated by comma character
	 * @return \MvcCore\Ext\Views\Helpers\LineBreaksHelper
	 */
	public function SetUnits ($units) {
		if (is_array($units)) {
			$this->units = $units;
		} else {
			$this->units = explode(',', (string) $units);
		}
		return $this;
	}

	/**
	 * Get weak words as array of strings, units and shortcuts.
	 * as array of string for currently processed language.
	 * @param string $lang international language code
	 * @return array
	 */
	protected function getWeekWordsUnitsAndShortcuts ($lang) {
		if (!isset($this->weekWords[$lang])) {
			if (isset(static::$WeekWordsDefault[$lang])) {
				$this->weekWords[$lang] = explode(',', static::$WeekWordsDefault[$lang]);
			} else {
				$this->weekWords[$lang] = [];
			}
		}
		if (!$this->units) 
			$this->units = explode(',', static::$UnitsDefault);
		if (!isset($this->shortcuts[$lang])) {
			if (isset(static::$ShortcutsDefault[$lang])) {
				$shortcuts = [];
				/** @var array $shortcutsLocalized */
				foreach (static::$ShortcutsDefault[$lang] as $shortcut) 
					$shortcuts[$shortcut] = str_replace(' ', '&nbsp;', $shortcut);
				$this->shortcuts[$lang] = & $shortcuts;
			} else {
				$this->shortcuts[$lang] = [];
			}
		}
		return [
			$this->weekWords[$lang], 
			$this->units, 
			$this->shortcuts[$lang]
		];
	}

	/**
	 * Process configured weak words and units and place HTML space entity
	 * where is necessary to not line break source text where it's not wanted.
	 * @param string $text source text
	 * @param string $lang optional, international language code
	 * @return string
	 */
	public function LineBreaks ($text, $lang = "") {
		$this->text = $text;
		$word = "";
		$lang = $lang ? $lang : $this->lang;
		list($weekWords, $units, $shortcuts) = $this->getWeekWordsUnitsAndShortcuts($lang);

		// if there are one or more tab chars in source text, convert them into single space
		$this->text = preg_replace("#\t+#mu", " ", $this->text);

		// if there are one or more space chars in source text, convert them into single space
		$this->text = preg_replace("#[ ]{2,}#mu", " ", $this->text);
		// for each week word
		for ($i = 0, $l = count($weekWords); $i < $l; $i += 1) {
			// load current week word into $word variable
			$word = $weekWords[$i];
			// process source text with current week word
			$this->processWeakWord($word);
			// convert first week word character into upper case (first word in sentence)
			$word = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
			// process source text with current week word with first upper cased char
			$this->processWeakWord($word);
		}

		// for each unit(s), where is white space char before and any number before that white space char:
		for ($i = 0, $l = count($units); $i < $l; $i += 1) {
			// load current unit into $word variable
			$word = $units[$i];
			// create regular expression pattern to search for unit(s), where is white space char before
			// and any number before that white space char
			$regExp = "#([0-9])\\s(" . $word . ")#mu";
			// process replacement for all founded white spaces into fixed space html entity in source text
			$this->text = preg_replace(
				$regExp,
				"$1&nbsp;$2",
				$this->text
			);
		}

		// for all special shortcuts - remove all line breaking white spaces
		foreach ($shortcuts as $sourceShortcut => $targetShortcut) {
			$this->text = str_replace($sourceShortcut, $targetShortcut, $this->text);
		}

		// for all decimals, where is space between them:
		// example: 9 999 999 -> 9&nbsp;999&nbsp;999
		$this->text = preg_replace("#([0-9])\s([0-9])#", "$1&nbsp;$2", $this->text);

		return $this->text;
	}

	/**
	 * Process single weak word - place HTML space entity
	 * where is necessary to not line break source text where it's not wanted.
	 * @param string $word
	 * @return void
	 */
	protected function processWeakWord ($word) {
		$index = 0;
		$text = ' ' . $this->text . ' ';
		// go through infinite loop and process given week word with html fixed spaces replacement
		while (TRUE) {
			$index = mb_strpos($text, ' ' . $word . ' ');
			if ($index !== FALSE) {
				// If there is any week word and basic white space
				// before and after the week word in source text:
				//	- take all source text before week word including white space before week word,
				//	- take week word
				//	- add fixed space html entity
				//	- and add all rest source text after week word
				//	  and white space char after week word
				$text = mb_substr($text, 0, $index + 1) . $word . '&nbsp;' . mb_substr($text, $index + 1 + mb_strlen($word) + 1);
				// move $index variable after position, where is source text already processed
				$index += 1 + mb_strlen($word) + 6; // (6 - means length of space html entity: '&nbsp;'
			} else {
				// there is no other occurrence of week word in source text
				break;
			}
		}
		$this->text = mb_substr($text, 1, mb_strlen($text) - 2);
	}
}
