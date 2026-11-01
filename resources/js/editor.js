// The bio editor (Alpine component). Loaded as a module only on the editor page and cached by the browser;
// the page's data comes in as JSON from <script id="bio-editor-config"> instead of being printed into the page.
const CONFIG = JSON.parse(document.getElementById('bio-editor-config').textContent);
const csrf = () => document.head.querySelector('meta[name="csrf-token"]')?.content || '';
// Static lookups are frozen: Alpine skips frozen objects, so ~400 brands are not made reactive and not watched.
const PLATFORMS = Object.freeze(CONFIG.platforms);
const THEMES = Object.freeze(CONFIG.themes);
// Editor API URLs follow the *saved* slug, so renaming the page doesn't break later requests.
// Fields the editor never sends back: the server owns them, or they have their own endpoints.
const NOT_SAVED = new Set(['id', 'user_id', 'links', 'user', 'created_at', 'updated_at', 'deleted_at']);
window.bioEditorSlug = CONFIG.bioPage.slug;
window.bioEditorUrl = (path = '') => '/bio/' + encodeURIComponent(window.bioEditorSlug) + path;

// The Design tab: six collapsible sections; which ones are open is remembered per browser.
window.designTab = function() {
    const KEY = 'bio-design-open';
    let saved = {};
    try { saved = JSON.parse(localStorage.getItem(KEY) || '{}'); } catch (e) {}
    return {
        sections: [['header', 'Header'], ['theme', 'Theme'], ['wallpaper', 'Wallpaper'], ['text', 'Text'], ['buttons', 'Buttons'], ['footer', 'Footer']],
        open: { header: true, theme: true, wallpaper: true, text: true, buttons: true, footer: true, ...saved },
        publicUrl: CONFIG.publicBase + '/' + encodeURIComponent(CONFIG.bioPage.slug),
        toggle(key) {
            this.open[key] = !this.open[key];
            try { localStorage.setItem(KEY, JSON.stringify(this.open)); } catch (e) {}
        },
        jump(key) {
            this.open[key] = true;
            this.$nextTick(() => document.getElementById('design-' + key)?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
        },
    };
};

window.bioEditorV2 = function() {
    return {
        // State
        activeTab: 'links',
        previewMode: 'mobile',
        saving: false,
        hasUnsavedChanges: false,
        // saved | dirty | saving | error: shown next to the Save button
        saveState: 'saved',
        saveError: '',
        fieldErrors: {},
        autosave: true,
        previewOpen: false,
        showDiscardModal: false,
        announcement: '',
        pendingNavigation: null,
        editingBlock: null,
        showAddBlockModal: false,
        blockCategoryFilter: 'all',
        blockSearch: '',
        showSocialPicker: false,
        showSocialEditor: false,
        editingSocial: null,
        showTextEditor: false,
        showImageUploader: false,
        showIconPicker: false,
        showPassword: false,
        socialSearch: '',
        iconSearch: '',
        imageUploadTab: 'upload',
        imageBlockUrl: '',
        imageBlockAlt: '',
        imageBlockLink: '',
        textEditorBlock: null,
        iconPickerBlock: null,
        editingTextBlock: null,
        urlInputTimeout: null,
        previewUrl: CONFIG.previewUrl,
        previewReady: false,
        deletedBlockIds: [],
        // Last server revision this tab has seen; a save from another tab makes the next save fail with 409 (L2).
        _baseUpdatedAt: null,
        _saveInFlight: false,
        _saveQueued: false,
        _orderDirty: false,
        // Link Library State
        showLinkLibraryModal: false,
        librarySearch: '',
        libraryLinks: [],
        libraryLoading: false,
        linkInputMode: 'create', // 'create' or 'existing'
        dirtyBlockIds: new Set(), // Track which blocks have been modified
        // Data
        platforms: PLATFORMS,
        bioPage: CONFIG.bioPage,
        blocks: CONFIG.blocks,
        archived: CONFIG.archived || [],
        selectMode: false,
        selectedIds: [],
        showArchived: false,
        brands: PLATFORMS,
        // Themes - using hex colors only for input compatibility
        themes: THEMES,
        // Sortable instances
        blocksSortable: null,
        socialsSortable: null,
        // Computed
        get filteredPlatforms() {
            const search = this.socialSearch.toLowerCase();
            if (!search) return this.platforms;
            return Object.fromEntries(
                Object.entries(this.platforms).filter(([key, val]) => 
                    val.name.toLowerCase().includes(search) || key.includes(search)
                )
            );
        },
        get filteredBrands() {
            const search = this.iconSearch.toLowerCase();
            if (!search) return this.brands;
            return Object.fromEntries(
                Object.entries(this.brands).filter(([key, val]) => 
                    val.name.toLowerCase().includes(search) || key.includes(search)
                )
            );
        },
        get isBlockValid() {
            if (!this.editingBlock) return false;
            if (this.editingBlock._urlError) return false;
            if (this.editingBlock.type === 'link') {
                return !!(this.editingBlock.url && String(this.editingBlock.url).trim());
            }
            return true;
        },
        init() {
            // Expose bioPage to window for child components (like qrGenerator)
            window.bioEditorData = { bioPage: this.bioPage };
            // ?tab=profile|links|socials|appearance|settings opens that tab (the dashboard checklist links here)
            const wanted = new URLSearchParams(location.search).get('tab');
            if (['profile', 'links', 'socials', 'appearance', 'settings'].includes(wanted)) this.activeTab = wanted;
            this.$nextTick(() => {
                this.initSortable();
            });
            // Initialize social_links if not exists
            if (!this.bioPage.social_links) {
                this.bioPage.social_links = [];
            }
            // Stable client-side keys for x-for / drag sorting (stripped before saving)
            this.bioPage.social_links.forEach(s => { s._uid ??= this.uid(); });
            this._baseUpdatedAt = this.bioPage.updated_at || null;
            // What the server has, per page field: a save sends only the fields that differ from this.
            this._pageSnap = this.pageSnapshot();
            this._blockSnap = {};
            this.blocks.forEach(b => { this._blockSnap[b.id] = this.blockKey(b); });
            try { this.autosave = localStorage.getItem('bio-autosave') !== 'off'; } catch (e) {}
            // Listen for preview ready
            window.addEventListener('message', (event) => {
                if (event.origin !== window.location.origin) return;
                if (event.data?.type === 'preview-ready') {
                    this.previewReady = true;
                    this._pvLast = {};
                    this.sendPreviewUpdate();
                }
            });
            // One cheap watcher instead of two deep ones: it reads the editor's data a few levels deep (so any change
            // re-runs it) without serialising it, which used to cost milliseconds per keystroke.
            const read = (o, depth) => {
                if (o && typeof o === 'object') for (const k in o) if (depth > 0) read(o[k], depth - 1);
            };
            let first = true;
            Alpine.effect(() => {
                read(this.bioPage, 3);
                read(this.blocks, 3);
                if (first) { first = false; return; }
                queueMicrotask(() => this.onModelChanged());
            });
            // Warn only when something is still unsaved or on its way
            window.addEventListener('beforeunload', (e) => {
                if (this.hasUnsavedChanges || this._saveInFlight) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
            // Leaving the tab saves what is pending
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && this.hasUnsavedChanges && !this._autoBlocked) this.saveBioPage();
            });
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    this._autoBlocked = false;
                    this.saveBioPage();
                }
            });
            this.$watch('previewOpen', open => { document.documentElement.style.overflow = open ? 'hidden' : ''; });
        },
        uid() {
            return Math.random().toString(36).slice(2, 10);
        },
        // ---- change tracking: per field, so a save sends only what changed ----
        pageSnapshot() {
            const raw = Alpine.raw(this.bioPage), out = {};
            for (const key of Object.keys(raw)) {
                if (NOT_SAVED.has(key)) continue;
                const value = key === 'social_links'
                    ? (raw[key] || []).map(({ _uid, ...rest }) => rest)
                    : raw[key];
                out[key] = JSON.stringify(value ?? null);
            }
            return out;
        },
        changedPageKeys() {
            const now = this.pageSnapshot(), changed = [];
            for (const key of Object.keys(now)) if (now[key] !== this._pageSnap[key]) changed.push(key);
            return changed;
        },
        // A block counts as changed when its content differs from what the server has (order has its own endpoint),
        // so a missing markBlockDirty() call can no longer make an edit silently not save.
        blockKey(block) {
            const out = {};
            for (const key of Object.keys(block)) {
                if (key[0] === '_' || key === 'is_new' || key === 'order' || key === 'id') continue;
                out[key] = block[key];
            }
            return JSON.stringify(out);
        },
        changedBlocks() {
            return this.blocks.filter(b => b.id < 1000000000 && !b.is_new
                && (this.dirtyBlockIds.has(b.id) || this.blockKey(Alpine.raw(b)) !== this._blockSnap[b.id]));
        },
        hasPending() {
            return this.changedPageKeys().length > 0
                || this.changedBlocks().length > 0
                || this.deletedBlockIds.length > 0
                || this._orderDirty
                || this.blocks.some(b => b.id >= 1000000000 && !b.is_new);
        },
        // Called after every edit (not every frame: microtask-coalesced), and by code that changes data off to the side.
        checkForChanges() {
            this.hasUnsavedChanges = this.hasPending();
            if (this.saveState !== 'saving') this.saveState = this.hasUnsavedChanges ? (this.saveError && this._autoBlocked ? 'error' : 'dirty') : 'saved';
        },
        onModelChanged() {
            this._autoBlocked = false;
            this.queuePreview();
            clearTimeout(this._checkTimeout);
            this._checkTimeout = setTimeout(() => { this.checkForChanges(); this.scheduleAutosave(); }, 150);
        },
        // ---- autosave: Linktree-style, it just saves ----
        isComposing() {
            return !!(this.editingBlock || this.showTextEditor || this.showImageUploader || this.showSocialEditor
                || this.showIconPicker || this.showAddBlockModal || this.showLinkLibraryModal || this.showSocialPicker
                || this.showDiscardModal || this.saving);
        },
        scheduleAutosave() {
            clearTimeout(this._autoTimeout);
            if (!this.autosave || !this.hasUnsavedChanges || this._autoBlocked) return;
            this._autoTimeout = setTimeout(() => {
                if (this.isComposing()) { this.scheduleAutosave(); return; }
                this.saveBioPage();
            }, 1400);
        },
        toggleAutosave() {
            this.autosave = !this.autosave;
            try { localStorage.setItem('bio-autosave', this.autosave ? 'on' : 'off'); } catch (e) {}
            if (this.autosave) this.scheduleAutosave();
        },
        // ---- preview: one update per frame, only the parts that changed ----
        queuePreview() {
            if (this._pvRaf) return;
            this._pvRaf = requestAnimationFrame(() => { this._pvRaf = 0; this.sendPreviewUpdate(); });
        },
        debouncedPreviewUpdate() { this.queuePreview(); },
        debouncedCheckForChanges() { this.onModelChanged(); },
        async handleNavigation(url) {
            // Pending edits are saved first; only a failed save asks what to do.
            if (this.hasUnsavedChanges) {
                this._autoBlocked = false;
                await this.saveBioPage();
            }
            if (!this.hasUnsavedChanges) { window.location.href = url; return; }
            this.pendingNavigation = url;
            this.showDiscardModal = true;
        },
        confirmDiscard() {
            this.hasUnsavedChanges = false;
            this._saveInFlight = false;
            this.showDiscardModal = false;
            if (this.pendingNavigation) {
                window.location.href = this.pendingNavigation;
            } else {
                window.close();
            }
        },
        cancelDiscard() {
            this.showDiscardModal = false;
            this.pendingNavigation = null;
        },
        announce(message) {
            // Clear first so repeating the same message is still read out.
            this.announcement = '';
            this.$nextTick(() => { this.announcement = message; });
        },
        // Keyboard alternative to drag and drop (arrow keys on the drag handle).
        moveBlock(index, step) {
            const to = index + step;
            if (to < 0 || to >= this.blocks.length) return;
            const [moved] = this.blocks.splice(index, 1);
            this.blocks.splice(to, 0, moved);
            this.blocks.forEach((b, i) => b.order = i);
            this._orderDirty = true; this.onModelChanged();
            this.announce(`${this.getBlockDisplayTitle(moved)} moved to position ${to + 1} of ${this.blocks.length}`);
            this.$nextTick(() => document.querySelectorAll('#blocks-list .block-drag-handle')[to]?.focus());
        },
        moveSocial(index, step) {
            const list = this.bioPage.social_links || [];
            const to = index + step;
            if (to < 0 || to >= list.length) return;
            const [moved] = list.splice(index, 1);
            list.splice(to, 0, moved);
            this.announce(`${this.platforms[moved.platform]?.name || moved.platform} moved to position ${to + 1} of ${list.length}`);
            this.$nextTick(() => document.querySelectorAll('#socials-list .social-drag-handle')[to]?.focus());
        },
        cycleTab(step, absolute = false) {
            const tabs = ['profile', 'links', 'socials', 'appearance', 'settings'];
            // Arrow keys move from the focused tab, which may differ from the open one.
            const focused = document.activeElement?.id?.replace('editor-tab-', '');
            const current = tabs.indexOf(tabs.includes(focused) ? focused : this.activeTab);
            const next = absolute
                ? (step < 0 ? tabs.length - 1 : 0)
                : (current + step + tabs.length) % tabs.length;
            this.activeTab = tabs[next];
            this.$nextTick(() => document.getElementById('editor-tab-' + tabs[next])?.focus());
        },
        initSortable() {
            // Tabs use x-show, so the list containers exist for the editor's lifetime:
            // create each Sortable once and never destroy/re-create it after a drop.
            this.$nextTick(() => {
                const blocksEl = document.getElementById('blocks-list');
                if (blocksEl && !this.blocksSortable) {
                    this.blocksSortable = this.makeSortable(blocksEl, '.block-drag-handle', (from, to) => {
                        const [moved] = this.blocks.splice(from, 1);
                        this.blocks.splice(to, 0, moved);
                        this.blocks.forEach((b, i) => b.order = i);
                        this._orderDirty = true; this.onModelChanged();
                    });
                }
                const socialsEl = document.getElementById('socials-list');
                if (socialsEl && !this.socialsSortable) {
                    this.socialsSortable = this.makeSortable(socialsEl, '.social-drag-handle', (from, to) => {
                        const list = this.bioPage.social_links;
                        const [moved] = list.splice(from, 1);
                        list.splice(to, 0, moved);
                    });
                }
            });
        },
        makeSortable(el, handle, onMove) {
            return Sortable.create(el, {
                draggable: '.block-item',
                handle,
                animation: 150,
                ghostClass: 'block-ghost',
                chosenClass: 'block-chosen',
                // Pointer-based dragging behaves the same with mouse and touch and
                // avoids the browser's native drag image flicker.
                forceFallback: true,
                fallbackTolerance: 4,
                scroll: true,
                bubbleScroll: true,
                fallbackOnBody: true,
                // The drag ghost is a clone of the item appended to <body>; keep Alpine
                // from initialising it outside its x-for scope ("item is not defined").
                onStart: () => { document.body._x_ignoreMutationObserver = true; },
                onEnd: (evt) => {
                    delete document.body._x_ignoreMutationObserver;
                    const { item, from, oldIndex, newIndex } = evt;
                    if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) return;
                    // Put the node back where it was: Alpine's keyed x-for moves it
                    // once the array changes, so DOM and data never disagree.
                    const siblings = Array.from(from.children).filter(c => c !== item && c.tagName !== 'TEMPLATE');
                    from.insertBefore(item, siblings[oldIndex] ?? null);
                    onMove(oldIndex, newIndex);
                }
            });
        },
        debouncedSaveOrder() {
            clearTimeout(this._orderTimeout);
            this._orderTimeout = setTimeout(() => this.saveBlocksOrder(), 400);
        },
        // Preview
        sendPreviewUpdate() {
            if (!this.previewReady) return;
            const frame = this.$refs.previewFrame;
            if (!frame || !frame.contentWindow) return;
            try {
                // Read-only: postMessage copies the data itself, so no JSON round trip here.
                const bioPageData = Alpine.raw(this.bioPage);
                const blocksData = Alpine.raw(this.blocks);
                // Helper to format avatar URL
                const formatAvatarUrl = (url) => {
                    if (!url) return null;
                    if (url.startsWith('/storage/') || url.startsWith('http')) return url;
                    return '/storage/' + url;
                };
                const previewData = {
                        profile: {
                            displayName: bioPageData.title || 'Your Name',
                            bio: bioPageData.bio || '',
                            avatar: formatAvatarUrl(bioPageData.avatar_url),
                            avatarShape: bioPageData.avatar_shape || 'circle',
                            badge: bioPageData.badge || null,
                            badgeColor: bioPageData.badge_color || '#3b82f6'
                        },
                        blocks: blocksData,
                        socials: {
                            position: bioPageData.social_icons_position === 'bottom_page' ? 'bottom' : 'top',
                            // Same rule as the public page: shown unless switched off, and only with a value.
                            items: (bioPageData.social_links || []).filter(s => s && ![false, 0, '0', 'false'].includes(s.enabled ?? s.is_active ?? true) && String(s.value || s.url || '').trim() !== '').map((s, i) => ({
                                id: i,
                                platform: s.platform,
                                url: s.value || s.url || ''
                            }))
                        },
                        design: {
                            // Avatar and header
                            avatarShape: bioPageData.avatar_shape || 'circle',
                            headerLayout: bioPageData.header_layout || 'classic',
                            coverImage: formatAvatarUrl(bioPageData.cover_image),
                            // Background
                            backgroundColor: bioPageData.background_type === 'solid' ? 
                                (bioPageData.background_value || bioPageData.background_color || '#f8fafc') : 
                                (bioPageData.background_color || '#f8fafc'),
                            backgroundType: bioPageData.background_type || 'solid',
                            backgroundGradient: bioPageData.background_type === 'gradient' ? 
                                (bioPageData.background_value || bioPageData.background_gradient) : null,
                            backgroundImage: bioPageData.background_type === 'image' ? 
                                (bioPageData.background_value && (bioPageData.background_value.startsWith('http') || bioPageData.background_value.startsWith('/storage/'))
                                    ? bioPageData.background_value 
                                    : (bioPageData.background_value && !bioPageData.background_value.startsWith('linear-gradient') && !bioPageData.background_value.startsWith('#')
                                        ? '/storage/' + bioPageData.background_value 
                                        : (bioPageData.background_image 
                                            ? (bioPageData.background_image.startsWith('http') || bioPageData.background_image.startsWith('/storage/') 
                                                ? bioPageData.background_image 
                                                : '/storage/' + bioPageData.background_image)
                                            : null))) 
                                : null,
                            // Typography
                            textColor: bioPageData.text_color || '#1e293b',
                            titleColor: bioPageData.title_color || bioPageData.text_color || CONFIG.defaults.title,
                            bioColor: bioPageData.bio_color || CONFIG.defaults.bio,
                            fontFamily: bioPageData.font_family || 'inter',
                            // Buttons
                            buttonBgColor: bioPageData.link_bg_color || bioPageData.button_bg_color || CONFIG.defaults.link_bg,
                            buttonTextColor: bioPageData.link_text_color || bioPageData.button_text_color || CONFIG.defaults.link_text,
                            buttonShape: bioPageData.block_shape || bioPageData.button_shape || 'rounded-sm',
                            buttonShadow: bioPageData.block_shadow || bioPageData.button_shadow || 'md',
                            // Animations
                            hoverEffect: bioPageData.hover_effect || 'none',
                            backgroundAnimation: bioPageData.background_animation || 'none',
                            // Theme
                            theme: bioPageData.theme || 'default'
                        },
                        settings: {
                            hideBranding: bioPageData.hide_branding || false
                        }
                    };
                // Only the parts that changed since the last message go over: the preview then touches less of its page.
                this._pvLast ||= {};
                const data = {};
                for (const part of ['profile', 'blocks', 'socials', 'design', 'settings']) {
                    const json = JSON.stringify(previewData[part]);
                    if (json !== this._pvLast[part]) { this._pvLast[part] = json; data[part] = previewData[part]; }
                }
                if (!Object.keys(data).length) return;
                frame.contentWindow.postMessage({ type: 'preview-update', data }, window.location.origin);
            } catch (e) {
                console.warn('Preview update failed:', e);
            }
        },
        refreshPreview() {
            const frame = this.$refs.previewFrame;
            if (frame) frame.src = frame.src;
        },
        onPreviewLoad() {
        },
        // Block Management
        addBlock(type) {
            this.linkInputMode = 'create';
            const newBlock = {
                id: Date.now(),
                type: type,
                title: this.getDefaultBlockTitle(type),
                url: '',
                content: '',
                thumbnail_url: null,
                is_active: true,
                order: this.blocks.length,
                brand: null,
                is_new: true, // Mark as new
                // Advanced block fields
                embed_url: '',
                countdown_date: '',
                countdown_label: '',
                map_address: '',
                map_zoom: 15,
                vcard_name: '',
                vcard_phone: '',
                vcard_email: '',
                vcard_company: '',
                faq_items: [],
                html_content: '',
                description: '',
                settings: type === 'product' ? { price: '', currency: 'IDR', sold_out: false } : (type === 'collection' ? { layout: 'stack' } : null)
            };
            this.blocks.push(newBlock);
            if (['link', 'header', 'text', 'video', 'music', 'vcard', 'html', 'countdown', 'map', 'faq', 'youtube', 'spotify', 'soundcloud', 'code', 'image', 'product', 'collection'].includes(type)) {
                this.editingBlock = JSON.parse(JSON.stringify(newBlock));
            }
            this.showAddBlockModal = false;
        },
        getDefaultBlockTitle(type) {
            const titles = {
                'header': 'Header',
                'text': 'Text Block',
                'video': 'Video',
                'music': 'Music',
                'vcard': 'Contact Card',
                'html': 'Custom HTML',
                'countdown': 'Countdown Timer',
                'map': 'Location',
                'faq': 'FAQ',
                'product': 'Product',
                'collection': 'Collection'
            };
            return titles[type] || '';
        },
        addBrandBlock(brandId) {
            this.linkInputMode = 'create';
            const brand = this.brands[brandId];
            const newBlock = {
                id: Date.now(),
                type: 'link',
                title: brand?.name || brandId,
                url: '',
                content: '',
                thumbnail_url: null,
                is_active: true,
                order: this.blocks.length,
                brand: brandId,
                is_new: true // Mark as new
            };
            this.blocks.push(newBlock);
            this.editingBlock = JSON.parse(JSON.stringify(newBlock));
            this.showAddBlockModal = false;
        },
        addDividerInstant() {
            const newBlock = {
                id: Date.now(),
                type: 'divider',
                title: 'Divider',
                url: '',
                content: '',
                thumbnail_url: null,
                is_active: true,
                order: this.blocks.length
            };
            this.blocks.push(newBlock);
            this.showNotification('Divider added!', 'success');
        },
        openLinkLibrary() {
            this.showLinkLibraryModal = true;
            this.fetchLibraryLinks();
        },
        fetchLibraryLinks() {
            this.libraryLoading = true;
            fetch('/api/links?query=' + encodeURIComponent(this.librarySearch), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                this.libraryLinks = data.data || data;
                this.libraryLoading = false;
            })
            .catch(err => {
                console.error('Error fetching links:', err);
                this.libraryLoading = false;
            });
        },
        selectLibraryLink(link) {
            if (this.editingBlock) {
                this.editingBlock.url = link.short_url;
                this.editingBlock.title = this.editingBlock.title || link.title;
                this.editingBlock._urlError = null;
                // Clear search results
                this.librarySearch = '';
                this.libraryLinks = [];
            }
            this.showLinkLibraryModal = false;
        },
        closeBlockEditor() {
            if (this.editingBlock) {
                const index = this.blocks.findIndex(b => b.id === this.editingBlock.id);
                if (index !== -1) {
                    // If it's marked as new, remove it
                    if (this.blocks[index].is_new) {
                        this.blocks.splice(index, 1);
                    }
                }
            }
            this.editingBlock = null;
        },
        async saveEditingBlock() {
            if (!this.editingBlock) return;
            if (this.editingBlock.type === 'link') {
                if (!this.editingBlock.title || !String(this.editingBlock.title).trim()) {
                    this.showNotification('Title is required', 'error');
                    return;
                }
                this.processUrl(this.editingBlock, true);
                let urlToCheck = String(this.editingBlock.url || '').trim();
                if (!urlToCheck) {
                    this.showNotification('URL is required', 'error');
                    return;
                }
                if (!/^[a-z][a-z0-9+.\-]*:/i.test(urlToCheck)) {
                    urlToCheck = 'https://' + urlToCheck;
                }
                this.editingBlock.url = urlToCheck;
                // Auto-shorten custom web links (not brand/mailto/tel links, not our own short links)
                const isWeb = /^https?:\/\//i.test(urlToCheck);
                if (isWeb && !this.editingBlock.brand && !urlToCheck.includes(window.location.host)) {
                    this.saving = true;
                    try {
                        const response = await fetch('/api/bio/shorten', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                url: urlToCheck,
                                title: this.editingBlock.title
                            })
                        });
                        const data = await response.json().catch(() => ({}));
                        if (response.ok && data.short_url) {
                            this.editingBlock.url = data.short_url;
                        } else if (response.status === 422) {
                            this.editingBlock._urlError = data.message || 'This URL is not allowed.';
                            this.showNotification(this.editingBlock._urlError, 'error');
                            return;
                        } else if (response.status === 429) {
                            this.showNotification('Too many links created. Please wait a moment.', 'error');
                            return;
                        }
                        // Other failures: keep the long URL, the block still saves.
                    } catch (e) {
                        console.error('Auto-shorten failed:', e);
                    } finally {
                        this.saving = false;
                    }
                }
                if (this.editingBlock.brand && !this.validateBrandUrl(this.editingBlock)) {
                    const brandName = this.brands[this.editingBlock.brand]?.name || this.editingBlock.brand;
                    this.showNotification(`URL doesn't match ${brandName}. Please enter a valid ${brandName} URL.`, 'error');
                    return;
                }
            }
            if (this.editingBlock.type === 'youtube') {
                if (!this.editingBlock.embed_url || !this.editingBlock.embed_url.trim()) {
                    this.showNotification('YouTube URL is required', 'error');
                    return;
                }
                if (!this.editingBlock.embed_url.match(/youtube\.com|youtu\.be/i)) {
                    this.showNotification('Please enter a valid YouTube URL', 'error');
                    return;
                }
            }
            if (this.editingBlock.type === 'spotify') {
                if (!this.editingBlock.embed_url || !this.editingBlock.embed_url.trim()) {
                    this.showNotification('Spotify URL is required', 'error');
                    return;
                }
                if (!this.editingBlock.embed_url.match(/spotify\.com/i)) {
                    this.showNotification('Please enter a valid Spotify URL', 'error');
                    return;
                }
            }
            if (this.editingBlock.type === 'soundcloud') {
                if (!this.editingBlock.embed_url || !this.editingBlock.embed_url.trim()) {
                    this.showNotification('SoundCloud URL is required', 'error');
                    return;
                }
                if (!this.editingBlock.embed_url.match(/soundcloud\.com/i)) {
                    this.showNotification('Please enter a valid SoundCloud URL', 'error');
                    return;
                }
            }
            if (this.editingBlock.type === 'map') {
                if (!this.editingBlock.map_address || !this.editingBlock.map_address.trim()) {
                    this.showNotification('Address is required for map block', 'error');
                    return;
                }
            }
            if (this.editingBlock.type === 'countdown') {
                if (!this.editingBlock.countdown_date) {
                    this.showNotification('Target date is required for countdown', 'error');
                    return;
                }
            }
            if (this.editingBlock.type === 'header' && (!this.editingBlock.title || !this.editingBlock.title.trim())) {
                this.showNotification('Header title is required', 'error');
                return;
            }
            const index = this.blocks.findIndex(b => b.id === this.editingBlock.id);
            if (index !== -1) {
                const updatedBlock = { ...this.editingBlock };
                delete updatedBlock.is_new; // Remove new flag
                this.blocks[index] = updatedBlock;
                // Mark block as dirty so it gets saved
                this.markBlockDirty(updatedBlock.id);
            }
            this.editingBlock = null;
        },
        toggleBlock(index) {
            this.blocks[index].is_active = !this.blocks[index].is_active;
            this.markBlockDirty(this.blocks[index].id);
        },
        // Social Icons - Step 1: Select platform
        selectSocialPlatform(platformId) {
            if (!this.bioPage.social_links) {
                this.bioPage.social_links = [];
            }
            if (this.bioPage.social_links.length >= 5) {
                this.showNotification('Maximum 5 social icons allowed', 'error');
                return;
            }
            // Create temporary social object for editing
            this.editingSocial = {
                platform: platformId,
                value: '',
                enabled: true
            };
            this.showSocialPicker = false;
            this.socialSearch = '';
            this.showSocialEditor = true;
        },
        // Social Icons - Step 2: Save the social link
        saveSocialEdit() {
            if (!this.editingSocial?.value?.trim()) {
                this.showNotification('Please enter a username or URL', 'error');
                return;
            }
            if (!this.bioPage.social_links) {
                this.bioPage.social_links = [];
            }
            this.bioPage.social_links.push({
                _uid: this.uid(),
                platform: this.editingSocial.platform,
                value: this.editingSocial.value.trim(),
                enabled: true
            });
            this.showSocialEditor = false;
            this.editingSocial = null;
            this.showNotification('Social icon added!', 'success');
        },
        // Social Icons - Cancel editing
        cancelSocialEdit() {
            this.showSocialEditor = false;
            this.editingSocial = null;
        },
        // Legacy addSocial for backwards compatibility
        addSocial(platformId) {
            this.selectSocialPlatform(platformId);
        },
        removeSocial(index) {
            this.bioPage.social_links.splice(index, 1);
        },
        get filteredPlatforms() {
            const search = (this.socialSearch || '').toLowerCase();
            if (!this.platforms) return {};
            if (!search) return this.platforms;
            return Object.fromEntries(
                Object.entries(this.platforms).filter(([key, val]) => 
                    (val.name && val.name.toLowerCase().includes(search)) || key.includes(search)
                )
            );
        },
        async uploadAvatar(event) {
            const file = event.target.files[0];
            if (!file) return;
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                this.showNotification('Avatar must be less than 2MB', 'error');
                event.target.value = '';
                return;
            }
            // Validate file type
            if (!file.type.startsWith('image/')) {
                this.showNotification('Please select an image file', 'error');
                event.target.value = '';
                return;
            }
            // The avatar changes the page revision too. Keep it in the same
            // save lane so the following page save cannot use an older token.
            if (this._saveInFlight) {
                this.showNotification('Please wait for the current save, then upload the avatar.', 'info');
                event.target.value = '';
                return;
            }
            this._saveInFlight = true;
            const formData = new FormData();
            formData.append('avatar', file);
            this.showNotification('Uploading avatar...', 'info');
            await fetch(bioEditorUrl('/upload-avatar'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    return res.json().then(data => {
                        throw new Error(data.message || 'Upload failed');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.avatar_url) {
                    // Store just the path without /storage/ prefix for consistency
                    const avatarPath = data.avatar_url.replace('/storage/', '');
                    this.bioPage.avatar_url = avatarPath;
                    this._pageSnap.avatar_url = JSON.stringify(avatarPath); // the server already has it
                    this.noteRevision(data.page_updated_at);
                    this.showNotification('Avatar uploaded!', 'success');
                } else if (data.success === false) {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(err => {
                console.error('Avatar upload error:', err);
                this.showNotification('Failed to upload: ' + err.message, 'error');
            })
            .finally(() => {
                event.target.value = '';
                this._saveInFlight = false;
                if (this._saveQueued) {
                    this._saveQueued = false;
                    this.saveBioPage();
                }
            });
        },
        // Button colours live in link_* (and the older button_* pair); a custom colour means the Default theme.
        setButtonColor(which, value) {
            if (which === 'bg') { this.bioPage.link_bg_color = value; this.bioPage.button_bg_color = value; }
            else { this.bioPage.link_text_color = value; this.bioPage.button_text_color = value; }
            if (this.bioPage.theme !== 'default') this.bioPage.theme = 'default';
        },
        uploadCoverImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', csrf());
            fetch(bioEditorUrl('/upload-image'), { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.path) {
                    this.bioPage.cover_image = data.path;
                    this.showNotification('Cover uploaded!', 'success');
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(error => this.showNotification(error.message || 'Failed to upload', 'error'));
        },
        uploadBackgroundImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', csrf());
            fetch(bioEditorUrl('/upload-image'), {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.url) {
                    this.bioPage.background_image = data.path || data.image_url;
                    this.bioPage.background_value = data.url;
                    this.bioPage.background_type = 'image';
                    this.showNotification('Background uploaded!', 'success');
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(error => this.showNotification(error.message || 'Failed to upload', 'error'));
        },
        applyTheme(themeId) {
            this.bioPage.theme = themeId;
            const theme = this.themes[themeId];
            if (theme) {
                const isGradient = theme.background.includes('gradient');
                this.bioPage.background_type = isGradient ? 'gradient' : 'solid';
                if (isGradient) {
                    this.bioPage.background_value = theme.background;
                    this.bioPage.background_gradient = theme.background;
                } else {
                    this.bioPage.background_value = theme.background;
                    this.bioPage.background_color = theme.background;
                }
                this.bioPage.title_color = theme.text;
                this.bioPage.bio_color = theme.bio;
                this.bioPage.link_bg_color = theme.link_bg;
                this.bioPage.button_bg_color = theme.link_bg;
                this.bioPage.link_text_color = theme.link_text || theme.text;
                this.bioPage.button_text_color = theme.link_text || theme.text;
                this.resetBlockColors();
            }
        },
        // Clear per-block button colours so the theme applies (live blocks, not the stale page copy).
        resetBlockColors() {
            this.blocks.forEach(block => {
                if (block.type === 'link' && (block.btn_bg_color || block.btn_text_color || block.btn_border_color)) {
                    block.btn_bg_color = null;
                    block.btn_text_color = null;
                    block.btn_border_color = null;
                    this.markBlockDirty(block.id);
                }
            });
        },
        /**
         * Copy style from a theme to the Default theme (custom mode)
         * @param {string} themeId Source theme ID
         * @param {string} what What to copy: 'button', 'background', or 'all'
         */
        copyThemeStyle(themeId, what) {
            const theme = this.themes[themeId];
            if (!theme) {
                this.showNotification('Theme not found', 'error');
                return;
            }
            // Switch to Default theme first
            this.bioPage.theme = 'default';
            if (what === 'button' || what === 'all') {
                // Copy button colors
                this.bioPage.link_bg_color = theme.link_bg;
                this.bioPage.button_bg_color = theme.link_bg;
                this.bioPage.link_text_color = theme.link_text || theme.text;
                this.bioPage.button_text_color = theme.link_text || theme.text;
                // Also copy text colors for consistency
                this.bioPage.title_color = theme.text;
                this.bioPage.bio_color = theme.bio;
                this.resetBlockColors();
            }
            if (what === 'background' || what === 'all') {
                // Copy background
                const isGradient = theme.background.includes('gradient');
                this.bioPage.background_type = isGradient ? 'gradient' : 'solid';
                if (isGradient) {
                    this.bioPage.background_value = theme.background;
                    this.bioPage.background_gradient = theme.background;
                } else {
                    this.bioPage.background_value = theme.background;
                    this.bioPage.background_color = theme.background;
                }
            }
            const whatLabel = what === 'button' ? 'Button style' : what === 'background' ? 'Background' : 'Style';
            this.showNotification(`${whatLabel} copied from ${theme.name}!`, 'success');
        },
        // Deletes blocks removed in the editor. Failed ids stay queued for the next save (M3).
        async processDeletions() {
            if (this.deletedBlockIds.length === 0) return 0;
            const results = await Promise.all(this.deletedBlockIds.map(id =>
                this.deleteBlockFromServer(id).then(() => null, () => id)
            ));
            this.deletedBlockIds = results.filter(id => id !== null);
            return this.deletedBlockIds.length;
        },
        // Countdown targets are saved as an exact moment (UTC), not a wall-clock time (M13).
        toLocalInput(value) {
            const d = value ? new Date(value) : null;
            if (!d || isNaN(d)) return '';
            const p = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
        },
        fromLocalInput(value) {
            const d = value ? new Date(value) : null;
            return d && !isNaN(d) ? d.toISOString() : '';
        },
        noteRevision(updatedAt) {
            if (updatedAt && (!this._baseUpdatedAt || new Date(updatedAt) >= new Date(this._baseUpdatedAt))) {
                this._baseUpdatedAt = updatedAt;
            }
        },
        // Saves what changed, in one pass: the page fields that differ, then block creates/updates/deletes in
        // parallel, then the order once. One save at a time; edits made meanwhile are saved by the next pass.
        async saveBioPage() {
            if (this._saveInFlight) {
                this._saveQueued = true;
                return;
            }
            clearTimeout(this._autoTimeout);
            if (!this.hasPending()) {
                this.hasUnsavedChanges = false;
                this.saveState = 'saved';
                return;
            }
            this._saveInFlight = true;
            this.saving = true;
            this.saveState = 'saving';
            this.fieldErrors = {};
            this.saveError = '';
            let failed = false;
            try {
                const keys = this.changedPageKeys();
                if (keys.length) await this.savePageFields(keys);
                const failedDeletes = await this.processDeletions();
                await this.saveBlocks();
                if (failedDeletes > 0) {
                    throw new Error(`${failedDeletes} deleted block(s) could not be removed. Save again to retry.`);
                }
            } catch (error) {
                failed = true;
                console.error('Save failed:', error);
                this.saveError = error.message || 'Failed to save';
                this._autoBlocked = true; // no retry loop: the next edit (or the Save button) tries again
                this.showNotification(this.saveError, 'error');
            } finally {
                this.saving = false;
                this._saveInFlight = false;
                this.hasUnsavedChanges = this.hasPending();
                this.saveState = failed ? 'error' : (this.hasUnsavedChanges ? 'dirty' : 'saved');
                if (!failed && this._saveQueued) {
                    this._saveQueued = false;
                    this.saveBioPage();
                } else if (!failed) {
                    this.scheduleAutosave();
                }
                this._saveQueued = false;
            }
        },
        // Takes you to the tab that holds the field the server complained about.
        showTabOf(key) {
            const base = String(key || '').split('.')[0];
            const tab = ['title', 'bio', 'badge', 'badge_color', 'avatar_shape', 'avatar_url'].includes(base) ? 'profile'
                : base === 'social_links' || base.startsWith('social') ? 'socials'
                : /color|background|theme|font|shape|shadow|hover|animation|layout|custom_css|header/.test(base) ? 'appearance'
                : 'settings';
            this.activeTab = tab;
        },
        async savePageFields(keys) {
            const raw = Alpine.raw(this.bioPage), sentSnap = this.pageSnapshot(), body = {};
            for (const key of keys) {
                body[key] = key === 'social_links' ? (raw[key] || []).map(({ _uid, ...social }) => social) : raw[key];
            }
            if (body.socials && !Array.isArray(body.socials)) delete body.socials;
            body.base_updated_at = this._baseUpdatedAt;
            const response = await fetch(bioEditorUrl(''), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                body: JSON.stringify(body)
            });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                if (response.status === 422 && errorData.errors) {
                    // Show each problem next to its field (and in the banner), not only in a short toast.
                    this.fieldErrors = Object.fromEntries(Object.entries(errorData.errors).map(([k, v]) => [k, v[0]]));
                    this.showTabOf(Object.keys(this.fieldErrors)[0]);
                    const first = Object.values(this.fieldErrors)[0];
                    const where = Object.keys(this.fieldErrors).map(k => k.replace(/_/g, ' ')).slice(0, 3).join(', ');
                    throw new Error(`${first || 'Some fields are not valid'}${Object.keys(this.fieldErrors).length > 1 ? ` (also: ${where})` : ''}`);
                }
                if (response.status === 409) throw new Error(errorData.message || 'This page was changed in another tab. Reload the editor.');
                if (response.status === 419) throw new Error('Your session expired. Reload the page and sign in again.');
                throw new Error(errorData.message || 'Failed to save');
            }
            const saved = await response.json().catch(() => ({}));
            this.noteRevision(saved.bioPage?.updated_at);
            // What was sent is now what the server has.
            for (const key of keys) this._pageSnap[key] = sentSnap[key];
            if (saved.bioPage?.slug && saved.bioPage.slug !== window.bioEditorSlug) {
                window.bioEditorSlug = saved.bioPage.slug;
                this.previewUrl = bioEditorUrl('/preview');
            }
        },
        async saveBlocks() {
            const pending = new Set(this.dirtyBlockIds);
            const jobs = [];
            let created = false;
            for (const block of this.blocks) {
                if (block.is_new) continue; // still being composed in the block editor
                const key = this.blockKey(Alpine.raw(block));
                if (block.id >= 1000000000) {
                    created = true;
                    jobs.push(this.createBlock(block).then(() => { this._blockSnap[block.id] = key; }));
                } else if (pending.has(block.id) || key !== this._blockSnap[block.id]) {
                    jobs.push(this.updateBlock(block).then(() => { this._blockSnap[block.id] = key; }));
                }
            }
            if (jobs.length) {
                const results = await Promise.allSettled(jobs);
                const rejected = results.find(r => r.status === 'rejected');
                if (rejected) throw new Error(rejected.reason?.message || 'A block could not be saved');
            }
            // Only the ids that were just saved are forgotten: edits made while saving stay pending.
            pending.forEach(id => this.dirtyBlockIds.delete(id));
            // New blocks got real ids: persist the full order once.
            if ((created || this._orderDirty) && !(await this.saveBlocksOrder())) {
                throw new Error('The block order could not be saved');
            }
        },
        markBlockDirty(blockId) {
            if (blockId && blockId < 1000000000) {
                this.dirtyBlockIds.add(blockId);
            }
        },
        async createBlock(block) {
            const response = await fetch(bioEditorUrl('/links'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(block)
            });
            const data = await response.json().catch(() => ({}));
            if (response.ok) {
                block.id = data.block?.id || data.id;
                this.noteRevision(data.page_updated_at);
            } else {
                throw new Error(data.message || 'Failed to create block');
            }
        },
        async updateBlock(block) {
            const response = await fetch(bioEditorUrl(`/links/${block.id}`), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(block)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Failed to update block');
            }
            this.noteRevision(data.page_updated_at);
        },
        async deleteBlockFromServer(blockId) {
            const response = await fetch(bioEditorUrl(`/links/${blockId}`), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
            });
            // 404: already gone (deleted from another tab), nothing left to retry.
            if (!response.ok && response.status !== 404) {
                throw new Error('Failed to delete block');
            }
            this.noteRevision((await response.json().catch(() => ({}))).page_updated_at);
        },
        async saveBlocksOrder() {
            const linksData = this.blocks
                .filter(b => b.id < 1000000000)
                .map((b, idx) => ({ id: b.id, order: idx }));
            if (linksData.length === 0) return true;
            try {
                const response = await fetch(bioEditorUrl('/links/reorder'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ links: linksData })
                });
                if (!response.ok) throw new Error(response.status);
                this.noteRevision((await response.json().catch(() => ({}))).page_updated_at);
                this._orderDirty = false;
                return true;
            } catch (e) {
                // Autosave of the order must not fail silently (M4): keep the change pending.
                console.error('Reorder failed:', e);
                this._orderDirty = true;
                this.hasUnsavedChanges = true;
                this.showNotification('Could not save the new order. Click Save to retry.', 'error');
                return false;
            }
        },
        getFontFamily(fontValue) {
            const fontMap = {
                'inter': "'Inter', system-ui, sans-serif",
                'poppins': "'Poppins', system-ui, sans-serif",
                'roboto': "'Roboto', system-ui, sans-serif",
                'montserrat': "'Montserrat', system-ui, sans-serif",
                'nunito': "'Nunito', system-ui, sans-serif",
                'open-sans': "'Open Sans', system-ui, sans-serif",
                'lato': "'Lato', system-ui, sans-serif",
                'raleway': "'Raleway', system-ui, sans-serif",
                'quicksand': "'Quicksand', system-ui, sans-serif",
                'dm-sans': "'DM Sans', system-ui, sans-serif",
                'manrope': "'Manrope', system-ui, sans-serif",
                'plus-jakarta-sans': "'Plus Jakarta Sans', system-ui, sans-serif",
                'lexend': "'Lexend', system-ui, sans-serif",
                'space-grotesk': "'Space Grotesk', system-ui, sans-serif",
                'work-sans': "'Work Sans', system-ui, sans-serif",
                'oswald': "'Oswald', system-ui, sans-serif",
                'playfair-display': "'Playfair Display', Georgia, serif",
                'merriweather': "'Merriweather', Georgia, serif"
            };
            return fontMap[fontValue] || "'Inter', system-ui, sans-serif";
        },
        getFontDisplayName(fontValue) {
            const nameMap = {
                'inter': 'Inter',
                'poppins': 'Poppins',
                'roboto': 'Roboto',
                'montserrat': 'Montserrat',
                'nunito': 'Nunito',
                'open-sans': 'Open Sans',
                'lato': 'Lato',
                'raleway': 'Raleway',
                'quicksand': 'Quicksand',
                'dm-sans': 'DM Sans',
                'manrope': 'Manrope',
                'plus-jakarta-sans': 'Plus Jakarta Sans',
                'lexend': 'Lexend',
                'space-grotesk': 'Space Grotesk',
                'work-sans': 'Work Sans',
                'oswald': 'Oswald',
                'playfair-display': 'Playfair Display',
                'merriweather': 'Merriweather'
            };
            return nameMap[fontValue] || 'Inter';
        },
        getPlatformIcon(brandId) {
            if (!brandId) return '/images/brands/generic-website.svg?v=20251212';
            const brand = this.brands[brandId];
            return brand?.icon ? `/images/brands/${brand.icon}?v=20251212` : `/images/brands/${brandId}.svg?v=20251212`;
        },
        getPlatformColor(brandId) {
            return this.brands[brandId]?.color || '#6366f1';
        },
        // Get current active theme object
        getCurrentTheme() {
            const themeName = this.bioPage.theme || 'default';
            return this.themes[themeName] || this.themes['default'];
        },
        // Get effective button background color (respects theme)
        getEffectiveButtonBg() {
            const theme = this.getCurrentTheme();
            if (this.bioPage.link_bg_color) {
                return this.bioPage.link_bg_color;
            }
            return theme?.link_bg || '#6366f1';
        },
        // Get effective button text color (respects theme)
        getEffectiveButtonText() {
            const theme = this.getCurrentTheme();
            if (this.bioPage.link_text_color) {
                return this.bioPage.link_text_color;
            }
            return theme?.link_text || '#ffffff';
        },
        // Check if block is using auto style (no custom colors set)
        isBlockAutoStyle(block) {
            if (!block) return true;
            return !block.btn_bg_color && !block.btn_text_color && !block.btn_border_color && !block.btn_icon_invert;
        },
        // Toggle auto style for a block
        toggleBlockAutoStyle(block, isAuto) {
            if (isAuto) {
                // Reset all custom styles to follow theme
                block.btn_bg_color = null;
                block.btn_text_color = null;
                block.btn_border_color = null;
                block.btn_icon_invert = false;
            } else {
                // Set initial values based on current theme
                block.btn_bg_color = this.getEffectiveButtonBg();
                block.btn_text_color = this.getEffectiveButtonText();
                block.btn_border_color = null;
                block.btn_icon_invert = false;
            }
            this.markBlockDirty(block.id);
        },
        // Reset block to theme default
        resetBlockToTheme(block) {
            block.btn_bg_color = null;
            block.btn_text_color = null;
            block.btn_border_color = null;
            block.btn_icon_invert = false;
            this.markBlockDirty(block.id);
        },
        // Get auto icon filter based on theme button color
        getAutoIconFilter() {
            const bgColor = this.getEffectiveButtonBg();
            const isLight = this.isLightColor(bgColor);
            return isLight ? 'brightness(0)' : 'brightness(0) invert(1)';
        },
        getSocialPlaceholder(platform) {
            const placeholders = {
                'whatsapp': '+1 234 567 8900',
                'telegram': '@username or phone',
                'twitter': '@username',
                'x': '@username',
                'instagram': '@username',
                'facebook': 'facebook.com/yourpage',
                'youtube': 'youtube.com/@channel',
                'tiktok': '@username',
                'linkedin': 'linkedin.com/in/yourprofile',
                'github': 'github.com/username',
                'email': 'your@email.com',
                'website': 'https://yourwebsite.com',
                'discord': 'discord.gg/invite',
                'spotify': 'open.spotify.com/artist/...',
                'threads': '@username'
            };
            return placeholders[platform] || 'Enter your URL or handle';
        },
        validateSocialInput(index) {
            const social = this.bioPage.social_links[index];
            if (!social || !social.value) return;
            let value = social.value.trim();
            const atPrefixPlatforms = ['instagram', 'twitter', 'x', 'tiktok', 'threads'];
            if (atPrefixPlatforms.includes(social.platform) && !value.startsWith('@') && !value.includes('/')) {
                value = '@' + value;
            }
            this.bioPage.social_links[index].value = value;
        },
        getBlockTypeColor(type) {
            const colors = {
                'link': '#3b82f6',
                'text': '#10b981',
                'image': '#ec4899',
                'divider': '#6b7280',
                'spacer': '#6b7280',
                'header': '#8b5cf6'
            };
            return colors[type] || '#6366f1';
        },
        getBlockDisplayTitle(block) {
            if (!block) return 'Untitled';
            if (block.type === 'text' && block.content) {
                const temp = document.createElement('div');
                temp.innerHTML = block.content;
                const text = temp.textContent || temp.innerText || '';
                return text.substring(0, 50) + (text.length > 50 ? '...' : '') || 'Text Block';
            }
            if (block.type === 'image') {
                return block.title || 'Image';
            }
            if (block.type === 'divider') {
                return 'Divider';
            }
            if (block.type === 'header') {
                return block.title || 'Header';
            }
            return block.title || block.content || 'Untitled';
        },
        getBlockTypeIcon(type) {
            const icons = {
                'link': '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>',
                'text': '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>',
                'image': '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                'divider': '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>',
                'spacer': '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>',
                'header': '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>'
            };
            return icons[type] || icons['link'];
        },
        addQuickBlock(type) {
            this.showAddBlockModal = false;
            if (type === 'text') {
                this.textEditorBlock = {
                    id: Date.now(),
                    type: 'text',
                    title: '',
                    url: '',
                    content: '',
                    is_active: true,
                    order: this.blocks.length
                };
                this.showTextEditor = true;
                this.$nextTick(() => {
                    if (this.$refs.richTextEditor) {
                        this.$refs.richTextEditor.innerHTML = '';
                        this.$refs.richTextEditor.focus();
                    }
                });
            } else if (type === 'image') {
                this.showImageUploader = true;
                this.imageUploadTab = 'upload';
                this.imageBlockUrl = '';
                this.imageBlockAlt = '';
                this.imageBlockLink = '';
            } else {
                this.addBlock(type);
            }
        },
        openBlockEditor(block) {
            if (block.type === 'text') {
                this.textEditorBlock = JSON.parse(JSON.stringify(block));
                this.showTextEditor = true;
                this.$nextTick(() => {
                    if (this.$refs.richTextEditor) {
                        this.$refs.richTextEditor.innerHTML = block.content || '';
                    }
                });
            } else {
                this.linkInputMode = 'create';
                this.editingBlock = JSON.parse(JSON.stringify(block));
            }
        },
        // ---- select mode, archive, restore ----
        toggleSelectMode() { this.selectMode = !this.selectMode; this.selectedIds = []; },
        toggleSelected(id) { this.selectedIds = this.selectedIds.includes(id) ? this.selectedIds.filter(x => x !== id) : [...this.selectedIds, id]; },
        selectAllBlocks() { this.selectedIds = this.selectedIds.length === this.blocks.length ? [] : this.blocks.map(b => b.id); },
        bulkSetActive(on) {
            this.blocks.forEach(b => { if (this.selectedIds.includes(b.id) && b.is_active !== on) { b.is_active = on; this.markBlockDirty(b.id); } });
            this.selectedIds = [];
        },
        bulkDelete() {
            if (!confirm('Delete ' + this.selectedIds.length + ' block(s)? Archive keeps their clicks; delete does not.')) return;
            [...this.selectedIds].forEach(id => { const i = this.blocks.findIndex(b => b.id === id); if (i >= 0) this.removeBlock(i); });
            this.selectedIds = [];
        },
        async bulkArchive() {
            const ids = [...this.selectedIds];
            for (const id of ids) { const i = this.blocks.findIndex(b => b.id === id); if (i >= 0) await this.archiveBlock(i, true); }
            this.selectedIds = [];
            this.showNotification(ids.length + ' archived', 'success');
        },
        async archiveBlock(index, quiet = false) {
            const block = this.blocks[index];
            if (!block || block.id >= 1000000000) { this.removeBlock(index); return; }
            try {
                const res = await fetch(bioEditorUrl('/links/' + block.id + '/archive'), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Archive failed');
                this.blocks.splice(index, 1);
                this.blocks.forEach((b, i) => b.order = i);
                this.archived.unshift(data.link);
                if (!quiet) this.showNotification('Archived. Restore it any time from the archived list.', 'success');
                this.refreshPreview && this.refreshPreview();
            } catch (e) { this.showNotification(e.message || 'Failed to archive', 'error'); }
        },
        async restoreBlock(item) {
            try {
                const res = await fetch(bioEditorUrl('/links/' + item.id + '/restore'), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Restore failed');
                this.archived = this.archived.filter(a => a.id !== item.id);
                this.blocks.push(data.link);
                this.showNotification('Restored', 'success');
                this.refreshPreview && this.refreshPreview();
            } catch (e) { this.showNotification(e.message || 'Failed to restore', 'error'); }
        },
        async deleteArchived(item) {
            if (!confirm('Delete this archived block for good?')) return;
            await this.deleteBlockFromServer(item.id);
            this.archived = this.archived.filter(a => a.id !== item.id);
        },
        removeBlock(index) {
            const block = this.blocks[index];
            this.blocks.splice(index, 1);
            this.blocks.forEach((b, i) => b.order = i);
            if (block.id && block.id < 1000000000) {
                this.deletedBlockIds.push(block.id);
                this.checkForChanges();
            }
        },
        openTextEditor(block) {
            this.textEditorBlock = JSON.parse(JSON.stringify(block));
            this.showTextEditor = true;
            this.$nextTick(() => {
                if (this.$refs.richTextEditor) {
                    this.$refs.richTextEditor.innerHTML = this.textEditorBlock?.content || '';
                }
            });
        },
        formatText(command) {
            document.execCommand(command, false, null);
            if (this.$refs.richTextEditor) {
                this.$refs.richTextEditor.focus();
            }
        },
        updateTextBlockContent(event) {
            if (this.textEditorBlock) {
                this.textEditorBlock.content = event.target.innerHTML;
            }
        },
        saveTextBlock() {
            if (this.textEditorBlock) {
                const index = this.blocks.findIndex(b => b.id === this.textEditorBlock.id);
                if (index !== -1) {
                    this.blocks[index] = { ...this.textEditorBlock };
                } else {
                    this.blocks.push(this.textEditorBlock);
                }
                if (this.editingBlock && this.editingBlock.id === this.textEditorBlock.id) {
                    this.editingBlock.content = this.textEditorBlock.content;
                }
                this.markBlockDirty(this.textEditorBlock.id);
            }
            this.showTextEditor = false;
            this.textEditorBlock = null;
        },
        async uploadBlockImage(event, block) {
            const file = event.target.files[0];
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                this.showNotification('Please select an image file', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.showNotification('Image size must be less than 5MB', 'error');
                return;
            }
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', csrf());
            this.saving = true;
            try {
                const response = await fetch(bioEditorUrl('/upload-image'), {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.image_url) {
                    block.content = block.thumbnail_url = data.image_url.startsWith('http') || data.image_url.startsWith('/') ? data.image_url : '/storage/' + data.image_url;
                    this.markBlockDirty(block.id);
                    this.showNotification('Image uploaded!', 'success');
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            } catch(e) {
                console.error('Upload error:', e);
                this.showNotification('Failed to upload image', 'error');
            } finally {
                this.saving = false;
            }
        },
        async uploadImageBlock(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.saving = true;
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', csrf());
            try {
                const response = await fetch(bioEditorUrl('/upload-image'), {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.image_url) {
                    const imageUrl = data.image_url.startsWith('/') ? data.image_url : '/storage/' + data.image_url;
                    const newBlock = {
                        id: Date.now(),
                        type: 'image',
                        title: file.name || '',
                        url: '',
                        content: imageUrl,
                        thumbnail_url: imageUrl,
                        is_active: true,
                        order: this.blocks.length
                    };
                    this.blocks.push(newBlock);
                    this.showImageUploader = false;
                    this.imageBlockUrl = '';
                    this.imageBlockAlt = '';
                    this.showNotification('Image uploaded!', 'success');
                } else {
                    throw new Error('No image URL returned');
                }
            } catch (error) {
                console.error('Image upload error:', error);
                this.showNotification('Failed to upload image', 'error');
            } finally {
                this.saving = false;
            }
        },
        saveImageBlock() {
            if (this.imageBlockUrl) {
                const newBlock = {
                    id: Date.now(),
                    type: 'image',
                    title: this.imageBlockAlt || '',
                    url: '',
                    content: this.imageBlockUrl,
                    thumbnail_url: this.imageBlockUrl,
                    is_active: true,
                    order: this.blocks.length
                };
                this.blocks.push(newBlock);
                this.showImageUploader = false;
                this.imageBlockUrl = '';
                this.imageBlockAlt = '';
                this.showNotification('Image added!', 'success');
            } else {
                this.showNotification('Please enter an image URL', 'error');
            }
        },
        openIconPicker(block = null) {
            this.iconPickerBlock = block;
            this.showIconPicker = true;
            this.iconSearch = '';
        },
        openIconPickerSafe(block) {
            if (!block) {
                console.error('No block provided to icon picker');
                return;
            }
            this.iconPickerBlock = block;
            this.showIconPicker = true;
            this.iconSearch = '';
        },
        setBlockIcon(brandId) {
            const targetBlock = this.iconPickerBlock || this.editingBlock;
            if (targetBlock) {
                targetBlock.brand = brandId;
                if (!targetBlock.title) {
                    targetBlock.title = this.brands[brandId]?.name || brandId;
                }
                if (this.iconPickerBlock) {
                    const index = this.blocks.findIndex(b => b.id === this.iconPickerBlock.id);
                    if (index !== -1) {
                        this.blocks[index].brand = brandId;
                        if (!this.blocks[index].title) {
                            this.blocks[index].title = this.brands[brandId]?.name || brandId;
                        }
                    }
                }
            }
            this.showIconPicker = false;
            this.iconPickerBlock = null;
        },
        async uploadCustomIcon(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 1024 * 1024) {
                this.showNotification('Icon must be less than 1MB', 'error');
                event.target.value = '';
                return;
            }
            const formData = new FormData();
            formData.append('icon', file);
            try {
                const response = await fetch(bioEditorUrl('/upload-icon'), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
                    body: formData
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success || !data.icon_url) {
                    // 422 carries the reason (for example an SVG with scripts); show it instead of a generic failure
                    throw new Error(data.errors?.icon?.[0] || data.message || (response.status === 413 ? 'The file is too large.' : 'Upload failed'));
                }
                const targetBlock = this.iconPickerBlock || this.editingBlock;
                if (targetBlock) {
                    targetBlock.custom_icon = data.icon_url;
                    targetBlock.brand = null;
                    if (this.iconPickerBlock) {
                        const index = this.blocks.findIndex(b => b.id === this.iconPickerBlock.id);
                        if (index !== -1) {
                            this.blocks[index].custom_icon = data.icon_url;
                            this.blocks[index].brand = null;
                            this.markBlockDirty(this.blocks[index].id);
                        }
                    }
                }
                this.showNotification('Icon uploaded!', 'success');
                this.showIconPicker = false;
                this.iconPickerBlock = null;
            } catch (error) {
                this.showNotification(error.message || 'Failed to upload icon', 'error');
                console.error(error);
            }
            event.target.value = '';
        },
        async fetchUrlTitle(block) {
            // Debounced: the editor may already be closed (block is null)
            if (!block || !block.url || (block.title && block.title.trim() !== '')) return;
            let url = block.url.trim();
            // Basic URL validation/normalization
            if (!url.match(/^https?:\/\//i)) {
                url = 'https://' + url;
            }
            try {
                const response = await fetch('/api/utils/fetch-title', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ url: url })
                });
                if (response.ok) {
                    const data = await response.json();
                    // Check again if title is still empty before setting it
                    if (data.title && (!block.title || block.title.trim() === '')) {
                        block.title = data.title;
                    }
                }
            } catch (e) {
                console.error('Error fetching title:', e);
            }
        },
        // Expands handles/phone numbers into full URLs. Runs on blur and before
        // saving - never while typing, so the field doesn't change under the cursor.
        processUrl(block, immediate = false) {
            if (this.urlInputTimeout) clearTimeout(this.urlInputTimeout);
            const run = () => {
                if (!block) return;
                let url = (block.url || '').trim();
                if (!url) return;
                // EARLY CHECK: Detect email pattern first - before any other processing
                // Matches: user@domain.com, test.user+tag@example.co.uk, etc.
                const emailPattern = /^[\w.+\-]+@[\w.\-]+\.[a-z]{2,}$/i;
                const isEmail = emailPattern.test(url.replace(/^mailto:/i, ''));
                // If it looks like an email (with or without mailto:), handle it specially
                if (isEmail || url.toLowerCase().startsWith('mailto:')) {
                    const cleanEmail = url.replace(/^mailto:/i, '').trim();
                    if (emailPattern.test(cleanEmail)) {
                        block.url = 'mailto:' + cleanEmail;
                    }
                    return; // Don't process further - this is an email
                }
                // Handle tel: prefix for phone numbers
                if (url.toLowerCase().startsWith('tel:')) {
                    return; // Already has tel: prefix, don't modify
                }
                // Handle sms: prefix for SMS
                if (url.toLowerCase().startsWith('sms:')) {
                    return; // Already has sms: prefix, don't modify
                }
                // Handle http:// (without s) - keep as is
                if (url.toLowerCase().startsWith('http://')) {
                    return; // Keep http:// as is, don't force https
                }
                const brandId = block.brand;
                const brand = this.brands[brandId];
                const usernamePatterns = {
                    'instagram': 'https://instagram.com/',
                    'x': 'https://x.com/',
                    'threads': 'https://threads.net/@',
                    'tiktok': 'https://tiktok.com/@',
                    'snapchat': 'https://snapchat.com/add/',
                    'telegram': 'https://t.me/',
                    'line': 'https://line.me/ti/p/',
                    'bluesky': 'https://bsky.app/profile/',
                };
                const subdomainPatterns = {
                    'tumblr': '.tumblr.com',
                    'bandcamp': '.bandcamp.com',
                };
                const pathPatterns = {
                    'facebook': 'https://facebook.com/',
                    'youtube': 'https://youtube.com/@',
                    'linkedin': 'https://linkedin.com/in/',
                    'pinterest': 'https://pinterest.com/',
                    'reddit': 'https://reddit.com/u/',
                    'twitch': 'https://twitch.tv/',
                    'github': 'https://github.com/',
                    'gitlab': 'https://gitlab.com/',
                    'spotify': 'https://open.spotify.com/',
                    'soundcloud': 'https://soundcloud.com/',
                    'discord': 'https://discord.gg/',
                    'messenger': 'https://m.me/',
                };
                if (url.match(/^https?:\/\//i)) {
                    return;
                }
                if (url.match(/^[a-z]+:/i)) {
                    return;
                }
                if (url.startsWith('@')) {
                    const username = url.substring(1);
                    if (brandId && usernamePatterns[brandId]) {
                        block.url = usernamePatterns[brandId] + username;
                        return;
                    }
                    return;
                }
                if (brandId === 'whatsapp') {
                    if (url.match(/^\+[\d\s\-()]+$/)) {
                        const phone = url.replace(/[\s\-()]/g, '').replace(/^\+/, '');
                        block.url = 'https://wa.me/' + phone;
                        return;
                    }
                    if (url.match(/^https?:\/\/(wa\.me|api\.whatsapp\.com)/i)) {
                        return;
                    }
                    return;
                }
                if (brandId === 'signal') {
                    if (url.match(/^\+[\d\s\-()]+$/)) {
                        const phone = url.replace(/[\s\-()]/g, '').replace(/^\+/, '');
                        block.url = 'https://signal.me/#p/+' + phone;
                        return;
                    }
                    return;
                }
                if (brandId && subdomainPatterns[brandId]) {
                    if (!url.includes('.')) {
                        block.url = 'https://' + url + subdomainPatterns[brandId];
                        return;
                    }
                }
                if (brandId && pathPatterns[brandId]) {
                    if (!url.includes('.') && !url.includes('/')) {
                        block.url = pathPatterns[brandId] + url;
                        return;
                    }
                }
                if (brandId === 'reddit') {
                    if (url.startsWith('u/') || url.startsWith('r/')) {
                        block.url = 'https://reddit.com/' + url;
                        return;
                    }
                }
                if (brandId === 'discord') {
                    if (!url.includes('.') && !url.includes('/')) {
                        block.url = 'https://discord.gg/' + url;
                        return;
                    }
                }
                // Phone and SMS - handle phone numbers with proper prefixes
                if (brandId === 'phone') {
                    if (url.match(/^\+?[\d\s\-()]+$/)) {
                        const phone = url.replace(/[\s\-()]/g, '');
                        block.url = 'tel:' + phone;
                        return;
                    }
                    return;
                }
                if (brandId === 'sms') {
                    if (url.match(/^\+?[\d\s\-()]+$/)) {
                        const phone = url.replace(/[\s\-()]/g, '');
                        block.url = 'sms:' + phone;
                        return;
                    }
                    return;
                }
                if (brandId === 'email') {
                    // Already handled above in early check
                    return;
                }
                if (url.includes('.')) {
                    block.url = 'https://' + url;
                }
            };
            if (immediate) {
                run();
            } else {
                this.urlInputTimeout = setTimeout(run, 1000);
            }
        },
        validateBrandUrl(block) {
            if (!block.brand || !block.url) return true;
            const brandDomains = {
                // Social Media
                'facebook': ['facebook.com', 'fb.com', 'fb.me'],
                'instagram': ['instagram.com', 'instagr.am'],
                'x': ['x.com', 'twitter.com'],
                'threads': ['threads.net'],
                'tiktok': ['tiktok.com', 'vm.tiktok.com'],
                'youtube': ['youtube.com', 'youtu.be', 'm.youtube.com'],
                'youtube-music': ['music.youtube.com'],
                'linkedin': ['linkedin.com'],
                'pinterest': ['pinterest.com', 'pin.it'],
                'snapchat': ['snapchat.com'],
                'reddit': ['reddit.com'],
                'tumblr': ['tumblr.com'],
                'bluesky': ['bsky.app'],
                'mastodon': [], // Allows any Mastodon instance
                // Messaging
                'whatsapp': ['wa.me', 'api.whatsapp.com'],
                'telegram': ['t.me', 'telegram.me'],
                'messenger': ['m.me', 'messenger.com'],
                'discord': ['discord.gg', 'discord.com'],
                'signal': ['signal.me'],
                'line': ['line.me'],
                'slack': ['slack.com'],
                // Music & Audio
                'spotify': ['open.spotify.com', 'spotify.com'],
                'apple-music': ['music.apple.com'],
                'apple-podcasts': ['podcasts.apple.com'],
                'amazon-music': ['music.amazon.com', 'amazon.com/music'],
                'soundcloud': ['soundcloud.com'],
                'bandcamp': ['bandcamp.com'],
                'deezer': ['deezer.com'],
                'tidal': ['tidal.com', 'listen.tidal.com'],
                'audiomack': ['audiomack.com'],
                // Video & Streaming
                'twitch': ['twitch.tv'],
                'vimeo': ['vimeo.com'],
                'dailymotion': ['dailymotion.com'],
                'kick': ['kick.com'],
                'rumble': ['rumble.com'],
                // Development
                'github': ['github.com'],
                'gitlab': ['gitlab.com'],
                'bitbucket': ['bitbucket.org'],
                'codepen': ['codepen.io'],
                'codesandbox': ['codesandbox.io'],
                'replit': ['replit.com'],
                'stackoverflow': ['stackoverflow.com'],
                'dev': ['dev.to'],
                'hashnode': ['hashnode.dev', 'hashnode.com'],
                // Design & Creative
                'dribbble': ['dribbble.com'],
                'behance': ['behance.net'],
                'figma': ['figma.com'],
                'artstation': ['artstation.com'],
                'deviantart': ['deviantart.com'],
                // Business & Productivity
                'notion': ['notion.so', 'notion.site'],
                'trello': ['trello.com'],
                'medium': ['medium.com'],
                'substack': ['substack.com'],
                'producthunt': ['producthunt.com'],
                'xing': ['xing.com'],
                // Gaming
                'steam': ['steamcommunity.com', 'store.steampowered.com'],
                'playstation': ['playstation.com', 'psnprofiles.com'],
                'xbox': ['xbox.com', 'xboxgamertag.com'],
                'nintendo': ['nintendo.com'],
                'epic-games': ['epicgames.com', 'store.epicgames.com'],
                'gog': ['gog.com'],
                'itch-io': ['itch.io'],
                'osu': ['osu.ppy.sh'],
                'vrchat': ['vrchat.com'],
                'roblox': ['roblox.com'],
                'battlenet': ['battle.net', 'blizzard.com'],
                // Payments & Finance
                'paypal': ['paypal.com', 'paypal.me'],
                'venmo': ['venmo.com'],
                'cashapp': ['cash.app'],
                'ko-fi': ['ko-fi.com'],
                'patreon': ['patreon.com'],
                'buymeacoffee': ['buymeacoffee.com'],
                // Shopping & E-commerce
                'amazon': ['amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.fr', 'amazon.ca', 'amazon.es', 'amazon.it'],
                'ebay': ['ebay.com', 'ebay.co.uk', 'ebay.de'],
                'etsy': ['etsy.com'],
                'shopify': ['myshopify.com'],
                // App Stores
                'appstore': ['apps.apple.com'],
                'playstore': ['play.google.com'],
                // Web3 & NFT
                'opensea': ['opensea.io'],
                'rarible': ['rarible.com'],
                'foundation': ['foundation.app'],
                // Extended platforms
                'clubhouse': ['clubhouse.com'],
                'guilded': ['guilded.gg'],
                'cameo': ['cameo.com'],
                'ngl': ['ngl.link'],
                'researchgate': ['researchgate.net'],
                'orcid': ['orcid.org'],
            };
            const domains = brandDomains[block.brand];
            if (!domains || domains.length === 0) return true;
            try {
                const urlObj = new URL(block.url);
                const hostname = urlObj.hostname.replace('www.', '');
                return domains.some(d => hostname === d || hostname.endsWith('.' + d));
            } catch (e) {
                return false;
            }
        },
        validatePlatformUrl(block) {
            if (!block) return true;
            if (!block.brand || !block.url) {
                block._urlError = null;
                return true;
            }
            const url = (block.url || '').trim();
            if (!url) {
                block._urlError = null;
                return true;
            }
            const brandId = block.brand;
            const brand = this.brands[brandId];
            const validationRules = {
                'signal': {
                    pattern: /^(\+\d{10,15}|https?:\/\/signal\.me\/#p\/\+\d+)$/,
                    message: 'Phone number with country code required (e.g., +1234567890)',
                    example: '+1234567890'
                },
                'whatsapp': {
                    pattern: /^(\+\d{10,15}|https?:\/\/(wa\.me|api\.whatsapp\.com)\/\d+.*)$/,
                    message: 'Phone with country code required (e.g., +1234567890) or wa.me link',
                    example: '+1234567890'
                },
                'instagram': {
                    pattern: /^(@?[\w.]+|https?:\/\/(www\.)?instagram\.com\/[\w.]+\/?.*$)/i,
                    message: 'Enter Instagram username or URL (e.g., @username or instagram.com/username)',
                    example: '@username'
                },
                'x': {
                    pattern: /^(@?[\w]+|https?:\/\/(www\.)?(x|twitter)\.com\/[\w]+\/?.*$)/i,
                    message: 'Enter X/Twitter username or URL (e.g., @username or x.com/username)',
                    example: '@username'
                },
                'twitter': {
                    pattern: /^(@?[\w]+|https?:\/\/(www\.)?(x|twitter)\.com\/[\w]+\/?.*$)/i,
                    message: 'Enter X/Twitter username or URL',
                    example: '@username'
                },
                'tiktok': {
                    pattern: /^(@?[\w.]+|https?:\/\/(www\.)?tiktok\.com\/@?[\w.]+\/?.*$)/i,
                    message: 'Enter TikTok username or URL (e.g., @username or tiktok.com/@username)',
                    example: '@username'
                },
                'telegram': {
                    pattern: /^(@?[\w]+|https?:\/\/(t\.me|telegram\.me)\/[\w]+\/?.*$)/i,
                    message: 'Enter Telegram username or URL (e.g., @username or t.me/username)',
                    example: 't.me/username'
                },
                'line': {
                    pattern: /^(@[\w\-]+|https?:\/\/line\.me\/.+)$/i,
                    message: 'Enter LINE ID (e.g., @lineid)',
                    example: '@lineid'
                },
                'messenger': {
                    pattern: /^([\w.]+|https?:\/\/(m\.me|messenger\.com)\/.+)$/i,
                    message: 'Enter Messenger username or m.me link',
                    example: 'm.me/username'
                },
                'discord': {
                    pattern: /^([\w]+|https?:\/\/(discord\.gg|discord\.com\/(invite)?)\/.+)$/i,
                    message: 'Enter Discord invite code or discord.gg link',
                    example: 'discord.gg/invitecode'
                },
                'threads': {
                    pattern: /^(@?[\w.]+|https?:\/\/(www\.)?threads\.net\/@?[\w.]+\/?.*$)/i,
                    message: 'Enter Threads username or URL (e.g., @username)',
                    example: '@username'
                },
                'youtube': {
                    pattern: /^https?:\/\/(www\.)?(youtube\.com|youtu\.be|m\.youtube\.com)\/.+$/i,
                    message: 'YouTube URL: youtube.com/@channel, youtu.be/VIDEO, or m.youtube.com/...',
                    example: 'youtube.com/@channel or youtu.be/videoID'
                },
                'spotify': {
                    pattern: /^https?:\/\/(open\.)?spotify\.com\/.+$/i,
                    message: 'Enter a valid Spotify URL (open.spotify.com/...)',
                    example: 'open.spotify.com/track/...'
                },
                'soundcloud': {
                    pattern: /^https?:\/\/(www\.)?soundcloud\.com\/.+$/i,
                    message: 'Enter a valid SoundCloud URL (soundcloud.com/...)',
                    example: 'soundcloud.com/username'
                },
                'github': {
                    pattern: /^https?:\/\/(www\.)?github\.com\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid GitHub URL (github.com/username)',
                    example: 'github.com/username'
                },
                'linkedin': {
                    pattern: /^https?:\/\/(www\.)?linkedin\.com\/(in|company)\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid LinkedIn URL (linkedin.com/in/profile)',
                    example: 'linkedin.com/in/profile'
                },
                'facebook': {
                    pattern: /^https?:\/\/(www\.)?(facebook|fb)\.(com|me)\/.+$/i,
                    message: 'Enter a valid Facebook URL (facebook.com/...)',
                    example: 'facebook.com/username'
                },
                'twitch': {
                    pattern: /^https?:\/\/(www\.)?twitch\.tv\/[\w_]+\/?.*$/i,
                    message: 'Enter a valid Twitch URL (twitch.tv/username)',
                    example: 'twitch.tv/username'
                },
                'pinterest': {
                    pattern: /^https?:\/\/(www\.)?pinterest\.com\/.+$/i,
                    message: 'Enter a valid Pinterest URL (pinterest.com/...)',
                    example: 'pinterest.com/username'
                },
                'reddit': {
                    pattern: /^https?:\/\/(www\.)?reddit\.com\/(u|r|user)\/.+$/i,
                    message: 'Enter a valid Reddit URL (reddit.com/u/... or reddit.com/r/...)',
                    example: 'reddit.com/u/username'
                },
                'patreon': {
                    pattern: /^https?:\/\/(www\.)?patreon\.com\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid Patreon URL (patreon.com/username)',
                    example: 'patreon.com/username'
                },
                'ko-fi': {
                    pattern: /^https?:\/\/(www\.)?ko-fi\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Ko-fi URL (ko-fi.com/username)',
                    example: 'ko-fi.com/username'
                },
                'buy-me-a-coffee': {
                    pattern: /^https?:\/\/(www\.)?buymeacoffee\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Buy Me a Coffee URL (buymeacoffee.com/username)',
                    example: 'buymeacoffee.com/username'
                },
                'paypal': {
                    pattern: /^https?:\/\/(www\.)?paypal\.(me|com)\/[\w]+\/?.*$/i,
                    message: 'Enter a valid PayPal URL (paypal.me/username)',
                    example: 'paypal.me/username'
                },
                'venmo': {
                    pattern: /^(@?[\w]+|https?:\/\/(www\.)?venmo\.com\/[\w]+.*)$/i,
                    message: 'Enter Venmo username (e.g., @username)',
                    example: '@username'
                },
                'cash-app': {
                    pattern: /^(\$[\w]+|https?:\/\/(www\.)?cash\.app\/\$[\w]+.*)$/i,
                    message: 'Enter Cash App cashtag (e.g., $cashtag)',
                    example: '$cashtag'
                },
                'snapchat': {
                    pattern: /^(@?[\w]+|https?:\/\/(www\.)?snapchat\.com\/add\/[\w]+.*)$/i,
                    message: 'Enter Snapchat username (e.g., @username)',
                    example: '@username'
                },
                'bluesky': {
                    pattern: /^(@?[\w.-]+\.bsky\.social|@?[\w.-]+|https?:\/\/bsky\.app\/profile\/.+)$/i,
                    message: 'Enter Bluesky handle (e.g., @handle.bsky.social)',
                    example: '@handle.bsky.social'
                },
                'mastodon': {
                    pattern: /^(@?[\w]+@[\w.-]+|https?:\/\/.+\/@?[\w]+.*)$/i,
                    message: 'Enter Mastodon handle (e.g., @user@instance.social)',
                    example: '@user@instance.social'
                },
                'behance': {
                    pattern: /^https?:\/\/(www\.)?behance\.net\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Behance URL (behance.net/username)',
                    example: 'behance.net/username'
                },
                'dribbble': {
                    pattern: /^https?:\/\/(www\.)?dribbble\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Dribbble URL (dribbble.com/username)',
                    example: 'dribbble.com/username'
                },
                'medium': {
                    pattern: /^https?:\/\/(www\.)?medium\.com\/@?[\w]+\/?.*$/i,
                    message: 'Enter a valid Medium URL (medium.com/@username)',
                    example: 'medium.com/@username'
                },
                'figma': {
                    pattern: /^https?:\/\/(www\.)?figma\.com\/@?[\w]+\/?.*$/i,
                    message: 'Enter a valid Figma URL (figma.com/@username)',
                    example: 'figma.com/@username'
                },
                'notion': {
                    pattern: /^https?:\/\/(www\.)?(notion\.so|notion\.site)\/.+$/i,
                    message: 'Enter a valid Notion URL (notion.so/...)',
                    example: 'notion.so/...'
                },
                'calendly': {
                    pattern: /^https?:\/\/(www\.)?calendly\.com\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid Calendly URL (calendly.com/username)',
                    example: 'calendly.com/username'
                },
                'vimeo': {
                    pattern: /^https?:\/\/(www\.)?vimeo\.com\/.+$/i,
                    message: 'Enter a valid Vimeo URL (vimeo.com/...)',
                    example: 'vimeo.com/username'
                },
                'apple-music': {
                    pattern: /^https?:\/\/music\.apple\.com\/.+$/i,
                    message: 'Enter a valid Apple Music URL (music.apple.com/...)',
                    example: 'music.apple.com/...'
                },
                'apple-podcasts': {
                    pattern: /^https?:\/\/podcasts\.apple\.com\/.+$/i,
                    message: 'Enter a valid Apple Podcasts URL (podcasts.apple.com/...)',
                    example: 'podcasts.apple.com/...'
                },
                'youtube-music': {
                    pattern: /^https?:\/\/music\.youtube\.com\/.+$/i,
                    message: 'Enter a valid YouTube Music URL (music.youtube.com/...)',
                    example: 'music.youtube.com/...'
                },
                'amazon-music': {
                    pattern: /^https?:\/\/music\.amazon\.(com|co\.\w+)\/.+$/i,
                    message: 'Enter a valid Amazon Music URL (music.amazon.com/...)',
                    example: 'music.amazon.com/...'
                },
                'bandcamp': {
                    pattern: /^https?:\/\/[\w\-]+\.bandcamp\.com\/?.*$/i,
                    message: 'Enter a valid Bandcamp URL (username.bandcamp.com)',
                    example: 'username.bandcamp.com'
                },
                'tumblr': {
                    pattern: /^https?:\/\/[\w\-]+\.tumblr\.com\/?.*$/i,
                    message: 'Enter a valid Tumblr URL (username.tumblr.com)',
                    example: 'username.tumblr.com'
                },
                'substack': {
                    pattern: /^https?:\/\/[\w\-]+\.substack\.com\/?.*$/i,
                    message: 'Enter a valid Substack URL (username.substack.com)',
                    example: 'username.substack.com'
                },
                'steam': {
                    pattern: /^https?:\/\/(www\.)?(steamcommunity\.com\/(id|profiles)|store\.steampowered\.com\/(app|sub)|steampowered\.com)\/.+$/i,
                    message: 'Enter a valid Steam URL (steamcommunity.com/id/... or store.steampowered.com/app/...)',
                    example: 'steamcommunity.com/id/username or store.steampowered.com/app/...'
                },
                'etsy': {
                    pattern: /^https?:\/\/(www\.)?etsy\.com\/shop\/.+$/i,
                    message: 'Enter a valid Etsy shop URL (etsy.com/shop/...)',
                    example: 'etsy.com/shop/yourshop'
                },
                'amazon': {
                    pattern: /^https?:\/\/(www\.)?amazon\.(com|co\.\w+)\/.+$/i,
                    message: 'Enter a valid Amazon URL (amazon.com/...)',
                    example: 'amazon.com/...'
                },
                'gumroad': {
                    pattern: /^https?:\/\/(www\.)?gumroad\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Gumroad URL (gumroad.com/username)',
                    example: 'gumroad.com/username'
                },
                'letterboxd': {
                    pattern: /^https?:\/\/(www\.)?letterboxd\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Letterboxd URL (letterboxd.com/username)',
                    example: 'letterboxd.com/username'
                },
                'goodreads': {
                    pattern: /^https?:\/\/(www\.)?goodreads\.com\/(user|author)\/.+$/i,
                    message: 'Enter a valid Goodreads URL (goodreads.com/user/...)',
                    example: 'goodreads.com/user/...'
                },
                'unsplash': {
                    pattern: /^https?:\/\/(www\.)?unsplash\.com\/@[\w\-]+\/?.*$/i,
                    message: 'Enter a valid Unsplash URL (unsplash.com/@username)',
                    example: 'unsplash.com/@username'
                },
                'slack': {
                    pattern: /^https?:\/\/[\w\-]+\.slack\.com\/?.*$/i,
                    message: 'Enter a valid Slack URL (workspace.slack.com)',
                    example: 'workspace.slack.com'
                },
                'kick': {
                    pattern: /^https?:\/\/(www\.)?kick\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Kick URL (kick.com/username)',
                    example: 'kick.com/username'
                },
                'gitlab': {
                    pattern: /^https?:\/\/(www\.)?gitlab\.com\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid GitLab URL (gitlab.com/username)',
                    example: 'gitlab.com/username'
                },
                'dev-to': {
                    pattern: /^https?:\/\/(www\.)?dev\.to\/[\w]+\/?.*$/i,
                    message: 'Enter a valid DEV.to URL (dev.to/username)',
                    example: 'dev.to/username'
                },
                'stack-overflow': {
                    pattern: /^https?:\/\/(www\.)?stackoverflow\.com\/(users|questions)\/.+$/i,
                    message: 'Enter a valid Stack Overflow URL (stackoverflow.com/users/...)',
                    example: 'stackoverflow.com/users/...'
                },
                'hashnode': {
                    pattern: /^https?:\/\/(www\.)?hashnode\.com\/@[\w]+\/?.*$/i,
                    message: 'Enter a valid Hashnode URL (hashnode.com/@username)',
                    example: 'hashnode.com/@username'
                },
                'artstation': {
                    pattern: /^https?:\/\/(www\.)?artstation\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid ArtStation URL (artstation.com/username)',
                    example: 'artstation.com/username'
                },
                'vsco': {
                    pattern: /^https?:\/\/(www\.)?vsco\.co\/[\w]+\/?.*$/i,
                    message: 'Enter a valid VSCO URL (vsco.co/username)',
                    example: 'vsco.co/username'
                },
                'flickr': {
                    pattern: /^https?:\/\/(www\.)?flickr\.com\/photos\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid Flickr URL (flickr.com/photos/username)',
                    example: 'flickr.com/photos/username'
                },
                'fiverr': {
                    pattern: /^https?:\/\/(www\.)?fiverr\.com\/[\w]+\/?.*$/i,
                    message: 'Enter a valid Fiverr URL (fiverr.com/username)',
                    example: 'fiverr.com/username'
                },
                'trello': {
                    pattern: /^https?:\/\/(www\.)?trello\.com\/b\/.+$/i,
                    message: 'Enter a valid Trello URL (trello.com/b/...)',
                    example: 'trello.com/b/...'
                },
                'gofundme': {
                    pattern: /^https?:\/\/(www\.)?gofundme\.com\/f\/.+$/i,
                    message: 'Enter a valid GoFundMe URL (gofundme.com/f/...)',
                    example: 'gofundme.com/f/...'
                },
                'kickstarter': {
                    pattern: /^https?:\/\/(www\.)?kickstarter\.com\/projects\/.+$/i,
                    message: 'Enter a valid Kickstarter URL (kickstarter.com/projects/...)',
                    example: 'kickstarter.com/projects/...'
                },
                'tidal': {
                    pattern: /^https?:\/\/(www\.)?(tidal\.com|listen\.tidal\.com)\/.+$/i,
                    message: 'Enter a valid TIDAL URL (tidal.com/browse/... or listen.tidal.com/...)',
                    example: 'tidal.com/browse/artist/...'
                },
                'deezer': {
                    pattern: /^https?:\/\/(www\.)?deezer\.com\/.+$/i,
                    message: 'Enter a valid Deezer URL (deezer.com/...)',
                    example: 'deezer.com/artist/...'
                },
                'xing': {
                    pattern: /^https?:\/\/(www\.)?xing\.com\/profile\/[\w\-]+\/?.*$/i,
                    message: 'Enter a valid XING URL (xing.com/profile/username)',
                    example: 'xing.com/profile/username'
                },
                'email': {
                    pattern: /^(mailto:)?[\w.+-]+@[\w.-]+\.\w{2,}$/i,
                    message: 'Enter a valid email address',
                    example: 'your@email.com'
                },
                'phone': {
                    pattern: /^\+\d{10,15}$/,
                    message: 'Enter phone with country code (e.g., +1234567890)',
                    example: '+1234567890'
                },
                'sms': {
                    pattern: /^\+\d{10,15}$/,
                    message: 'Enter phone with country code (e.g., +1234567890)',
                    example: '+1234567890'
                },
            };
            const rule = validationRules[brandId];
            if (!rule) {
                block._urlError = null;
                return true;
            }
            if (!rule.pattern.test(url)) {
                block._urlError = rule.message;
                return false;
            }
            block._urlError = null;
            return true;
        },
        copyPageUrl() {
            navigator.clipboard.writeText(CONFIG.publicBase + '/' + this.bioPage.slug)
                .then(() => this.showNotification('URL copied!', 'success'));
        },
        /**
         * Get Simple Icons CDN URL for a platform
         * @param {string} platform Platform identifier
         * @param {string|null} color Optional hex color without # (e.g., 'ffffff' for white)
         * @returns {string|null} CDN URL or null if not available
         */
        getSimpleIconUrl(platform, color = null) {
            const mapping = {
                'amazon': 'amazon',
                'amazon-music': 'amazonmusic',
                'apple': 'apple',
                'apple-music': 'applemusic',
                'apple-music-alt': 'applemusic',
                'apple-podcasts': 'applepodcasts',
                'apple-podcasts-alt': 'applepodcasts',
                'artstation': 'artstation',
                'audiomack': 'audiomack',
                'bandcamp': 'bandcamp',
                'behance': 'behance',
                'bereal': 'bereal',
                'bluesky': 'bluesky',
                'bluesky-alt': 'bluesky',
                'buy-me-a-coffee': 'buymeacoffee',
                'cal': 'caldotcom',
                'calendly': 'calendly',
                'cameo': 'cameo',
                'cash-app': 'cashapp',
                'cash-app-btc': 'cashapp',
                'cash-app-dollar': 'cashapp',
                'cash-app-pound': 'cashapp',
                'clubhouse': 'clubhouse',
                'codeberg': 'codeberg',
                'codepen': 'codepen',
                'deezer': 'deezer',
                'dev-to': 'devdotto',
                'discord': 'discord',
                'discogs': 'discogs',
                'discogs-alt': 'discogs',
                'dribbble': 'dribbble',
                'email': 'gmail',
                'email-alt': 'maildotru',
                'etsy': 'etsy',
                'facebook': 'facebook',
                'figma': 'figma',
                'fiverr': 'fiverr',
                'flickr': 'flickr',
                'github': 'github',
                'gitlab': 'gitlab',
                'gofundme': 'gofundme',
                'gog': 'gogdotcom',
                'goodreads': 'goodreads',
                'google-drive': 'googledrive',
                'google-play': 'googleplay',
                'google-podcasts': 'googlepodcasts',
                'google-scholar': 'googlescholar',
                'guilded': 'guilded',
                'gumroad': 'gumroad',
                'hackerrank': 'hackerrank',
                'hashnode': 'hashnode',
                'instagram': 'instagram',
                'itch-io': 'itchdotio',
                'keybase': 'keybase',
                'kick': 'kick',
                'kick-alt': 'kick',
                'kickstarter': 'kickstarter',
                'kit': 'convertkit',
                'ko-fi': 'kofi',
                'last-fm': 'lastdotfm',
                'leetcode': 'leetcode',
                'lemmy': 'lemmy',
                'letterboxd': 'letterboxd',
                'line': 'line',
                'linkedin': 'linkedin',
                'mailchimp': 'mailchimp',
                'mastodon': 'mastodon',
                'matrix': 'matrix',
                'medium': 'medium',
                'meetup': 'meetup',
                'meetup-alt': 'meetup',
                'messenger': 'messenger',
                'microsoft': 'microsoft',
                'mixcloud': 'mixcloud',
                'myanimelist': 'myanimelist',
                'nostr': 'nostr',
                'notion': 'notion',
                'obsidian': 'obsidian',
                'onlyfans': 'onlyfans',
                'opensea': 'opensea',
                'orcid': 'orcid',
                'osu': 'osu',
                'patreon': 'patreon',
                'paypal': 'paypal',
                'pinterest': 'pinterest',
                'pixelfed': 'pixelfed',
                'playstation': 'playstation',
                'product-hunt': 'producthunt',
                'qobuz': 'qobuz',
                'redbubble': 'redbubble',
                'reddit': 'reddit',
                'researchgate': 'researchgate',
                'revolut': 'revolut',
                'signal': 'signal',
                'signal-alt': 'signal',
                'simplex': 'simplex',
                'slack': 'slack',
                'snapchat': 'snapchat',
                'soundcloud': 'soundcloud',
                'spotify': 'spotify',
                'spotify-alt': 'spotify',
                'stack-overflow': 'stackoverflow',
                'steam': 'steam',
                'strava': 'strava',
                'substack': 'substack',
                'telegram': 'telegram',
                'threads': 'threads',
                'threema': 'threema',
                'tidal': 'tidal',
                'tiktok': 'tiktok',
                'trakt': 'trakt',
                'trello': 'trello',
                'tumblr': 'tumblr',
                'twitch': 'twitch',
                'twitter': 'twitter',
                'unsplash': 'unsplash',
                'upwork': 'upwork',
                'venmo': 'venmo',
                'vimeo': 'vimeo',
                'vrchat': 'vrchat',
                'vsco': 'vsco',
                'whatsapp': 'whatsapp',
                'wordpress': 'wordpress',
                'x': 'x',
                'xbox': 'xbox',
                'xing': 'xing',
                'youtube': 'youtube',
                'youtube-alt': 'youtube',
                'youtube-music': 'youtubemusic',
                'zoom': 'zoom',
            };
            const iconName = mapping[platform];
            if (!iconName) return null;
            let url = `https://cdn.simpleicons.org/${iconName}`;
            if (color) {
                url += `/${color.replace('#', '')}`;
            }
            return url;
        },
        /**
         * Get icon URL - tries Simple Icons first, falls back to local
         * @param {string} platform Platform identifier
         * @param {string|null} color Optional color for Simple Icons
         * @returns {string} Icon URL
         */
        getIconUrl(platform, color = null) {
            // Try Simple Icons first
            const simpleUrl = this.getSimpleIconUrl(platform, color);
            if (simpleUrl) return simpleUrl;
            // Fall back to local icons
            return `/images/brands/${platform}.svg?v=20251212`;
        },
        /**
         * Auto-color module: Calculate optimal text color based on background
         * Uses WCAG luminance calculation for accessibility
         */
        getContrastColor(bgColor) {
            if (!bgColor) return '#ffffff';
            // Handle hex colors
            let hex = bgColor;
            if (hex.startsWith('#')) {
                hex = hex.slice(1);
            }
            // Handle shorthand hex
            if (hex.length === 3) {
                hex = hex.split('').map(c => c + c).join('');
            }
            // Handle rgba/rgb
            if (bgColor.startsWith('rgb')) {
                const rgb = bgColor.match(/\d+/g);
                if (rgb && rgb.length >= 3) {
                    const r = parseInt(rgb[0]);
                    const g = parseInt(rgb[1]);
                    const b = parseInt(rgb[2]);
                    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                    return luminance > 0.5 ? '#000000' : '#ffffff';
                }
            }
            // Handle gradient - extract first color
            if (bgColor.includes('gradient')) {
                const colorMatch = bgColor.match(/#[a-fA-F0-9]{3,6}/);
                if (colorMatch) {
                    return this.getContrastColor(colorMatch[0]);
                }
                return '#ffffff';
            }
            // Parse hex
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            // Calculate relative luminance
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.5 ? '#000000' : '#ffffff';
        },
        /**
         * Get recommended colors for a background
         * Returns object with text, subtle, and accent colors
         */
        getRecommendedColors(bgColor) {
            const textColor = this.getContrastColor(bgColor);
            const isLight = textColor === '#000000';
            return {
                text: textColor,
                subtle: isLight ? '#4b5563' : '#9ca3af',
                accent: isLight ? '#3b82f6' : '#60a5fa',
                muted: isLight ? '#6b7280' : '#94a3b8',
                border: isLight ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.1)'
            };
        },
        /**
         * Check if a color is light
         */
        isLightColor(color) {
            return this.getContrastColor(color) === '#000000';
        },
        /**
         * Auto-apply recommended text color when button background changes
         * Call this when user changes button_bg_color
         */
        autoApplyTextColor(block) {
            if (block.btn_bg_color) {
                block.btn_text_color = this.getContrastColor(block.btn_bg_color);
                this.markBlockDirty(block.id);
            }
        },
        showNotification(message, type = 'info') {
            // One toast element, reused: a burst of messages does not stack up.
            let el = document.getElementById('bio-toast');
            if (!el) {
                el = document.createElement('div');
                el.id = 'bio-toast';
                el.className = 'bio-toast';
                document.body.appendChild(el);
            }
            el.dataset.type = type;
            el.setAttribute('role', type === 'error' ? 'alert' : 'status');
            el.textContent = message;
            clearTimeout(this._toastTimeout);
            this._toastTimeout = setTimeout(() => el.remove(), type === 'error' ? 7000 : 2600);
        },
        confirmDelete() {
            if (confirm('Delete this page permanently?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = bioEditorUrl('');
                form.innerHTML = '<input type="hidden" name="_token" value="'+csrf()+'"><input type="hidden" name="_method" value="DELETE">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    };
}
