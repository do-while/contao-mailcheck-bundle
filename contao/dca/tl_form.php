<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;
use Softleister\ContaoMailcheckBundle\SpamCheck\SpamCheckerRegistry;


// Legend + Felder in die Standard-Palette von tl_form einhängen
PaletteManipulator::create()
    ->addLegend( 'mailcheck_legend', 'email_legend', PaletteManipulator::POSITION_BEFORE )
    ->addField( 'mailcheck_checks', 'mailcheck_legend', PaletteManipulator::POSITION_APPEND )
    ->addField( 'mailcheck_mode', 'mailcheck_legend', PaletteManipulator::POSITION_APPEND )
    ->applyToPalette( 'default', 'tl_form' );

// Feld: Auswahl der aktiven Prüfungen (Default: keine ausgewählt = Aus)
$GLOBALS['TL_DCA']['tl_form']['fields']['mailcheck_checks'] = array
(
    'exclude'          => true,
    'inputType'        => 'checkbox',
    'options_callback' => array('tl_form_mailcheck', 'getCheckOptions'),
    'eval'             => array('multiple'=>true, 'tl_class'=>'clr'),
    'sql'              => "blob NULL",
);

// Feld: Verhalten bei erkanntem Spam
$GLOBALS['TL_DCA']['tl_form']['fields']['mailcheck_mode'] = array
(
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => array('discard', 'mark'),
    'default'   => 'discard',
    'eval'      => array('tl_class'=>'w50', 'includeBlankOption'=>false),
    'reference' => &$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode_'],
    'sql'       => "varchar(16) NOT NULL default 'discard'",
);


/**
 * Liefert die im Bundle registrierten Spam-Prüfungen als Auswahl-Optionen
 * für das Backend-Feld tl_form.mailcheck_checks.
 */
class tl_form_mailcheck
{
    public function getCheckOptions()
    {
        return System::getContainer()->get( SpamCheckerRegistry::class )->getOptions();
    }
}
