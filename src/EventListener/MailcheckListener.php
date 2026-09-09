<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

namespace Softleister\ContaoMailcheckBundle\EventListener;


use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Form;
use Contao\StringUtil;
use Contao\System;
use Softleister\ContaoMailcheckBundle\SpamCheck\ScoredSpamCheckerInterface;
use Softleister\ContaoMailcheckBundle\SpamCheck\SpamCheckerRegistry;

/**
 * Hook-Listener für TL_HOOKS['prepareFormData'].
 *
 * Läuft VOR dem Mailversand (siehe Contao\Form::processFormData()) und
 * damit auch vor dem Hook 'processFormData', an den sich u.a. das
 * Notification Center hängt. Die Bundle-Ladereihenfolge spielt dabei
 * KEINE Rolle: prepareFormData und processFormData sind zwei
 * unterschiedliche, in Contao\Form::processFormData() fest verdrahtete
 * Zeitpunkte – prepareFormData läuft immer vor processFormData, egal in
 * welcher Reihenfolge die Bundles geladen werden.
 *
 * Geprüft wird nur, wenn im Formular unter "Formulareigenschaften"
 * mindestens eine Prüfung ausgewählt wurde (Feld mailcheck_checks,
 * Contao-Backend). Default: keine Auswahl = Aus. Das Verhalten bei
 * erkanntem Spam (verwerfen/markieren) wird ebenfalls im Formular
 * eingestellt (Feld mailcheck_mode).
 */
class MailcheckListener
{
    private const MODE_DISCARD = 'discard';
    private const MODE_MARK    = 'mark';

    private const MARK_PREFIX = 'MAILSPAM! ';

    /** Bundle-Klasse des Notification Center, falls installiert */
    private const NOTIFICATION_CENTER_BUNDLE = 'Terminal42\\NotificationCenterBundle\\NotificationCenterBundle';

    private SpamCheckerRegistry $registry;


    public function __construct( SpamCheckerRegistry $registry )
    {
        $this->registry = $registry;
    }


    public function onPrepareFormData( array &$submittedData, array $labels, array $fields, Form $form ): void
    {
        $activeChecks = StringUtil::deserialize( $form->mailcheck_checks, true );

        if( empty( $activeChecks ) ) return;        // Aus (Default)

        $triggered = $this->findTriggeredChecks( $activeChecks, $submittedData );

        if( empty( $triggered ) ) return;

        $mode = ( $form->mailcheck_mode ?: self::MODE_DISCARD );

        $this->logSpam( $form, $submittedData, $mode, $triggered );

        if( $mode === self::MODE_MARK ) {
            $form->subject = self::MARK_PREFIX . $form->subject;

            if( isset( $submittedData['subject'] ) ) {
                $submittedData['subject'] = self::MARK_PREFIX . $submittedData['subject'];
            }
            return;
        }

        // MODE_DISCARD (Standard): Mail und DB-Speicherung still unterdrücken
        $form->sendViaEmail = false;
        $form->storeValues  = false;

        // Notification Center (terminal42/notification_center) ebenfalls
        // unterdrücken, sofern installiert. Setzt das von NC ausgewertete
        // Feld nc_notification zurück (Achtung: nicht am NC-Quellcode
        // verifiziert, siehe DOKUMENTATION.md – bitte mit einer echten
        // NC-Konfiguration testen).
        if( \class_exists( self::NOTIFICATION_CENTER_BUNDLE ) ) {
            $form->nc_notification = 0;
        }
    }


    /**
     * Führt alle aktiven Prüfungen aus (keine Kurzschluss-Auswertung wie
     * vorher, da mehrere Prüfungen gleichzeitig aktiv sein können und
     * alle ausgelösten Prüfungen im Log-Eintrag erscheinen sollen).
     *
     * @return array<int, array{id: string, label: string, score: int|null}>
     */
    private function findTriggeredChecks( array $activeChecks, array $submittedData ): array
    {
        $triggered = [];

        foreach( $activeChecks as $checkId ) {
            $checker = $this->registry->get( $checkId );

            if( $checker === null ) continue;
            if( !$checker->isSpam( $submittedData ) ) continue;

            $triggered[] = [
                'id'    => $checkId,
                'label' => $this->registry->getLabel( $checkId ) ?: $checkId,
                'score' => ( $checker instanceof ScoredSpamCheckerInterface ) ? $checker->getScore() : null,
            ];
        }

        return $triggered;
    }


    private function logSpam( Form $form, array $submittedData, string $mode, array $triggered ): void
    {
        $checkParts = [];

        foreach( $triggered as $check ) {
            $checkParts[] = $check['label'] . ( $check['score'] !== null ? ' (Score: ' . $check['score'] . ')' : '' );
        }

        $fieldParts = [];

        foreach( $submittedData as $key => $value ) {
            if( \is_array( $value ) ) $value = \implode( ', ', $value );

            $fieldParts[] = $key . '="' . $value . '"';
        }

        System::getContainer()->get( 'monolog.logger.contao.forms' )->info(
            self::MARK_PREFIX . 'Form "' . $form->title . '" wurde als Spam erkannt (Modus: ' . $mode . ', Prüfung(en): ' . \implode( ', ', $checkParts ) . '). Felder: ' . \implode( ', ', $fieldParts ),
            ['contao' => new ContaoContext( __METHOD__, ContaoContext::FORMS )]
        );
    }
}
