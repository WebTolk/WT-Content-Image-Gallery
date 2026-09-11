<?php
/**
 * @package       WT Content Image gallery
 * @version       1.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\EditorsXtd\Wtcontentimagegalleryeditorsxtd\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;

use function defined;

defined('_JEXEC') or die;

/**
 * Editor button plugin for inserting WT Content Image Gallery shortcodes.
 *
 * @since  1.0.0
 */
final class Wtcontentimagegalleryeditorsxtd extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Plugin element name.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
    protected $_name = 'wtcontentimagegalleryeditorsxtd';

	/**
	 * Plugin folder name.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
    protected $_type = 'editors-xtd';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return array
	 *
	 * @since   5.2.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onEditorButtonsSetup'                  => 'onEditorButtonsSetup',
			'onAjaxWtcontentimagegalleryeditorsxtd' => 'onAjaxWtcontentimagegalleryeditorsxtd',
		];
	}

	/**
	 * Add the gallery editor button to supported editors.
	 *
	 * @param   EditorButtonsSetupEvent  $event  The editor buttons setup event.
	 *
	 * @return  void
	 *
	 * @since   5.2.0
	 */
	public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
	{
		$subject  = $event->getButtonsRegistry();
		$disabled = $event->getDisabledButtons();

		if (\in_array($this->_name, $disabled)) {
			return;
		}

		if (!empty(PluginHelper::getPlugin('content', 'wtcontentimagegallery')))
		{
			$app = $this->getApplication();
			if (!$app->isClient('administrator'))
			{
				return;
			}

			$user = $app->getIdentity();

			// Can create in any category (component permission) or at least in one category
			$canCreateRecords = $user->authorise('core.create', 'com_content')
				|| count($user->getAuthorisedCategories('com_content', 'core.create')) > 0;

			// Instead of checking edit on all records, we can use **same** check as the form editing view
			$values           = (array) Factory::getApplication()->getUserState('com_content.edit.article.id');
			$isEditingRecords = count($values);

			// This ACL check is probably a double-check (form view already performed checks)
			$hasAccess = $canCreateRecords || $isEditingRecords;
			if (!$hasAccess)
			{
				return;
			}

			$articleId = $this->getEditedArticleId();

            $url  =  new Uri('index.php');
			$url->setQuery([
                    'option' => 'com_ajax',
                    'plugin' => 'wtcontentimagegalleryeditorsxtd',
                    'group' => 'editors-xtd',
                    'format' => 'html',
                    'tmpl' => 'component',
                    Session::getFormToken()  => '1',
                    'editor' => $event->getEditorId(),
                    'article_id' => $articleId,
            ]);
			$button          = new Button(
				$this->_name,
				[
					'action'  => 'modal',
					'link'    => $url->toString(),
					'text'    => '{Gallery}',
					'icon'    => 'pictures',
					'iconSVG' => '<svg width="24" height="24" viewBox="0 0 512 512"><path d="M464 64H48C21.49 64 0 85.49 0 112v288c0 26.51 21.49 48'
						. ' 48 48h416c26.51 0 48-21.49 48-48V112c0-26.51-21.49-48-48-48zm-6 336H54a6 6 0 0 1-6-6V118a6 6 0 0 1 6-6h404a6 6'
						. ' 0 0 1 6 6v276a6 6 0 0 1-6 6zM128 152c-22.091 0-40 17.909-40 40s17.909 40 40 40 40-17.909 40-40-17.909-40-40-40'
						. 'zM96 352h320v-80l-87.515-87.515c-4.686-4.686-12.284-4.686-16.971 0L192 304l-39.515-39.515c-4.686-4.686-12.284-4'
						. '.686-16.971 0L96 304v48z"></path></svg>',
					'options' => [
						'height'      => '80vh',
						'width'       => '100%',
						'modalWidth'  => '90',
						'modalHeight' => '80',
					],
					// This is whole Plugin name, it is needed for keeping backward compatibility
					'name' => 'editors-xtd_wtcontentimagegalleryeditorsxtd',
				]
			);

			if ($button) {
				$subject->add($button);
			}
		}
	}

	/**
	 * Render the com_ajax modal content for selecting a gallery layout and media paths.
	 *
	 * @param   AjaxEvent  $event  The Ajax event.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
     *
     * @since 1.0.0
	 */
	public function onAjaxWtcontentimagegalleryeditorsxtd(AjaxEvent $event): void
	{
		$app = $this->getApplication();
		$input = $app->getInput();

		if (!Session::checkToken('get'))
		{
			throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
		}

		if (!$app->isClient('administrator'))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$lang = $app->getLanguage();
		$lang->load('plg_editors-xtd_wtcontentimagegalleryeditorsxtd', JPATH_ADMINISTRATOR);
		$doc = $app->getDocument();
		$webAssetManager = $doc->getWebAssetManager();
		$webAssetManager->getRegistry()->addExtensionRegistryFile('plg_editors-xtd_wtcontentimagegalleryeditorsxtd');
		$webAssetManager->useScript('wtcontentimagegalleryeditorsxtd.modal');

		$editor               = $input->get('editor', '');
		$contentPluginEnabled = PluginHelper::isEnabled('content', 'wtcontentimagegallery');
		$layout               = 'default';
		$layoutOptions        = [];
		$layoutsPath          = JPATH_SITE . '/plugins/content/wtcontentimagegallery/tmpl';

		if ($contentPluginEnabled)
		{
			$contentPlugin = PluginHelper::getPlugin('content', 'wtcontentimagegallery');
			$contentParams = new Registry($contentPlugin->params);
			$layout        = (string) $contentParams->get('default_layout_for_default', 'default');
			$layout        = $layout !== '' ? $layout : 'default';
		}

		if (is_dir($layoutsPath))
		{
			$layoutFiles = Folder::files($layoutsPath, '\.php$');

			foreach ($layoutFiles as $file)
			{
				$wtLayout        = File::stripExt($file);
				$layoutOptions[] = HTMLHelper::_('select.option', $wtLayout, $wtLayout);
			}
		}

		if ($layoutOptions === [])
		{
			$layoutOptions[] = HTMLHelper::_('select.option', $layout, $layout);
		}

		$mediaParams = ComponentHelper::getParams('com_media');
		$mediaPath   = trim((string) $mediaParams->get('file_path', 'images'), '/');
		$mediaPath   = $mediaPath !== '' ? $mediaPath : 'images';
		$mediaTypes  = '0,2,-1';
		$articleId   = $input->getInt('article_id', $this->getEditedArticleId());
		$asset       = $articleId > 0 ? 'com_content.article.' . $articleId : 'com_content';
		$authorId    = $articleId > 0 ? $this->getArticleAuthorId($articleId) : (int) $app->getIdentity()->id;

		$mediaUrl = new Uri('index.php');
		$mediaUrl->setQuery([
			'option' => 'com_media',
			'view' => 'media',
			'tmpl' => 'component',
			'mediatypes' => $mediaTypes,
			'asset' => $asset,
			'author' => $authorId,
			'path' => $mediaPath,
			Session::getFormToken() => '1',
		]);

		if (!empty($editor))
		{
			$doc->addScriptOptions('xtd-wtcontentimagegalleryeditorsxtd', [
				'editor' => $editor,
				'mediaIframeId' => 'wtcontentimagegalleryeditorsxtd_media_iframe',
				'mediaBaseRelativeUrl' => $mediaPath,
			]);
		}

		echo LayoutHelper::render(
			'plugins.editors-xtd.wtcontentimagegalleryeditorsxtd.modal.default',
			[
				'contentPluginEnabled' => $contentPluginEnabled,
				'layout'               => $layout,
				'layoutOptions'        => $layoutOptions,
				'mediaUrl'             => $mediaUrl->toString(),
			]
		);
	}

	/**
	 * Get the article id currently stored by the com_content edit form.
	 *
	 * @return  int  Current article id, or 0 when no article is available.
	 *
	 * @since   1.3.0
	 */
	private function getEditedArticleId(): int
	{
		$values = (array) Factory::getApplication()->getUserState('com_content.edit.article.id');
		$id = reset($values);

		return (int) $id;
	}

	/**
	 * Get an article author id for Media Manager ACL context.
	 *
	 * @param   integer  $articleId  Article id.
	 *
	 * @return  integer  Article author id, or the current user id when it cannot be resolved.
	 *
	 * @since   1.3.0
	 */
	private function getArticleAuthorId(int $articleId): int
	{
		if ($articleId <= 0) {
			return (int) $this->getApplication()->getIdentity()->id;
		}

		try {
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true)
				->select($db->quoteName('created_by'))
				->from($db->quoteName('#__content'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $articleId);
			$db->setQuery($query);

			$authorId = (int) $db->loadResult();
		} catch (\Throwable $exception) {
			$authorId = 0;
		}

		return $authorId > 0 ? $authorId : (int) $this->getApplication()->getIdentity()->id;
	}
}
