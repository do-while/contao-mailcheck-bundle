<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

use Softleister\ContaoMailcheckBundle\EventListener\MailcheckListener;

/*
 * Hook: läuft vor dem Mailversand, siehe Contao\Form::processFormData()
 * (der Hook "processFormData" selbst läuft erst NACH dem Mailversand
 * und eignet sich daher nicht zum stillen Blocken).
 */
$GLOBALS['TL_HOOKS']['prepareFormData'][] = [MailcheckListener::class, 'onPrepareFormData'];

/*
 * Hook von Notification Center 1.x (library/NotificationCenter/Model/Message.php):
 * läuft pro versendeter NC-Nachricht, return false storniert sie. Wird
 * genutzt, um NC-Benachrichtigungen bei erkanntem Spam zu unterdrücken
 * (das Feld nc_notification wirkt bei NC 1.x anders als bei NC 2.x nicht).
 */
$GLOBALS['TL_HOOKS']['sendNotificationMessage'][] = [MailcheckListener::class, 'onSendNotificationMessage'];

/*
 * Notification Center 1.x prüft beim Speichern einer Nachricht die
 * verwendeten Tokens gegen eine fest hinterlegte Whitelist pro
 * Gruppe, Notification-Typ und Feld (verifiziert in
 * library/NotificationCenter/AutoSuggester.php::verifyTokens(), dort
 * $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'][$strGroup][$strType]
 * mit $strGroup = NotificationModel::findGroupForType($strType)). Für den
 * eingebauten Typ 'core_form' (Formular-Benachrichtigungen) liegt diese
 * Gruppe bei 'contao' (live an Hagens Installation bestätigt: Struktur ist
 * $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['contao']['core_form'],
 * die Gruppe dient in NCs Backend-Auswahl als Kategorie-Überschrift). Für
 * den Betreff ist dort z.B. nur 'form_*', 'formconfig_*' und 'admin_email'
 * erlaubt - ein frei erfundener Token wie ##mailcheck_marker## würde beim
 * Speichern der NC-Nachricht mit "... werden vom Notification-Typ nicht
 * unterstützt" abgelehnt.
 *
 * Da dieses Array ein normales PHP-Array in $GLOBALS ist, können wir es
 * um unseren eigenen Token 'mailcheck_marker' erweitern (dieselbe Technik,
 * die Notification Center selbst für eigene Erweiterungen/Notification-
 * Typen vorsieht). Wichtig: Dieser Code muss NACH dem config.php von
 * Notification Center laufen, sonst existiert das Array hier noch nicht -
 * sichergestellt über setLoadAfter(['notification_center']) in
 * src/ContaoManager/Plugin.php. Die isset()-Prüfung ist trotzdem als
 * Absicherung vorhanden, falls NC 1.x aus irgendeinem Grund nicht (mehr)
 * installiert ist oder den Typ 'core_form' nicht (mehr unter dieser
 * Gruppe) registriert.
 */
if( isset( $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['contao']['core_form'] ) ) {
    foreach( ['email_subject', 'email_text', 'email_html'] as $field ) {
        if( isset( $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['contao']['core_form'][$field] ) ) {
            $GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['contao']['core_form'][$field][] = 'mailcheck_marker';
        }
    }
}
