<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

namespace Softleister\ContaoMailcheckBundle\SpamCheck;

/**
 * Eigene Prüf-Regeln: diese Schnittstelle implementieren und den
 * Service-Alias in Resources/config/services.yaml (bzw. im eigenen
 * Projekt) auf die eigene Klasse umbiegen.
 */
interface SpamCheckerInterface
{
    /**
     * @param array $submittedData Vom Besucher übermittelte Formulardaten (Feldname => Wert)
     */
    public function isSpam( array $submittedData ): bool;
}
