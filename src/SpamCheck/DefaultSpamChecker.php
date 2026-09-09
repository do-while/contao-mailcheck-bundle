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
 * Beispiel-Implementierung: Scoring-basierte Erkennung typischer
 * Bot-Muster (zufällige, sinnlose Zeichenketten in Formularfeldern).
 *
 * Ab einem Score von self::THRESHOLD Punkten gilt die Eingabe als Spam.
 *
 * Eigene Regeln: diese Klasse erweitern und spamScore() überschreiben,
 * oder eine eigene Klasse gegen SpamCheckerInterface implementieren.
 */
class DefaultSpamChecker implements ScoredSpamCheckerInterface
{
    protected const THRESHOLD = 4;

    /** Felder, deren Wert nicht geprüft wird (z.B. echte E-Mail-Adressen, technische Felder) */
    protected const IGNORE_FIELDS = ['email', 'e_mail', 'FORM_SUBMIT', 'REQUEST_TOKEN'];

    /** Score der zuletzt geprüften Formulardaten (für getScore()) */
    private int $lastScore = 0;

    public function isSpam( array $submittedData ): bool
    {
        $this->lastScore = $this->spamScore( $submittedData );

        return $this->lastScore >= static::THRESHOLD;
    }


    public function getScore( ): int
    {
        return $this->lastScore;
    }


    protected function spamScore( array $submittedData ): int
    {
        $score = 0;
        $combined = '';

        foreach( $submittedData as $key => $value ) {
            if( !\is_string( $value ) || \in_array( $key, static::IGNORE_FIELDS, true ) ) continue;
            if( $value === '' || filter_var( $value, FILTER_VALIDATE_EMAIL ) ) continue;

            $combined .= ' ' . $value;

            // Mehrfache Groß-/Kleinwechsel im Wort (CamelCase-artig, >=3 Wechsel)
            if( preg_match( '/\b[A-Za-z]*(?:[a-z][A-Z][A-Za-z]*){3,}\b/', $value ) ) $score += 3;

            // Wert ohne jedes Leerzeichen
            if( !preg_match( '/\s/', $value ) ) {
                $length = mb_strlen( $value );
                if( $length > 15 )       $score += 3;
                else if( $length > 12 )  $score += 2;
            }
        }

        // Lange Konsonantenkette (>=6 Zeichen am Stück) über alle Felder hinweg
        if( preg_match( '/[bcdfghjklmnpqrstvwxz]{6,}/i', $combined ) ) $score += 2;

        return $score;
    }
}
