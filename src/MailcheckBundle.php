<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

namespace Softleister\ContaoMailcheckBundle;


use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures the Contao Mailcheck bundle.
 */
class MailcheckBundle extends Bundle
{
    public function getPath( ): string
    {
        return \dirname( __DIR__ );
    }
}
