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
            BundleConfig::create( MailcheckBundle::class )
                ->setLoadAfter( [ContaoCoreBundle::class] ),
        ];
    }


    public function registerContainerConfiguration( LoaderInterface $loader, array $managerConfig ): void
    {
        $loader->load( __DIR__ . '/../Resources/config/services.yaml' );
    }
}
