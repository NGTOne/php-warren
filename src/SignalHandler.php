<?php

namespace Warren;

interface SignalHandler
{
    public function handleSignal(int $signo, string $signame) : void;
}
