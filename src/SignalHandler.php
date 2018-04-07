<?php

namespace Warren;

use Warren\Error\InvalidSignal;

use Seld\Signal\SignalHandler as SeldHandler;

abstract class SignalHandler
{
    private $receivedSigNos = [];
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
            function ($signo, $signame) {
                $this->handleSignal($signo, $signame);
            }
        );
    }

    private function handleSignal($signo, $signame) : void
    {
        $this->receivedSigNos[] = $signo;
        $this->receivedSignals[] = $signame;
    }

    protected function receivedSigNames() : array
    {
        return $this->receivedSignals;
    }

    protected function receivedSigNumbers() : array
    {
        return $this->receivedSigNos;
    }

    private function reset() : void
    {
        $this->receivedSignals = [];
        $this->receivedSigNos = [];
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
