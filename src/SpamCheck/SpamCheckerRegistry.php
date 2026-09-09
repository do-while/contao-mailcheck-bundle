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
 * Registriert die verfügbaren Prüf-Implementierungen (SpamCheckerInterface).
 *
 * Pro Formular kann im Backend unter "Formulareigenschaften" ausgewählt
 * werden, welche dieser Prüfungen laufen sollen (Default: keine – "Aus").
 *
 * Neue Prüfung hinzufügen: eigene Klasse gegen SpamCheckerInterface
 * implementieren, als Service registrieren und hier per add() eintragen
 * (siehe Resources/config/services.yaml) – sie erscheint dann automatisch
 * als Auswahl-Option im Backend.
 */
class SpamCheckerRegistry
{
    /** @var array<string, SpamCheckerInterface> */
    private array $checkers = [];

    /** @var array<string, string> */
    private array $labels = [];


    public function add( string $id, SpamCheckerInterface $checker, string $label ): void
    {
        $this->checkers[$id] = $checker;
        $this->labels[$id]   = $label;
    }


    public function get( string $id ): ?SpamCheckerInterface
    {
        return $this->checkers[$id] ?? null;
    }


    /**
     * @return array<string, string> id => Label, für das Backend-Auswahlfeld
     */
    public function getOptions( ): array
    {
        return $this->labels;
    }
}
