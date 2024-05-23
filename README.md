# MvcCore - Extension - View - Helper - Line Breaks

[![Latest Stable Version](https://img.shields.io/badge/Stable-v5.2.0-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-view-helper-linebreaks/releases)
[![License](https://img.shields.io/badge/License-BSD%203-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.4-brightgreen.svg?style=plastic)

MvcCore View Helper extension for processing any visible text content for non-line breaking spaces.
Spaces between digits like `9 999` are replaced into `9&nbsp;999` automatically.

## Installation
```shell
composer require mvccore/ext-view-helper-linebreaks
```

## Configuration
You can configure to not break line:
- after any custom weak word (mostly conjunctions) by language
- inside custom text shortcuts (example: U. S.) by language
- between digits and it's configured units

## Example

Template code:
```php
<p><?php
	// Earth diameter: 6&nbsp;378&nbsp;km. (or&nbsp;6&nbsp;378&nbsp;000&nbsp;m)
	echo $this->LineBreaks(
		'Earth diameter: 6 378 km (or 6 378 000 m).'
	);
?></p>
```