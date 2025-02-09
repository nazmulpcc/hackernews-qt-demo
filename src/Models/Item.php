<?php

namespace Nazmulpcc\HnPhpQt\Models;

use Evenement\EventEmitter;
use Nazmulpcc\HnPhpQt\HackerNewsClient;

/**
 * @property-read string $by
 * @property-read int $descendants
 * @property-read int $id
 * @property-read array $kids
 * @property-read int $score
 * @property-read int $time
 * @property-read string $title
 * @property-read string $type
 * @property-read string $url
 * @property-read string $text
 * @property-read bool $deleted
 */
class Item extends EventEmitter
{
    private array $children = [];

    public function __construct(private readonly array $attributes)
    {
    }

    public function __get(string $name)
    {
        if (method_exists($this, $method = 'get' . ucfirst($name))) {
            return $this->$method();
        }

        return $this->attributes[$name] ?? null;
    }

    public function loadChildren(): self
    {
        $client = new HackerNewsClient();

        foreach (($this->kids ?: []) as $index => $kid) {
            $client->getItem($kid)
                ->then(function (Item $item) use ($index) {
                    $this->children[$index] = $item->on('updated', function () {
                        $this->emit('updated');
                    })->loadChildren();

                    $this->children[$index]->emit('updated');
                });
        }

        return $this;
    }

    /**
     * @return self[]
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @return self[]
     */
    public function comments(): array
    {
        return array_filter($this->children, fn(self $child) => !$child->deleted && $child->type === 'comment');
    }

    public function commentCount(): int
    {
        $count = 0;
        foreach ($this->comments() as $comment) {
            $count += 1 + $comment->commentCount();
        }
        return $count;
    }
}