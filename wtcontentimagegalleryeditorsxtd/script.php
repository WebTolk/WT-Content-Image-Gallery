<?php
/**
 * Installer script for the WT Content Image Gallery editor button plugin.
 *
 * @package       WT Content Image gallery
 * @subpackage    plg_editors-xtd_wtcontentimagegalleryeditorsxtd
 * @version       1.3.0
 * @author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.3.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Log\Log;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

return new class () implements ServiceProviderInterface {
	/**
	 * Register the Joomla installer script service.
	 *
	 * @param   Container  $container  Joomla dependency injection container.
	 *
	 * @return  void
	 *
	 * @since   1.3.0
	 */
	public function register(Container $container): void
	{
		$container->set(
			InstallerScriptInterface::class,
			new class () implements InstallerScriptInterface {
				/**
				 * Handle extension installation.
				 *
				 * @param   InstallerAdapter  $adapter  Joomla installer adapter.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.3.0
				 */
				public function install(InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Handle extension update.
				 *
				 * @param   InstallerAdapter  $adapter  Joomla installer adapter.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.3.0
				 */
				public function update(InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Handle extension uninstall.
				 *
				 * @param   InstallerAdapter  $adapter  Joomla installer adapter.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.3.0
				 */
				public function uninstall(InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Validate the installation before Joomla applies changes.
				 *
				 * @param   string            $type     Installation action type.
				 * @param   InstallerAdapter  $adapter  Joomla installer adapter.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.3.0
				 */
				public function preflight(string $type, InstallerAdapter $adapter): bool
				{
					return true;
				}

				/**
				 * Install or remove plugin layouts after Joomla applies extension changes.
				 *
				 * @param   string            $type     Installation action type.
				 * @param   InstallerAdapter  $adapter  Joomla installer adapter.
				 *
				 * @return  boolean  True on success.
				 *
				 * @since   1.3.0
				 */
				public function postflight(string $type, InstallerAdapter $adapter): bool
				{
					$installer = $adapter->getParent();
					$layouts   = $installer->getManifest()->layouts ?? null;

					if ($type === 'uninstall')
					{
						return $this->removeLayouts($layouts);
					}

					return $this->parseLayouts($layouts, $installer);
				}

				/**
				 * Copy plugin layout files declared in the extension manifest.
				 *
				 * @param   ?SimpleXMLElement  $element    Manifest layouts element.
				 * @param   ?Installer         $installer  Joomla installer instance.
				 *
				 * @return  boolean  True when layouts were copied.
				 *
				 * @since   1.3.0
				 */
				protected function parseLayouts(?SimpleXMLElement $element = null, ?Installer $installer = null): bool
				{
					if (!$element || !count($element->children()) || !$installer)
					{
						return true;
					}

					$folder      = ((string) $element->attributes()->destination) !== ''
						? '/' . (string) $element->attributes()->destination
						: '';
					$destination = Path::clean(JPATH_ROOT . '/layouts' . $folder);

					$sourceFolder = (string) $element->attributes()->folder;
					$source       = ($sourceFolder !== '' && is_dir($installer->getPath('source') . '/' . $sourceFolder))
						? $installer->getPath('source') . '/' . $sourceFolder
						: $installer->getPath('source');

					$copyFiles = [];

					foreach ($element->children() as $file)
					{
						$path = [
							'src'  => Path::clean($source . '/' . (string) $file),
							'dest' => Path::clean($destination . '/' . (string) $file),
							'type' => $file->getName() === 'folder' ? 'folder' : 'file',
						];

						if (basename($path['dest']) !== $path['dest'])
						{
							$newDirectory = dirname($path['dest']);

							if (!Folder::create($newDirectory))
							{
								Log::add('Failed to create directory ' . $newDirectory, Log::WARNING, 'jerror');

								return false;
							}
						}

						$copyFiles[] = $path;
					}

					return $installer->copyFiles($copyFiles, true);
				}

				/**
				 * Remove plugin layout files declared in the extension manifest.
				 *
				 * @param   ?SimpleXMLElement  $element  Manifest layouts element.
				 *
				 * @return  boolean  True when layouts were removed.
				 *
				 * @since   1.3.0
				 */
				protected function removeLayouts(?SimpleXMLElement $element = null): bool
				{
					if (!$element || !count($element->children()))
					{
						return true;
					}

					$folder = ((string) $element->attributes()->destination) !== ''
						? '/' . (string) $element->attributes()->destination
						: '';
					$source = Path::clean(JPATH_ROOT . '/layouts' . $folder);

					foreach ($element->children() as $file)
					{
						$path = Path::clean($source . '/' . (string) $file);

						if (!file_exists($path))
						{
							continue;
						}

						$result = is_dir($path) ? Folder::delete($path) : File::delete($path);

						if ($result === false)
						{
							Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');

							return false;
						}
					}

					if ($folder !== '' && is_dir($source))
					{
						Folder::delete($source);
					}

					return true;
				}
			}
		);
	}
};
