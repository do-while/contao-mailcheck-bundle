<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

$GLOBALS['TL_LANG']['tl_form']['mailcheck_checks'] = ['Spam-Prüfungen', 'Wählen Sie aus, welche Prüfungen vor dem Mailversand laufen sollen. Ohne Auswahl (Standard) findet keine Prüfung durch dieses Bundle statt.'];
$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode']   = ['Verhalten bei Spam-Verdacht', 'Legt fest, was passiert, wenn eine der ausgewählten Prüfungen anschlägt.'];

$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode_']['discard'] = 'Mail verwerfen (still, ohne Fehlermeldung für den Absender)';
$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode_']['mark']    = 'Mail trotzdem senden, Betreff mit "MAILSPAM!" markieren';

$GLOBALS['TL_LANG']['tl_form']['mailcheck_legend'] = 'Spam-Prüfung';
