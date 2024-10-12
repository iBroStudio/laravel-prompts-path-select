#!/usr/bin/env php
<?php

function ask(string $question, string $default = ''): string
{
    $answer = readline($question.($default ? " ({$default})" : null).': ');

    if (! $answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question.' ('.($default ? 'Y/n' : 'y/N').')');

    if (! $answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function writeln(string $line): void
{
    echo $line.PHP_EOL;
}

function run(string $command): string
{
    return trim((string) shell_exec($command));
}

function slugify(string $subject): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function title_case(string $subject): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $subject)));
}

function title_snake(string $subject, string $replace = '_'): string
{
    return str_replace(['-', '_'], $replace, $subject);
}

function replace_in_file(string $file, array $replacements): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        str_replace(
            array_keys($replacements),
            array_values($replacements),
            $contents
        )
    );
}

function remove_prefix(string $prefix, string $content): string
{
    if (str_starts_with($content, $prefix)) {
        return substr($content, strlen($prefix));
    }

    return $content;
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function replaceForWindows(): array
{
    return preg_split('/\\r\\n|\\r|\\n/', run('dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i '.basename(__FILE__).' | findstr /r /i /M /F:/ ":author :vendor :package VendorName skeleton migration_table_name vendor_name vendor_slug author@domain.com"'));
}

function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i ":author|:vendor|:package|VendorName|skeleton|migration_table_name|vendor_name|vendor_slug|author@domain.com" --exclude-dir=vendor ./* | grep -v '.basename(__FILE__)));
}

function makePackageName(string $folderName): string
{
    $segments = explode('-', trim($folderName));
    foreach ($segments as $index => $segment) {
        $segments[$index] = ucfirst($segment);
    }

    return implode(' ', $segments);
}

$authorName = 'iBroStudio';
$authorUsername = 'iBroStudio';
$authorEmail = 'hello@ibro.studio';
$vendorName = 'iBroStudio';
$vendorSlug = 'ibrostudio';
$vendorNamespace = 'IBroStudio';

$currentDirectory = getcwd();
$folderName = basename($currentDirectory);

$packageName = ask('Package name', makePackageName($folderName));
$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('laravel-', $packageSlug);

$className = title_case(trim(remove_prefix('Laravel', $packageName)));
$className = ask('Class name', $className);
$variableName = lcfirst($className);
$description = ask('Package description', "{$packageName}");

writeln('------');
writeln("Author     : {$authorName} ({$authorUsername}, {$authorEmail})");
writeln("Vendor     : {$vendorName} ({$vendorSlug})");
writeln("Package    : {$packageSlug} <{$description}>");
writeln("Namespace  : {$vendorNamespace}\\{$className}");
writeln("Class name : {$className}");
writeln('---');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (! confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());

foreach ($files as $file) {
    replace_in_file($file, [
        ':author_name' => $authorName,
        ':author_username' => $authorUsername,
        'author@domain.com' => $authorEmail,
        ':vendor_name' => $vendorName,
        ':vendor_slug' => $vendorSlug,
        'VendorName' => $vendorNamespace,
        ':package_name' => $packageName,
        ':package_slug' => $packageSlug,
        ':package_slug_without_prefix' => $packageSlugWithoutPrefix,
        'Skeleton' => $className,
        'skeleton' => $packageSlug,
        'migration_table_name' => title_snake($packageSlug),
        'variable' => $variableName,
        ':package_description' => $description,
    ]);

    match (true) {
        str_contains($file, determineSeparator('src/Skeleton.php')) => rename($file, determineSeparator('./src/'.$className.'.php')),
        str_contains($file, determineSeparator('src/SkeletonServiceProvider.php')) => rename($file, determineSeparator('./src/'.$className.'ServiceProvider.php')),
        str_contains($file, determineSeparator('src/Facades/Skeleton.php')) => rename($file, determineSeparator('./src/Facades/'.$className.'.php')),
        str_contains($file, determineSeparator('src/Commands/SkeletonCommand.php')) => rename($file, determineSeparator('./src/Commands/'.$className.'Command.php')),
        str_contains($file, determineSeparator('tests/Commands/SkeletonCommandTest.php')) => rename($file, determineSeparator('./tests/Commands/'.$className.'CommandTest.php')),
        str_contains($file, determineSeparator('database/migrations/create_skeleton_table.php.stub')) => rename($file, determineSeparator('./database/migrations/create_'.title_snake($packageSlugWithoutPrefix).'_table.php.stub')),
        str_contains($file, determineSeparator('config/skeleton.php')) => rename($file, determineSeparator('./config/'.$packageSlugWithoutPrefix.'.php')),
        default => [],
    };
}

confirm('Execute `composer install` and run tests?', true) && run('composer install && composer test');

confirm('Let this script delete itself?', true) && unlink(__FILE__);
