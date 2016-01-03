<?php
/**
 * Created by PhpStorm.
 * User: Chad
 * Date: 6/24/2015
 * Time: 8:41 PM
 */

require_once('RateLimitHandler.php');

class RateLimitSleeper implements RateLimitHandler {

    private $debug = false;

    public function enableDebugging()
    {
        $this->debug = true;
    }

    /**
     * @param int $retryAfter Retry-After header returned by the api
     */
    public function handleLimit($retryAfter)
    {
        if ($this->debug) {
            var_dump('sleeping for: ' . $retryAfter . "\n");
        }


        /**
         * So this is our most basic handler for rate limiting... if we hit the cap, we'll simply sleep for
         * the recommended duration before continuting
         */
        sleep($retryAfter);
    }

    /**
     * @return bool returns whether or not to retry the api call after being handled
     */
    public function retryEnabled()
    {
        return true;
    }

}