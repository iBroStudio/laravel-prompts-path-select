<?php

use Illuminate\Support\Str;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function IBroStudio\PathSelectPrompt\pathselect;

it('can select a path directory', function () {
    Prompt::fake([Key::DOWN, Key::ENTER]);
    $result = pathselect('Select a directory');

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/src');
});

it('can accept a root', function () {
    Prompt::fake([Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a directory',
        root: base_path(),
    );

    expect(
        Str::after($result, base_path())
    )->toBe('/app');
});

it('can accept a default value', function () {
    Prompt::fake([Key::ENTER]);
    $result = pathselect(
        label: 'Select a directory',
        default: getcwd().'/tests',
    );

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/tests');
});

it('can target a file', function () {
    Prompt::fake([Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: 'file',
    );

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/CHANGELOG.md');
});

it('can target an extension', function () {
    Prompt::fake([Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: '.json',
    );

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/composer.json');
});

it('can navigate to a directory', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::LEFT, Key::DOWN, Key::RIGHT, Key::ENTER]);
    $result = pathselect('Select a directory');

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/src/Themes/Default');
});

it('can navigate to a file', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: 'file',
    );

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/src/Concerns/PathSelectThemes.php');
});

it('allows to search item by its first letter', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, 't', Key::ENTER]);
    $result = pathselect('Select a directory');

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/src/Themes');
});

it('displays the current directory', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::DOWN, Key::RIGHT, Key::RIGHT, Key::ENTER]);
    pathselect(
        label: 'Select a file',
        target: 'file',
    );

    Prompt::assertOutputContains('src/Themes/Default');
});

it('warns if there is no item to select', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::LEFT, Key::ENTER]);
    $result = pathselect('Select a directory');

    Prompt::assertOutputContains('empty');

    expect(
        Str::after($result, (string) getcwd())
    )->toBe('/src/Concerns');
});

it('warns if selection is not a file', function () {
    Prompt::fake([Key::DOWN, Key::ENTER, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: 'file',
    );

    Prompt::assertOutputContains('This is not a file');
});

it('warns if selection has not the requested extension', function () {
    Prompt::fake([Key::ENTER, Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER]);
    pathselect(
        label: 'Select a file',
        target: '.json',
    );

    Prompt::assertOutputContains('This is not a .json');
});

it('can display a hint', function () {
    Prompt::fake([Key::DOWN, Key::ENTER]);
    pathselect(
        label: 'Select a directory',
        hint: 'Where you wan to generate the file',
    );

    Prompt::assertOutputContains('Where you wan to generate the file');
});
