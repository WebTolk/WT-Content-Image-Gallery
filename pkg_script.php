<?php
/**
 * @package       WT Content Image gallery
 * @version       1.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the package installer script service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function register(Container $container): void
	{
		$container->set(
			InstallerScriptInterface::class,
			new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
				/**
				 * The application object.
				 *
				 * @var  AdministratorApplication
				 *
				 * @since  1.0.0
				 */
				private readonly AdministratorApplication $app;

				/**
				 * Minimum Joomla version required to install the extension.
				 *
				 * @var  string
				 *
				 * @since  1.0.0
				 */
				private string $minimumJoomla = '5.0.0';

				/**
				 * Minimum PHP version required to install the extension.
				 *
				 * @var  string
				 *
				 * @since  1.0.0
				 */
				private string $minimumPhp = '8.1';

				/**
				 * Constructor.
				 *
				 * @param   AdministratorApplication  $app  The application object.
				 *
				 * @since   1.0.0
				 */
				public function __construct(AdministratorApplication $app)
				{
					$this->app = $app;
				}

				/**
				 * Function called after the extension is installed.
				 *
				 * @param   InstallerAdapter  $adapter  The adapter calling this method.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.0.0
				 */
				public function install(InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Function called after the extension is uninstalled.
				 *
				 * @param   InstallerAdapter  $adapter  The adapter calling this method.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.0.0
				 */
				public function uninstall(InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Function called after the extension is updated.
				 *
				 * @param   InstallerAdapter  $adapter  The adapter calling this method.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.0.0
				 */
				public function update(InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Function called before extension installation/update/removal procedure commences.
				 *
				 * @param   string            $type     The type of change.
				 * @param   InstallerAdapter  $adapter  The adapter calling this method.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.0.0
				 */
				public function preflight(string $type, InstallerAdapter $adapter): bool
				{
					return $this->checkCompatible($adapter);
				}

				/**
				 * Function called after extension installation/update/removal procedure commences.
				 *
				 * @param   string            $type     The type of change.
				 * @param   InstallerAdapter  $adapter  The adapter calling this method.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.0.0
				 */
				public function postflight(string $type, InstallerAdapter $adapter): bool
				{
					$element = strtoupper($adapter->getElement());
					$smile   = '';

					if ($type !== 'uninstall')
					{
						$smiles = ['&#9786;', '&#128512;', '&#128521;', '&#128525;', '&#128526;', '&#128522;', '&#128591;'];
						$smile  = $smiles[array_rand($smiles)];
					}
					else
					{
						$smile = ':(';
					}

					$typeUpper = strtoupper($type);
					$html      = '
					<div class="row m-0">
						<div class="col-12 col-md-8 p-0 pe-2">
							<h2>' . $smile . ' ' . Text::_($element . '_AFTER_' . $typeUpper) . ' <br/>' . Text::_($element) . '</h2>
							' . Text::_($element . '_DESC') . '
							' . Text::_($element . '_WHATS_NEW') . '
						</div>
						<div class="col-12 col-md-4 p-0 d-flex flex-column justify-content-start">
							<img width="180" src="https://web-tolk.ru/web_tolk_logo_wide.png" alt="WebTolk">
							<p>Joomla Extensions</p>
							<p class="btn-group">
								<a class="btn btn-sm btn-outline-primary" href="https://web-tolk.ru" target="_blank" rel="noopener noreferrer">https://web-tolk.ru</a>
								<a class="btn btn-sm btn-outline-primary" href="mailto:info@web-tolk.ru"><i class="icon-envelope"></i> info@web-tolk.ru</a>
							</p>
							<div class="btn-group-vertical mb-3 web-tolk-btn-links" role="group" aria-label="WebTolk community links">
								<a class="btn btn-danger text-white w-100" href="https://t.me/joomlaru" target="_blank" rel="noopener noreferrer">' . Text::_($element . '_JOOMLARU_TELEGRAM_CHAT') . '</a>
								<a class="btn btn-primary text-white w-100" href="https://t.me/webtolkru" target="_blank" rel="noopener noreferrer">' . Text::_($element . '_WEBTOLK_TELEGRAM_CHANNEL') . '</a>
								<a class="btn btn-success text-white w-100" href="https://max.ru/join/LChBfwGDmArJpK6--oS0qVAJA1WdRk0OPXciwryF4ZY" target="_blank" rel="noopener noreferrer">' . Text::_($element . '_MAX_CHANNEL') . '</a>
							</div>
							' . Text::_($element . '_MAYBE_INTERESTING') . '
						</div>
					</div>';

					$this->app->enqueueMessage($html, 'info');

					return true;
				}

				/**
				 * Checks the Joomla and PHP versions before installation.
				 *
				 * @param   InstallerAdapter  $adapter  The adapter calling this method.
				 *
				 * @return  boolean  True when the environment is compatible.
				 *
				 * @since   1.0.0
				 */
				private function checkCompatible(InstallerAdapter $adapter): bool
				{
					$element = strtoupper($adapter->getElement());

					if (!(new Version())->isCompatible($this->minimumJoomla))
					{
						$this->app->enqueueMessage(
							Text::sprintf($element . '_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
							'error'
						);

						return false;
					}

					if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
					{
						$this->app->enqueueMessage(
							Text::sprintf($element . '_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
							'error'
						);

						return false;
					}

					return true;
				}
			}
		);
	}
};
