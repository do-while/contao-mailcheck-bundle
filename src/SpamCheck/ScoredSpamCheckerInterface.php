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
 * Optionale Zusatz-Schnittstelle für Prüfungen, die einen numerischen
 * Score liefern können. Nicht jede Prüfung muss diese Schnittstelle
 * implementieren – Prüfungen ohne Score erscheinen im Spam-Log-Eintrag
 * dann einfach ohne Score-Angabe.
 */
interface ScoredSpamCheckerInterface extends SpamCheckerInterface
{
    /**
     * Score der zuletzt per isSpam() geprüften Formulardaten.
     */
    public function getScore( ): int;
}
