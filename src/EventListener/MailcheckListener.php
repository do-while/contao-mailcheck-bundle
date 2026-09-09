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
 * Hook-Listener für TL_HOOKS['prepareFormData'] und TL_HOOKS['sendNotificationMessage'].
 *
 * HINWEIS: Dies ist der Contao-4.13-Zweig (siehe composer.json:
 * contao/core-bundle ^4.13, terminal42/notification_center ^1.7 als
 * feste Abhängigkeit). Für Contao 5.x mit Notification Center 2.x
 * (komplett andere, Symfony-basierte Architektur) gibt es einen
 * separaten Branch mit eigener NC-Integration.
 *
 * prepareFormData läuft VOR dem Mailversand (siehe
 * Contao\Form::processFormData()) und damit auch vor dem Hook
 * 'processFormData', an den sich u.a. das Notification Center hängt.
 * Die Bundle-Ladereihenfolge spielt dabei KEINE Rolle: prepareFormData
 * und processFormData sind zwei unterschiedliche, in
 * Contao\Form::processFormData() fest verdrahtete Zeitpunkte –
 * prepareFormData läuft immer vor processFormData, egal in welcher
 * Reihenfolge die Bundles geladen werden.
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

    private SpamCheckerRegistry $registry;

    /**
     * Wird in onPrepareFormData() gesetzt, wenn die aktuelle Anfrage als
     * Spam erkannt wurde (MODE_DISCARD) - ausgewertet in
     * onSendNotificationMessage(), um Notification-Center-1.x-Nachrichten
     * für dieselbe Anfrage zu unterdrücken. Pro Request nur EIN
     * Formular anzunehmen ist im normalen Contao-Ablauf (Seitenaufruf =
     * ein Formular-POST) eine sichere Annahme.
     */
    private bool $suppressNcMessages = false;

    /**
     * Wird in onPrepareFormData() gesetzt, wenn die aktuelle Anfrage im
     * Modus MODE_MARK als Spam erkannt wurde - ausgewertet in
     * onSendNotificationMessage(), um in Notification-Center-1.x-Nachrichten
     * per Token ##mailcheck_marker## eine Spam-Markierung einzufügen. Der
     * Token muss von Hagen einmalig manuell in das jeweilige NC-Nachrichten-
     * Template (z.B. im Betreff) eingetragen werden, siehe DOKUMENTATION.md.
     */
    private bool $markNcMessages = false;


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

            // Notification Center 1.x markieren: eigene Betreff-/Text-Templates
            // liegen komplett in NC selbst (Tokens ##...##), $form->subject wirkt
            // dort NICHT. Daher wird in onSendNotificationMessage() zusätzlich
            // der Token ##mailcheck_marker## in $cpTokens eingefügt.
            $this->markNcMessages = true;
            return;
        }

        // MODE_DISCARD (Standard): Mail und DB-Speicherung still unterdrücken
        $form->sendViaEmail = false;
        $form->storeValues  = false;

        // Notification Center 1.x unterdrücken: das Feld nc_notification
        // wirkt bei NC 1.x NICHT (anders als bei NC 2.x) - stattdessen
        // wird jede über NC versendete Nachricht in onSendNotificationMessage()
        // abgefangen, solange dieses Flag für die aktuelle Anfrage gesetzt ist.
        $this->suppressNcMessages = true;
    }


    /**
     * Hook-Callback für TL_HOOKS['sendNotificationMessage'] (Notification
     * Center 1.x, library/NotificationCenter/Model/Message.php). Wird pro
     * versendeter Nachricht aufgerufen; return false storniert genau
     * diese eine Nachricht. $objMessage/$objGatewayModel bewusst ohne
     * Typehint, da NC 1.x eigene Modellklassen sind und wir sie hier
     * inhaltlich nicht auswerten müssen.
     *
     * $cpTokens wird bewusst per Referenz angenommen (&$cpTokens): der
     * Hook wird in Message::send() mit einer lokalen Kopie $cpTokens
     * aufgerufen, die anschließend unverändert an $objGateway->send()
     * weitergereicht wird - Änderungen wirken sich also nur auf die
     * gerade zu versendende Nachricht aus. Der Token ##mailcheck_marker##
     * wird hier IMMER gesetzt (MARK_PREFIX oder Leerstring) - NC ersetzt
     * nur Tokens, die tatsächlich in $cpTokens vorhanden sind, ein nicht
     * gesetzter Token bliebe im Betreff als Klartext ##mailcheck_marker##
     * stehen (von Hagen live beobachtet). Er muss von Hagen manuell in
     * das jeweilige NC-Nachrichten-Template eingetragen werden, damit die
     * Markierung im NC-Versand sichtbar wird (siehe DOKUMENTATION.md).
     */
    public function onSendNotificationMessage( $objMessage, array &$cpTokens, string $cpLanguage, $objGatewayModel ): bool
    {
        $cpTokens['mailcheck_marker'] = $this->markNcMessages ? self::MARK_PREFIX : '';

        return !$this->suppressNcMessages;
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
