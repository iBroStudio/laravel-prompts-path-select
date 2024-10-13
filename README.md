# Laravel Prompts Path Select

This package is a small add-on to [Laravel Prompts](https://github.com/laravel/prompts) to provide the ability to select the path to a directory or file.

## Installation

You can install the package via composer:

```bash
composer require ibrostudio/laravel-prompts-path-select
```

## Usage

```php
use function IBroStudio\PathSelectPrompt\pathselect;
 
$directory = pathselect('Select a directory');
```

### Usual Prompts options

```php
$directory = pathselect(
    label: 'Select a directory',
    hint: 'Use right and left arrows to navigate in folders',
    required: true,
);
```

### Starting root
You can choose the folder from which the selection starts:

```php
$directory = pathselect(
    label: 'Select a directory',
    root: base_path(),
);
```

### Default selected folder
You can choose the folder from which the selection starts:

```php
$directory = pathselect(
    label: 'Select a directory',
    root: base_path(),
    default: base_path('vendor'),
);
```

### Target file
By default, the selector display directories. If you want, you can target a file:

```php
$file = pathselect(
    label: 'Select a file',
    root: base_path(),
    target: 'file',
);
```

### Target extension
You can also target an extension:

```php
$file = pathselect(
    label: 'Select a JSON file',
    root: base_path(),
    target: '.json',
);
```

### Navigation
You navigate in directories with right and left arrows.

**You can press the first letter of a folder or file to quickly select it.**

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
