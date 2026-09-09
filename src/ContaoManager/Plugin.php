<?php

declare(strict_types=1);

/**
 * @copyright  Softleister 2026
 * @package    contao-mailcheck-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/do-while/contao-mailcheck-bundle
 *
 */

namespace Softleister\ContaoMailcheckBundle\ContaoManager;


use Contao\CoreBundle\ContaoCoreBundle;
use Softleister\ContaoMailcheckBundle\MailcheckBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;


class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    public function getBundles( ParserInterface $parser )
    {
        return [
            // 'notification_center': Notification Center 1.x ist ein
            // Contao-2/3-Legacy-Modul (composer type "contao-module", kein
            // eigenes ContaoManager\Plugin) und wird daher über seinen
            // Ordnernamen unter system/modules/ referenziert, nicht über
            // eine FQCN. Notwendig, damit unser config.php (siehe
            // contao/config/config.php) erst NACH dem von Notification
            // Center läuft - dort wird $GLOBALS['NOTIFICATION_CENTER']
            // ['NOTIFICATION_TYPE']['core_form'] um unseren eigenen Token
            // 'mailcheck_marker' erweitert, das Array muss zu diesem
            // Zeitpunkt also bereits existieren.
            BundleConfig::create( MailcheckBundle::class )
                ->setLoadAfter( [ContaoCoreBundle::class, 'notification_center'] ),
        ];
    }


    public function registerContainerConfiguration( LoaderInterface $loader, array $managerConfig ): void
    {
        $loader->load( __DIR__ . '/../Resources/config/services.yaml' );
    }
}
