<?php

namespace Warren\Signal;

use Warren\Signal\Error\InvalidSignal;

use Seld\Signal\SignalHandler as SeldHandler;

abstract class SignalHandler
{
    private $receivedSignals = [];
    private $handler;
    private $existingSignalHandlers = [];

    public function __construct(array $signals)
    {
        // Some validation, a little more aggressive than in Seld's
        // library
        foreach ($signals as $signal) {
            if (!is_string($signal) and !is_int($signal)) {
                throw new InvalidSignal($signal);
            }
        }

        $this->targetSignals = $signals;
    }

    private function getExistingHandlers()
    {
        $existingHandlers = [];

        foreach ($this->targetSignals as $signal) {
            if (is_string($signal)) {
                $signal = constant($signal);
            }

            $existingHandlers[$signal] = pcntl_signal_get_handler(
                $signal
            );
        }

        return $existingHandlers;
    }

    public function enable()
    {
        $this->existingSignalHandlers = $this->getExistingHandlers();

        $this->handler = SeldHandler::create(
            $this->targetSignals,
            // Wrap it in an anon function so it can stay private
            function ($signo, $signame) {
                $this->handleSignal($signo, $signame);
            }
        );
    }

    public function disable()
    {
        foreach ($this->existingSignalHandlers as $signo => $handler) {
            pcntl_signal($signo, $handler);
        }

        $this->existingSignalHandlers = [];
        unset($this->handler);
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
        if ($this->handler and $this->handler->isTriggered()) {
            $this->handleSignals();
            $this->reset();
            $this->handler->reset();
        }
    }

    abstract protected function handleSignals() : void;
}
