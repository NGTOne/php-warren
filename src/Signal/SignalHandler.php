<?php

namespace Warren\Signal;

use Warren\Signal\Error\InvalidSignal;

use Seld\Signal\SignalHandler as SeldHandler;

abstract class SignalHandler
{
    private $receivedSignals = [];
    private $handler;

    public function __construct(array $signals)
    {
        // Some validation, a little more aggressive than in Seld's
        // library
        foreach ($signals as $signal) {
            if (!is_string($signal) and !is_int($signal)) {
                throw new InvalidSignal($signal);
            }
        }

        $this->handler = SeldHandler::create(
            $signals,
            // Wrap it in an anon function so it can stay private
            function ($signo, $signame) {
                $this->handleSignal($signo, $signame);
            }
        );
    }

    private function handleSignal($signo, $signame) : void
    {
        $this->receivedSignals[$signo] = $signame;
    }

    protected function receivedSignals() : array
    {
        return $this->receivedSignals;
    }

    private function reset() : void
    {
        $this->receivedSignals = [];
    }

    public function handleReceivedSignals() : void
    {
        if ($this->handler->isTriggered()) {
            $this->handleSignals();
            $this->reset();
            $this->handler->reset();
        }
    }

    abstract protected function handleSignals() : void;
}
