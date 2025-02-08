<?php

namespace Nazmulpcc\HnPhpQt;

use React\Http\Browser;
use React\Promise\PromiseInterface;

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
            ->then(function ($response) {
                return json_decode($response->getBody(), true);
            });
    }
}