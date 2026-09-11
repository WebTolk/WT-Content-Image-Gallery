<?php
/**
 * @package       WT Content Image gallery
 * @subpackage    plg_editors-xtd_wtcontentimagegalleryeditorsxtd
 * @version       1.3.0
 * @author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.3.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var array $displayData */
$contentPluginEnabled = (bool) ($displayData['contentPluginEnabled'] ?? false);
$layout               = (string) ($displayData['layout'] ?? 'default');
$layoutOptions        = (array) ($displayData['layoutOptions'] ?? []);
$mediaUrl             = (string) ($displayData['mediaUrl'] ?? '');
$selectAttributes     = [
	'class'      => 'form-select',
	'aria-label' => Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_LAYOUT_LABEL'),
];
?>
<div class="container-fluid wtcontentimagegalleryeditorsxtd-modal pb-4">
	<?php if (!$contentPluginEnabled) : ?>
		<div class="alert alert-danger">
			<h4><?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_CONTENT_PLUGIN_NOT_ENABLED'); ?></h4>
		</div>
	<?php endif; ?>

	<div class="d-flex flex-column flex-xl-row align-items-xl-center gap-2 mb-3 p-2 border rounded bg-body-tertiary">
		<div class="small text-muted flex-grow-1" id="wtcontentimagegalleryeditorsxtd_layout_status">
			<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_CURRENT_LAYOUT_LABEL'); ?>:
			<strong><?php echo htmlspecialchars($layout, ENT_COMPAT, 'UTF-8'); ?></strong>
		</div>
		<div class="input-group" style="max-width: 320px;">
			<label for="wtcontentimagegalleryeditorsxtd_layout" class="input-group-text">
				<strong>tmpl</strong>
			</label>
			<?php
			echo HTMLHelper::_(
				'select.genericlist',
				$layoutOptions,
				'wtcontentimagegalleryeditorsxtd_layout',
				$selectAttributes,
				'value',
				'text',
				$layout
			);
			?>
		</div>

		<button
			class="btn btn-success"
			id="wtcontentimagegalleryeditorsxtd_insert_btn"
			type="button">
			<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_INSERT_BTN_LABEL'); ?>
		</button>
	</div>

	<ul class="nav nav-tabs mb-3" id="wtcontentimagegalleryeditorsxtd_tabs" role="tablist">
		<li class="nav-item" role="presentation">
			<button
				class="nav-link active"
				id="wtcontentimagegalleryeditorsxtd_media_tab"
				data-bs-toggle="tab"
				data-bs-target="#wtcontentimagegalleryeditorsxtd_media_pane"
				type="button"
				role="tab"
				aria-controls="wtcontentimagegalleryeditorsxtd_media_pane"
				aria-selected="true">
				<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_MEDIA_TAB_LABEL'); ?>
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button
				class="nav-link"
				id="wtcontentimagegalleryeditorsxtd_selected_tab"
				data-bs-toggle="tab"
				data-bs-target="#wtcontentimagegalleryeditorsxtd_selected_pane"
				type="button"
				role="tab"
				aria-controls="wtcontentimagegalleryeditorsxtd_selected_pane"
				aria-selected="false">
				<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SELECTED_TAB_LABEL'); ?>
				<span class="badge bg-danger ms-2" id="wtcontentimagegalleryeditorsxtd_selected_count">0</span>
			</button>
		</li>
	</ul>

	<div class="tab-content" id="wtcontentimagegalleryeditorsxtd_tab_content">
		<div
			class="tab-pane fade show active"
			id="wtcontentimagegalleryeditorsxtd_media_pane"
			role="tabpanel"
			aria-labelledby="wtcontentimagegalleryeditorsxtd_media_tab"
			tabindex="0">
			<div class="row g-3">
				<div class="col-12 col-lg-11">
					<div class="ratio ratio-16x9 h-100" style="min-height: 640px;">
						<iframe
							id="wtcontentimagegalleryeditorsxtd_media_iframe"
							src="<?php echo htmlspecialchars($mediaUrl, ENT_COMPAT, 'UTF-8'); ?>"
							title="<?php echo htmlspecialchars(Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_MEDIA_MANAGER_LABEL'), ENT_COMPAT, 'UTF-8'); ?>"
							loading="eager"></iframe>
					</div>
				</div>
				<div class="col-12 col-lg-1">
					<button
						class="btn btn-success btn-lg w-100 d-flex align-items-center justify-content-center"
						id="wtcontentimagegalleryeditorsxtd_add_file_btn"
						type="button"
						title="<?php echo htmlspecialchars(Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ADD_FILE_BTN_LABEL'), ENT_COMPAT, 'UTF-8'); ?>"
						aria-label="<?php echo htmlspecialchars(Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ADD_FILE_BTN_LABEL'), ENT_COMPAT, 'UTF-8'); ?>"
						disabled>
						<span class="fs-2 lh-1" aria-hidden="true">+</span>
						<span class="visually-hidden"><?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ADD_FILE_BTN_LABEL'); ?></span>
					</button>
					<div
						class="visually-hidden"
						id="wtcontentimagegalleryeditorsxtd_selected_status"
						aria-live="polite">
						<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_NO_MEDIA_SELECTED'); ?>
					</div>
				</div>
			</div>
		</div>

		<div
			class="tab-pane fade"
			id="wtcontentimagegalleryeditorsxtd_selected_pane"
			role="tabpanel"
			aria-labelledby="wtcontentimagegalleryeditorsxtd_selected_tab"
			tabindex="0">
			<div class="d-flex flex-column gap-3">
				<div
					class="list-group"
					id="wtcontentimagegalleryeditorsxtd_selected_list"
					aria-live="polite"></div>

				<details>
					<summary><?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_RAW_LIST_LABEL'); ?></summary>
					<div class="mt-2">
						<textarea class="form-control" id="wtcontentimagegalleryeditorsxtd_images" name="images" rows="5" placeholder="<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_IMAGES_TEXTAREA_PLACEHOLDER'); ?>"></textarea>
					</div>
				</details>

				<details>
					<summary><?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SHORTCODE_LABEL'); ?></summary>
					<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SHORTCODE_DESC'); ?>
				</details>

				<details>
					<summary><?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_INSERT_IMAGE_LABEL'); ?></summary>
					<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_INSERT_IMAGE_DESC'); ?>
				</details>

				<details>
					<summary><?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_IMAGES_ALT_AND_TITLE_FROM_FILE_LABEL'); ?></summary>
					<?php echo Text::_('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_IMAGES_ALT_AND_TITLE_FROM_FILE_DESC'); ?>
				</details>
			</div>
		</div>
	</div>
</div>
