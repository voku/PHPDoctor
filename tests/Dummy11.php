<?php

declare(strict_types = 1);

namespace voku\tests;

trait Dummy11
{
    /**
     * @param numeric-string $param1
     *
     * @return void
     */
    public function test1(float $param1)
    {
    }

    /**
     * @param numeric-string $param1
     *
     * @return void
     */
    public function test2(string $param1)
    {
    }


    /**
     * @param class-string $param1
     *
     * @return void
     */
    public function test3(string $param1)
    {
    }

    /**
     * @param array<mixed> $date
     */
    public function sayHello($date): void
    {
        echo 'Hello, ' . $date[0]->format('j. n. Y');
    }
}
