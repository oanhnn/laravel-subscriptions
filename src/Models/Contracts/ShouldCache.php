<?php

namespace Laravel\Subscriptions\Models\Contracts;

/**
 * Interface ShouldCache
 *
 * @package     Laravel\Subscriptions\Models\Contracts
 * @author      Oanh Nguyen <oanhnn.bk@gmail.com>
 * @license     The MIT license
 */
interface ShouldCache
{
    /**
     * Determine if model cache clear is enabled.
     *
     * @return bool
     */
    public function isCacheClearEnabled(): bool;

    /**
     * Forget the model cache.
     *
     * @return void
     */
    public static function forgetCache();
}
