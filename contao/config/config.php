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
