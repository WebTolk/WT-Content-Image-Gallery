<?php
/**
 * @package       WT Content Image gallery
 * @version       1.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.3.0
 */

namespace Joomla\Plugin\EditorsXtd\Wtcontentimagegalleryeditorsxtd\WebAsset;

use Joomla\CMS\Document\Document;
use Joomla\CMS\Language\Text;
use Joomla\CMS\WebAsset\WebAssetAttachBehaviorInterface;
use Joomla\CMS\WebAsset\WebAssetItem;

use function defined;

defined('_JEXEC') or die;

/**
 * Web asset item for the editors-xtd modal script.
 *
 * @since  1.3.0
 */
final class ModalAssetItem extends WebAssetItem implements WebAssetAttachBehaviorInterface
{
	/**
	 * Language constants used by wtcontentimagegalleryeditorsxtd.js.
	 *
	 * @var string[]
	 * @since  1.3.0
	 */
	private const SCRIPT_TEXT_KEYS = [
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ADD_FILE_BTN_LABEL',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ADDED_FILE',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ALREADY_ADDED',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_NO_MEDIA_SELECTED',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SELECTED_FILE',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_REMOVE_FILE',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_PATH_NORMALIZE_ERROR',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_EMPTY_SELECTED_LIST',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_IMAGE',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_VIDEO',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_FOLDER',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_FILE',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_CURRENT_LAYOUT_LABEL',
		'PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SORT_HANDLE_LABEL',
	];

	/**
	 * Load JavaScript language constants when the asset is attached to the document.
	 *
	 * @param   Document  $doc  Active document.
	 *
	 * @return  void
	 *
	 * @since   1.3.0
	 */
	public function onAttachCallback(Document $doc)
	{
		foreach (self::SCRIPT_TEXT_KEYS as $key)
		{
			Text::script($key);
		}
	}
}
