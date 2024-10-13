<?php

namespace IBroStudio\PathSelectPrompt;

use IBroStudio\PathSelectPrompt\Themes\Default\PathSelectPromptRenderer;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Prompts\Concerns\Scrolling;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class PathSelectPrompt extends Prompt
{
    use Scrolling;

    /**
     * @var array<string, string>
     */
    public array $options;

    protected string $fieldLabel;

    /**
     * @var Collection<string, string>
     */
    protected Collection $tree;

    /**
     * @var Collection<int, int>
     */
    protected Collection $highlights;

    public function __construct(
        public string $label,
        protected string|false|null $root = null,
        public ?string $default = null,
        public string $target = 'directory',
        public int $scroll = 5,
        public mixed $validate = null,
        public string $hint = '',
        public bool|string $required = true,
    ) {
        static::$themes['default'] = [
            self::class => PathSelectPromptRenderer::class,
        ];

        if ($this->required === false) {
            throw new InvalidArgumentException('Argument [required] must be true or a string.');
        }

        $this->root = $this->root ?? getcwd();

        $this->fieldLabel = $this->label;

        $this->tree = Collection::make();

        $this->highlights = Collection::make();

        $this->buildOptions();

        if ($this->default) {
            $this->initializeScrolling(array_search($this->default, array_keys($this->options)) ?: 0);
            $this->scrollToHighlighted(count($this->options));
        } else {
            $this->initializeScrolling(0);
        }

        $this->on('key', fn ($key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::SHIFT_TAB, Key::CTRL_P, Key::CTRL_B => $this->highlightPrevious(count($this->options)),
            Key::DOWN, Key::DOWN_ARROW, Key::TAB, Key::CTRL_N, Key::CTRL_F => $this->highlightNext(count($this->options)),
            Key::oneOf([Key::HOME, Key::CTRL_A], $key) => $this->highlight(0),
            Key::oneOf([Key::END, Key::CTRL_E], $key) => $this->highlight(count($this->options) - 1),
            Key::RIGHT, Key::RIGHT_ARROW => $this->enterFolder(),
            Key::LEFT, Key::LEFT_ARROW => $this->exitFolder(),
            Key::ENTER => $this->submit(),
            default => $this->searchHighlight($key),
        });
    }

    protected function enterFolder(): void
    {
        if (
            ! is_null($this->value())
            && ($segment = Str::afterLast((string) $this->value(), '/')) !== $this->tree->last()
        ) {
            $this->highlights->push((int) $this->highlighted);
            $this->tree->push($segment);
            $this->label = $this->tree->implode('/');
            $this->buildOptions((string) $this->value());
            $this->highlight(0);
            $this->initializeScrolling(0);

            if (! count($this->options)) {
                $this->state = 'error';
                $this->error = 'empty';
            }

            $this->render();
        }
    }

    protected function exitFolder(): void
    {
        if ($this->tree->count()) {
            $this->tree->pop();
            $path = collect([$this->root])->merge($this->tree)->implode('/');
            $this->buildOptions($path);
            $this->highlight($this->highlights->last());
            $this->highlights->pop();
            $this->initializeScrolling($this->highlighted);
            $this->render();
        }

        if (! $this->tree->count()) {
            $this->label = $this->fieldLabel;
        } else {
            $this->label = $this->tree->implode('/');
        }
    }

    protected function buildOptions(?string $root = null): void
    {
        $root = $root ?? $this->root;

        $items = File::directories((string) $root);

        if ($this->target !== 'directory') {
            $files = File::files((string) $root);

            if ($this->target !== 'file') {
                $files = collect($files)
                    ->filter(fn ($file) => Str::endsWith($file, $this->target))
                    ->toArray();
            }

            $items = array_merge($items, $files);
        }

        if (count($items)) {
            $items = Arr::mapWithKeys($items, function (string $item) {
                return [$item => Str::afterLast($item, '/')];
            });

            $this->options = $items;
        } else {
            $this->options = [];
        }
    }

    protected function searchHighlight(string $search): void
    {
        $found = collect($this->options)
            ->flip()
            ->keys()
            ->flip()
            ->first(function (int $value, string $key) use ($search) {
                return Str::startsWith($key, [strtolower($search), strtoupper($search)]);
            });

        if (! is_null($found)) {
            $this->highlight($found);
        }
    }

    protected function submit(): void
    {
        if ($this->target !== 'directory') {
            if (
                ($this->target === 'file' && ! File::isFile((string) $this->value()))
                || ($this->target !== 'file' && ! Str::endsWith((string) $this->value(), $this->target))
            ) {
                $this->state = 'error';
                $this->error = 'This is not a '.$this->target;

                return;
            }
        }

        $this->label = $this->fieldLabel;

        parent::submit();
    }

    public function value(): int|string|null
    {
        if (static::$interactive === false) {
            return $this->default;
        }

        return array_keys($this->options)[$this->highlighted];
    }

    public function label(): ?string
    {
        return $this->options[array_keys($this->options)[$this->highlighted]] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function visible(): array
    {
        return array_slice($this->options, $this->firstVisible, $this->scroll, preserve_keys: true);
    }

    protected function isInvalidWhenRequired(mixed $value): bool
    {
        return $value === null;
    }
}
