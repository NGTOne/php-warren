<?php

namespace Warren;

interface ErrorHandler
{
    public function handle(\Throwable $error);
}
