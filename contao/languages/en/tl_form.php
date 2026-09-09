<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

$GLOBALS['TL_LANG']['tl_form']['mailcheck_checks'] = ['Spam checks', 'Select which checks should run before the mail is sent. If none are selected (default), this bundle performs no check at all.'];
$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode']   = ['Behaviour on suspected spam', 'Defines what happens when one of the selected checks flags the submission as spam.'];

$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode_']['discard'] = 'Discard the mail (silently, no error shown to the sender)';
$GLOBALS['TL_LANG']['tl_form']['mailcheck_mode_']['mark']    = 'Send the mail anyway, prefix the subject with "MAILSPAM!"';

$GLOBALS['TL_LANG']['tl_form']['mailcheck_legend'] = 'Spam check';
