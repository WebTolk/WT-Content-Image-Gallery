/**
 * @package       WT Content Image gallery
 * @version       1.3.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */
((WTContentImageGalleryEditorsXtd) => {
    'use strict';

    /**
     * Joomla script options for the modal.
     *
     * @type {Object}
     */
    WTContentImageGalleryEditorsXtd.options = {};

    /**
     * Cached modal DOM elements.
     *
     * @type {Object}
     */
    WTContentImageGalleryEditorsXtd.elements = {};

    /**
     * Image extensions displayed as image items in the selected list.
     *
     * @type {Array<string>}
     */
    WTContentImageGalleryEditorsXtd.imageExtensions = ['avif', 'bmp', 'gif', 'ico', 'jpg', 'jpeg', 'png', 'svg', 'webp'];

    /**
     * Video extensions displayed as video items in the selected list.
     *
     * @type {Array<string>}
     */
    WTContentImageGalleryEditorsXtd.videoExtensions = ['mp4', 'mp4v', 'mpeg', 'mov', 'ogv', 'webm'];

    /**
     * Selected media items.
     *
     * @type {Array<Object>}
     */
    WTContentImageGalleryEditorsXtd.selectedItems = [];

    /**
     * Latest media item selected in Media Manager.
     *
     * @type {?Object}
     */
    WTContentImageGalleryEditorsXtd.pendingMediaItem = null;

    /**
     * Whether textarea value is currently synchronized from the selected list.
     *
     * @type {boolean}
     */
    WTContentImageGalleryEditorsXtd.syncingTextarea = false;

    /**
     * Translate a loaded Joomla text constant.
     *
     * @param {string} key Language constant key.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.translate = (key) => Joomla.Text._(key);

    /**
     * Remove duplicate media items by normalized path.
     *
     * @param {Array<Object>} items Media items.
     *
     * @returns {Array<Object>}
     */
    WTContentImageGalleryEditorsXtd.uniqueItems = (items) => {
        const paths = new Set();

        return items.filter((item) => {
            if (!item.path || paths.has(item.path)) {
                return false;
            }

            paths.add(item.path);

            return true;
        });
    };

    /**
     * Get a lowercase extension from a path.
     *
     * @param {string} path Media path.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.getExtension = (path) => {
        const cleanPath = path.split('#')[0].split('?')[0];
        const fileName = cleanPath.split('/').pop() || '';
        const parts = fileName.split('.');

        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    };

    /**
     * Resolve a media item type from Media Manager type and file extension.
     *
     * @param {string} path      Media path.
     * @param {string} mediaType Media Manager item type.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.getType = (path, mediaType) => {
        if (mediaType === 'dir') {
            return 'folder';
        }

        const extension = WTContentImageGalleryEditorsXtd.getExtension(path);

        if (WTContentImageGalleryEditorsXtd.imageExtensions.includes(extension)) {
            return 'image';
        }

        if (WTContentImageGalleryEditorsXtd.videoExtensions.includes(extension)) {
            return 'video';
        }

        return 'file';
    };

    /**
     * Get the Joomla icon class for a media item type.
     *
     * @param {string} type Media item type.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.getTypeIcon = (type) => {
        switch (type) {
            case 'folder':
                return 'icon-folder';
            case 'image':
                return 'icon-image';
            case 'video':
                return 'icon-video';
            default:
                return 'icon-file';
        }
    };

    /**
     * Get a translated label for a media item type.
     *
     * @param {string} type Media item type.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.getTypeLabel = (type) => {
        switch (type) {
            case 'folder':
                return WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_FOLDER');
            case 'image':
                return WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_IMAGE');
            case 'video':
                return WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_VIDEO');
            default:
                return WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_TYPE_FILE');
        }
    };

    /**
     * Get the display name for a media item.
     *
     * @param {string} path         Media path.
     * @param {string} fallbackName Name provided by Media Manager.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.getBaseName = (path, fallbackName) => {
        if (fallbackName) {
            return fallbackName;
        }

        const cleanPath = path.replace(/\/+$/, '');
        const name = cleanPath.split('/').pop();

        return name || cleanPath;
    };

    /**
     * Get com_media script options from the embedded Media Manager iframe.
     *
     * @returns {Object}
     */
    WTContentImageGalleryEditorsXtd.getMediaManagerOptions = () => {
        const iframe = document.getElementById(WTContentImageGalleryEditorsXtd.options.mediaIframeId || '');

        try {
            return iframe && iframe.contentWindow && iframe.contentWindow.Joomla
                ? iframe.contentWindow.Joomla.getOptions('com_media', {})
                : {};
        } catch {
            return {};
        }
    };

    /**
     * Strip the current site root from an absolute URL.
     *
     * @param {string} path URL or path.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.stripUrlRoot = (path) => {
        const systemPaths = Joomla.getOptions('system.paths', {});

        if (systemPaths.rootFull && path.indexOf(systemPaths.rootFull) === 0) {
            return path.substring(systemPaths.rootFull.length).replace(/^\/+/, '');
        }

        if (window.location.origin && path.indexOf(window.location.origin) === 0) {
            return path.substring(window.location.origin.length).replace(/^\/+/, '');
        }

        return path;
    };

    /**
     * Convert a Media Manager path or adapter URL to a site-relative media path.
     *
     * @param {string} rawPath Raw Media Manager path.
     *
     * @returns {string}
     */
    WTContentImageGalleryEditorsXtd.normalizeMediaPath = (rawPath) => {
        if (!rawPath || typeof rawPath !== 'string') {
            return '';
        }

        let path = WTContentImageGalleryEditorsXtd.stripUrlRoot(rawPath.trim().replace(/\\/g, '/'));
        path = path.split('#')[0].split('?')[0];

        if (!path) {
            return '';
        }

        if (/^[a-z][a-z0-9+.-]*:\/\//i.test(path)) {
            return '';
        }

        const adapterMatch = path.match(/^([^:]+):\/(.*)$/);

        if (adapterMatch) {
            const adapter = adapterMatch[1];
            const relativePath = adapterMatch[2].replace(/^\/+/, '');
            const mediaOptions = WTContentImageGalleryEditorsXtd.getMediaManagerOptions();
            let basePath = '';

            if (adapter.indexOf('local-') === 0) {
                basePath = adapter.substring(6);
            } else if (mediaOptions.fileBaseRelativeUrl) {
                basePath = mediaOptions.fileBaseRelativeUrl;
            } else if (WTContentImageGalleryEditorsXtd.options.mediaBaseRelativeUrl) {
                basePath = WTContentImageGalleryEditorsXtd.options.mediaBaseRelativeUrl;
            }

            basePath = String(basePath || '').replace(/^\/+|\/+$/g, '');

            if (!basePath) {
                return '';
            }

            return [basePath, relativePath].filter(Boolean).join('/').replace(/\/+/g, '/');
        }

        return path.replace(/^\/+/, '');
    };

    /**
     * Create a normalized media item.
     *
     * @param {string} path      Raw media path.
     * @param {string} mediaType Media Manager item type.
     * @param {string} name      Media Manager item name.
     * @param {string} source    Item source name.
     *
     * @returns {?Object}
     */
    WTContentImageGalleryEditorsXtd.createItem = (path, mediaType, name, source) => {
        const normalizedPath = WTContentImageGalleryEditorsXtd.normalizeMediaPath(path);

        if (!normalizedPath) {
            return null;
        }

        return {
            type: WTContentImageGalleryEditorsXtd.getType(normalizedPath, mediaType),
            name: WTContentImageGalleryEditorsXtd.getBaseName(normalizedPath, name),
            path: normalizedPath,
            source,
            valid: true,
        };
    };

    /**
     * Write the selected media paths to the raw paths textarea.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.syncTextareaFromList = () => {
        WTContentImageGalleryEditorsXtd.syncingTextarea = true;
        WTContentImageGalleryEditorsXtd.elements.textarea.value = WTContentImageGalleryEditorsXtd.selectedItems
            .map((item) => item.path)
            .join(', ');
        WTContentImageGalleryEditorsXtd.syncingTextarea = false;
    };

    /**
     * Update the current layout status in the toolbar.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.syncLayoutStatus = () => {
        const { layoutSelect, layoutStatus } = WTContentImageGalleryEditorsXtd.elements;
        const selectedLayout = layoutSelect.value || 'default';

        layoutStatus.innerHTML = '';
        layoutStatus.append(`${WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_CURRENT_LAYOUT_LABEL')}: `);

        const value = document.createElement('strong');
        value.textContent = selectedLayout;
        layoutStatus.appendChild(value);
    };

    /**
     * Move an item in the selected media list.
     *
     * @param {number} sourceIndex Source item index.
     * @param {number} targetIndex Target item index.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.moveSelectedItem = (sourceIndex, targetIndex) => {
        const [movedItem] = WTContentImageGalleryEditorsXtd.selectedItems.splice(sourceIndex, 1);

        WTContentImageGalleryEditorsXtd.selectedItems.splice(targetIndex, 0, movedItem);
        WTContentImageGalleryEditorsXtd.syncTextareaFromList();
        WTContentImageGalleryEditorsXtd.renderSelectedList();
    };

    /**
     * Render the selected media list and bind row actions.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.renderSelectedList = () => {
        const { selectedList, selectedCount } = WTContentImageGalleryEditorsXtd.elements;

        selectedList.innerHTML = '';
        selectedCount.textContent = String(WTContentImageGalleryEditorsXtd.selectedItems.length);

        if (!WTContentImageGalleryEditorsXtd.selectedItems.length) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item text-muted small';
            empty.textContent = WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_EMPTY_SELECTED_LIST');
            selectedList.appendChild(empty);

            return;
        }

        WTContentImageGalleryEditorsXtd.selectedItems.forEach((item, index) => {
            const row = document.createElement('div');
            row.className = 'list-group-item d-flex align-items-start gap-3';
            row.draggable = true;
            row.dataset.index = String(index);
            row.style.cursor = 'grab';
            row.addEventListener('dragstart', (event) => {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(index));
                row.classList.add('opacity-50');
            });
            row.addEventListener('dragend', () => {
                row.classList.remove('opacity-50');
            });
            row.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
            });
            row.addEventListener('drop', (event) => {
                event.preventDefault();

                const sourceIndex = Number.parseInt(event.dataTransfer.getData('text/plain'), 10);
                const targetIndex = Number.parseInt(row.dataset.index || '', 10);

                if (!Number.isInteger(sourceIndex) || !Number.isInteger(targetIndex) || sourceIndex === targetIndex) {
                    return;
                }

                WTContentImageGalleryEditorsXtd.moveSelectedItem(sourceIndex, targetIndex);
            });

            const sortHandle = document.createElement('span');
            sortHandle.className = 'text-muted fs-5 lh-1 flex-shrink-0';
            sortHandle.setAttribute('aria-label', WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SORT_HANDLE_LABEL'));
            sortHandle.setAttribute('title', WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SORT_HANDLE_LABEL'));
            sortHandle.textContent = '☰';

            const icon = document.createElement('span');
            icon.className = `${WTContentImageGalleryEditorsXtd.getTypeIcon(item.type)} fs-4 text-muted`;
            icon.setAttribute('aria-hidden', 'true');

            const body = document.createElement('div');
            body.className = 'flex-grow-1 overflow-hidden';

            const title = document.createElement('div');
            title.className = 'fw-bold text-break';
            title.textContent = item.name;

            const path = document.createElement('div');
            path.className = 'small text-muted text-break';
            path.textContent = item.path;

            const type = document.createElement('div');
            type.className = 'small text-muted';
            type.textContent = WTContentImageGalleryEditorsXtd.getTypeLabel(item.type);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn btn-sm btn-outline-danger flex-shrink-0';
            removeButton.setAttribute(
                'aria-label',
                `${WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_REMOVE_FILE')}: ${item.name}`
            );
            removeButton.innerHTML = '<span class="icon-trash" aria-hidden="true"></span>';
            removeButton.addEventListener('click', () => {
                WTContentImageGalleryEditorsXtd.selectedItems.splice(index, 1);
                WTContentImageGalleryEditorsXtd.syncTextareaFromList();
                WTContentImageGalleryEditorsXtd.renderSelectedList();
            });

            body.appendChild(title);
            body.appendChild(path);
            body.appendChild(type);
            row.appendChild(sortHandle);
            row.appendChild(icon);
            row.appendChild(body);
            row.appendChild(removeButton);
            selectedList.appendChild(row);
        });
    };

    /**
     * Show a selection status message.
     *
     * @param {string} message Status message.
     * @param {string} type    Bootstrap text color type.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.setStatus = (message, type = 'muted') => {
        WTContentImageGalleryEditorsXtd.elements.selectedStatus.className = `small text-${type} text-wrap text-break w-100`;
        WTContentImageGalleryEditorsXtd.elements.selectedStatus.textContent = message;
    };

    /**
     * Store the latest media item selected in Media Manager.
     *
     * @param {?Object} item Media item.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.setPendingMediaItem = (item) => {
        WTContentImageGalleryEditorsXtd.pendingMediaItem = item;
        WTContentImageGalleryEditorsXtd.elements.addFileButton.disabled = !WTContentImageGalleryEditorsXtd.pendingMediaItem;

        if (WTContentImageGalleryEditorsXtd.pendingMediaItem) {
            WTContentImageGalleryEditorsXtd.setStatus(
                `${WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_SELECTED_FILE')}: ${WTContentImageGalleryEditorsXtd.pendingMediaItem.path}`,
                'muted'
            );
        } else {
            WTContentImageGalleryEditorsXtd.setStatus(
                WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_NO_MEDIA_SELECTED'),
                'muted'
            );
        }
    };

    /**
     * Parse paths from the textarea or from an existing gallery shortcode.
     *
     * @returns {Array<Object>}
     */
    WTContentImageGalleryEditorsXtd.parseTextareaItems = () => {
        let source = WTContentImageGalleryEditorsXtd.elements.textarea.value.trim();
        const shortcodeMatch = source.match(/\{gallery(?:\s[^}]*)?}([\s\S]*?)\{\/gallery}/i);

        if (shortcodeMatch) {
            source = shortcodeMatch[1].trim();
        }

        if (!source || /<\s*(img|video)\b/i.test(source)) {
            return [];
        }

        return WTContentImageGalleryEditorsXtd.uniqueItems(source.split(',')
            .map((path) => WTContentImageGalleryEditorsXtd.createItem(path, '', '', 'textarea'))
            .filter(Boolean));
    };

    /**
     * Replace the current selected media list.
     *
     * @param {Array<Object>} items Media items.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.replaceSelectedItems = (items) => {
        WTContentImageGalleryEditorsXtd.selectedItems.splice(0, WTContentImageGalleryEditorsXtd.selectedItems.length, ...items);
        WTContentImageGalleryEditorsXtd.renderSelectedList();
    };

    /**
     * Handle a Media Manager item selection event.
     *
     * @param {Event} event Media selected event.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.onMediaFileSelected = (event) => {
        const detail = event.detail || {};
        const item = WTContentImageGalleryEditorsXtd.createItem(detail.path, detail.type, detail.name, 'media-manager');

        if (!item) {
            WTContentImageGalleryEditorsXtd.setPendingMediaItem(null);
            WTContentImageGalleryEditorsXtd.setStatus(
                WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_PATH_NORMALIZE_ERROR'),
                'danger'
            );

            return;
        }

        WTContentImageGalleryEditorsXtd.setPendingMediaItem(item);
    };

    /**
     * Add the pending Media Manager item to the selected list.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.addPendingMediaItem = () => {
        if (!WTContentImageGalleryEditorsXtd.pendingMediaItem) {
            WTContentImageGalleryEditorsXtd.setStatus(
                WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_NO_MEDIA_SELECTED'),
                'warning'
            );

            return;
        }

        if (WTContentImageGalleryEditorsXtd.selectedItems.some((item) => item.path === WTContentImageGalleryEditorsXtd.pendingMediaItem.path)) {
            WTContentImageGalleryEditorsXtd.setStatus(
                WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ALREADY_ADDED'),
                'warning'
            );

            return;
        }

        WTContentImageGalleryEditorsXtd.selectedItems.push(WTContentImageGalleryEditorsXtd.pendingMediaItem);
        WTContentImageGalleryEditorsXtd.syncTextareaFromList();
        WTContentImageGalleryEditorsXtd.renderSelectedList();
        WTContentImageGalleryEditorsXtd.setStatus(
            `${WTContentImageGalleryEditorsXtd.translate('PLG_WTCONTENTIMAGEGALLERYEDITORSXTD_ADDED_FILE')}: ${WTContentImageGalleryEditorsXtd.pendingMediaItem.path}`,
            'success'
        );
    };

    /**
     * Handle the raw paths textarea input event.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.onTextareaInput = () => {
        if (WTContentImageGalleryEditorsXtd.syncingTextarea) {
            return;
        }

        WTContentImageGalleryEditorsXtd.replaceSelectedItems(WTContentImageGalleryEditorsXtd.parseTextareaItems());
    };

    /**
     * Insert the generated shortcode into the active editor.
     *
     * @returns {boolean}
     */
    WTContentImageGalleryEditorsXtd.insertShortcode = () => {
        const tmpl = WTContentImageGalleryEditorsXtd.elements.layoutSelect.value;
        const images = WTContentImageGalleryEditorsXtd.elements.textarea.value.trim();
        let shortCode = '';

        if (images !== '') {
            shortCode = `{gallery tmpl=${tmpl}}${images}{/gallery}`;
        } else {
            shortCode = `{gallery tmpl=${tmpl}}{/gallery}`;
        }

        if (!Joomla.getOptions('xtd-wtcontentimagegalleryeditorsxtd')) {
            return false;
        }

        const {
            editor
        } = Joomla.getOptions('xtd-wtcontentimagegalleryeditorsxtd');

        window.parent.Joomla.editors.instances[editor].replaceSelection(shortCode);

        if (window.parent.Joomla.Modal) {
            window.parent.Joomla.Modal.getCurrent().close();
        }

        return true;
    };

    /**
     * Initialize the editor modal script.
     *
     * @returns {void}
     */
    WTContentImageGalleryEditorsXtd.init = () => {
        WTContentImageGalleryEditorsXtd.options = Joomla.getOptions('xtd-wtcontentimagegalleryeditorsxtd') || {};
        WTContentImageGalleryEditorsXtd.elements = {
            insertButton: document.getElementById('wtcontentimagegalleryeditorsxtd_insert_btn'),
            addFileButton: document.getElementById('wtcontentimagegalleryeditorsxtd_add_file_btn'),
            layoutSelect: document.getElementById('wtcontentimagegalleryeditorsxtd_layout'),
            layoutStatus: document.getElementById('wtcontentimagegalleryeditorsxtd_layout_status'),
            textarea: document.getElementById('wtcontentimagegalleryeditorsxtd_images'),
            selectedList: document.getElementById('wtcontentimagegalleryeditorsxtd_selected_list'),
            selectedCount: document.getElementById('wtcontentimagegalleryeditorsxtd_selected_count'),
            selectedStatus: document.getElementById('wtcontentimagegalleryeditorsxtd_selected_status'),
        };

        const {
            insertButton,
            addFileButton,
            layoutSelect,
            layoutStatus,
            textarea,
            selectedList,
            selectedCount,
            selectedStatus,
        } = WTContentImageGalleryEditorsXtd.elements;

        if (!insertButton || !addFileButton || !layoutSelect || !layoutStatus || !textarea || !selectedList || !selectedCount || !selectedStatus) {
            return;
        }

        document.addEventListener('onMediaFileSelected', WTContentImageGalleryEditorsXtd.onMediaFileSelected);
        addFileButton.addEventListener('click', WTContentImageGalleryEditorsXtd.addPendingMediaItem);
        textarea.addEventListener('input', WTContentImageGalleryEditorsXtd.onTextareaInput);
        layoutSelect.addEventListener('change', WTContentImageGalleryEditorsXtd.syncLayoutStatus);
        insertButton.addEventListener('click', WTContentImageGalleryEditorsXtd.insertShortcode);

        WTContentImageGalleryEditorsXtd.renderSelectedList();
        WTContentImageGalleryEditorsXtd.syncLayoutStatus();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', WTContentImageGalleryEditorsXtd.init);
    } else {
        WTContentImageGalleryEditorsXtd.init();
    }
})(window.WTContentImageGalleryEditorsXtd = window.WTContentImageGalleryEditorsXtd || {});
