<?php

namespace Nazmulpcc\HnPhpQt;

use Nazmulpcc\HnPhpQt\Models\Item;
use React\Http\Browser;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use function React\Promise\all;

class HackerNewsClient
{
    protected Browser $http;

    public function __construct()
    {
        $this->http = new Browser();
    }

    public function getTopStories(): PromiseInterface
    {
        return $this->http->get('https://hacker-news.firebaseio.com/v0/topstories.json')
            ->then(function ($response) {
                return json_decode($response->getBody(), true);
            });
    }

    public function getItem(int $id): PromiseInterface
    {
        return $this->http->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json")
            ->then(function (Response $response) {
                return new Item(json_decode($response->getBody(), true));
            });
    }
}