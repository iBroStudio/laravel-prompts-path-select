<?php

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function IBroStudio\PathSelectPrompt\pathselect;
use function Laravel\Prompts\form;
use function Laravel\Prompts\select;

it('can select a path directory', function () {
    Prompt::fake([Key::DOWN, Key::ENTER]);
    $result = pathselect('Select a directory');

    expect($result)->toBe(getcwd().'/src');
});

it('can accept a root', function () {
    Prompt::fake([Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a directory',
        root: base_path(),
    );

    expect($result)->toBe(base_path('bootstrap'));
});

it('can accept a default value', function () {
    Prompt::fake([Key::ENTER]);
    $result = pathselect(
        label: 'Select a directory',
        default: getcwd().'/tests',
    );

    expect($result)->toBe(getcwd().'/tests');
});

it('can target a file', function () {
    Prompt::fake([Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: 'file',
    );

    expect($result)->toBe(getcwd().'/CHANGELOG.md');
});

it('can target an extension', function () {
    Prompt::fake([Key::DOWN, Key::DOWN, Key::DOWN, Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: '.json',
    );

    expect($result)->toBe(getcwd().'/composer.json');
});

it('can navigate to a directory', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::LEFT, Key::DOWN, Key::RIGHT, Key::ENTER]);
    $result = pathselect('Select a directory');

    expect($result)->toBe(getcwd().'/src/Themes/Default');
});

it('can navigate to a file', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::RIGHT, Key::DOWN, Key::ENTER]);
    $result = pathselect(
        label: 'Select a file',
        target: 'file',
    );

    expect($result)->toBe(getcwd().'/src/Themes/Default/PathSelectPromptRenderer.php');
});

it('allows to search item by its first letter', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, 't', Key::ENTER]);
    $result = pathselect('Select a directory');

    expect($result)->toBe(getcwd().'/src/Themes');
});

it('displays the current directory', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::RIGHT, Key::ENTER]);
    pathselect(
        label: 'Select a file',
        target: 'file',
    );

    Prompt::assertOutputContains('src/Themes/Default');
});

it('warns if there is no item to select', function () {
    Prompt::fake([Key::DOWN, Key::RIGHT, Key::RIGHT, Key::RIGHT, Key::LEFT, Key::ENTER]);
    $result = pathselect('Select a directory');

    Prompt::assertOutputContains('empty');

    expect($result)->toBe(getcwd().'/src/Themes/Default');
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

it('can use a pathselect with a other Prompts elements', function () {
    Prompt::fake([Key::DOWN, Key::ENTER, Key::DOWN, Key::ENTER]);
    $pathselect = pathselect('Select a directory');

    select(
        label: 'Use select?',
        options: [
            false => 'no',
            true => 'yes',
        ]
    );

    expect($pathselect)->toBe(getcwd().'/src');
});

it('can use pathselect in a Prompts form', function () {
    Prompt::fake([Key::DOWN, Key::ENTER, Key::DOWN, Key::ENTER]);

    $responses = form()
        ->add(function () {
            return pathselect('Select a directory');
        }, name: 'directory')
        ->select(
            label: 'Use select?',
            options: [
                false => 'no',
                true => 'yes',
            ],
            name: 'select'
        )
        ->submit();

    expect($responses)->toMatchArray([
        'directory' => getcwd().'/src',
        'select' => 'yes',
    ]);
});
