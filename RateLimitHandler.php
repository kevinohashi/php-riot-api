<?php
/**
 * Created by PhpStorm.
 * User: Chad
 * Date: 6/24/2015
 * Time: 8:37 PM
 */

interface RateLimitHandler {

    /**
     * @param int $retryAfter Retry-After header returned by the api
     */
    public function handleLimit($retryAfter);

    /**
     * @return bool returns whether or not to retry the api call after being handled
     */
    public function retryEnabled();

}