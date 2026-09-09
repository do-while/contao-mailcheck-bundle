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


use Contao\Form;
use Contao\StringUtil;
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
        if( !$this->isSpam( $activeChecks, $submittedData ) ) return;

        if( ( $form->mailcheck_mode ?: self::MODE_DISCARD ) === self::MODE_MARK ) {
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


    private function isSpam( array $activeChecks, array $submittedData ): bool
    {
        foreach( $activeChecks as $checkId ) {
            $checker = $this->registry->get( $checkId );

            if( $checker !== null && $checker->isSpam( $submittedData ) ) return true;
        }

        return false;
    }
}
