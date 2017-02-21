<?php
namespace NearbyNotifier\Captcha;

interface Handler
{

    /**
     * Solve a captcha
     *
     * @param string $challenge
     *
     * @return string
     */
    public function solve(string $challenge) : string;
}
