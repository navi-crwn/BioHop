@extends('layouts.app')
@section('title', 'Edit Bio - ' . $bioPage->title)
@section('container-width', 'max-w-full')
@push('scripts-head')
    @vite('resources/js/editor.js')
@endpush
@section('content')
<p id="bio-storage-warning" role="status" class="mb-3 rounded-xl bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-300" hidden></p>
{{-- The page's data as JSON: parsed by resources/js/editor.js (cached), not printed into a 3,000-line inline script. --}}
@php
    $editorConfig = [
        'bioPage' => \Illuminate\Support\Arr::except($bioPage->toArray(), ['links']),
        'blocks' => $bioPage->links->whereNull('archived_at')->sortBy('order')->values(),
        'archived' => $bioPage->links->whereNotNull('archived_at')->sortByDesc('archived_at')->values(),
        'platforms' => config('brands.platforms', []),
        'themes' => \Hop\Bio\Support\BioThemes::picker(),
        'defaults' => config('bio-themes.defaults'),
        'previewUrl' => route('bio.preview', $bioPage),
        'publicBase' => public_url('b'),
    ];
@endphp
<script type="application/json" id="bio-editor-config">{!! json_encode($editorConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<div class="bio-editor" :class="{ 'preview-open': previewOpen }" x-data="window.bioEditorV2()" @keydown.escape.window="previewOpen = false">
    <div class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="announcement"></div>
    <!-- Fake fields to prevent browser password autofill -->
    <input type="text" name="fake_username" style="display:none !important" tabindex="-1" autocomplete="username">
    <input type="password" name="fake_password" style="display:none !important" tabindex="-1" autocomplete="new-password">
    @if (session('success'))
        <div class="mb-3"><x-flash /></div>
    @endif
    <div class="be-grid" autocomplete="off">
        <section class="be-main" aria-label="Page editor">
            <div class="be-head">
                <div class="be-title">
                    <a href="{{ route('bio.index') }}" @click.prevent="handleNavigation('{{ route('bio.index') }}')" class="be-iconbtn" aria-label="Back to bio pages">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </a>
                    <div class="min-w-0">
                        <h1 x-text="bioPage.title || 'Untitled'"></h1>
                        <a :href="'{{ public_url('b') }}/' + bioPage.slug" target="_blank" rel="noopener" class="be-url">
                            <span x-text="'{{ public_host() }}/b/' + bioPage.slug"></span>
                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
                <div class="be-actions">
                    <span class="be-state" :data-state="saveState" role="status" aria-live="polite">
                        <i aria-hidden="true"></i>
                        <span x-text="saveState === 'saving' ? 'Saving…' : saveState === 'error' ? 'Not saved' : saveState === 'dirty' ? (autosave ? 'Saving soon…' : 'Unsaved changes') : 'All changes saved'"></span>
                    </span>
                    <button type="button" class="be-iconbtn" @click="copyPageUrl()" title="Copy link" aria-label="Copy page link">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <button type="button" class="be-iconbtn" @click="toggleAutosave()" :aria-pressed="autosave" :title="autosave ? 'Autosave is on' : 'Autosave is off'" :aria-label="autosave ? 'Turn autosave off' : 'Turn autosave on'">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="autosave ? '' : 'opacity:.4'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.6 15a7 7 0 0012.7 1.5M18.4 9A7 7 0 005.7 7.5"/></svg>
                    </button>
                    <button type="button" @click="_autoBlocked = false; saveBioPage()" :disabled="saving || !hasUnsavedChanges" class="be-save">
                        <svg x-show="!saving" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"/></svg>
                        <span class="be-save-label" x-text="saving ? 'Saving…' : hasUnsavedChanges ? 'Save' : 'Saved'"></span>
                    </button>
                </div>
            </div>
            <div x-show="saveError" x-cloak class="be-error" role="alert">
                <div class="min-w-0"><strong>Couldn’t save.</strong> <span x-text="saveError"></span></div>
                <button type="button" @click="_autoBlocked = false; saveBioPage()" :disabled="saving">Try again</button>
            </div>
            <div class="editor-tabs" role="tablist" aria-label="Editor sections" @keydown.arrow-right.prevent="cycleTab(1)" @keydown.arrow-left.prevent="cycleTab(-1)" @keydown.home.prevent="cycleTab(0, true)" @keydown.end.prevent="cycleTab(4, true)">
            <button type="button" @click="activeTab = 'profile'" :class="{ 'active': activeTab === 'profile' }" class="editor-tab" role="tab" id="editor-tab-profile" aria-controls="editor-panel-profile" :aria-selected="activeTab === 'profile'" :tabindex="activeTab === 'profile' ? 0 : -1">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profile
            </button>
            <button type="button" @click="activeTab = 'links'" :class="{ 'active': activeTab === 'links' }" class="editor-tab" role="tab" id="editor-tab-links" aria-controls="editor-panel-links" :aria-selected="activeTab === 'links'" :tabindex="activeTab === 'links' ? 0 : -1">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Build
            </button>
            <button type="button" @click="activeTab = 'socials'" :class="{ 'active': activeTab === 'socials' }" class="editor-tab" role="tab" id="editor-tab-socials" aria-controls="editor-panel-socials" :aria-selected="activeTab === 'socials'" :tabindex="activeTab === 'socials' ? 0 : -1">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Socials
            </button>
            <button type="button" @click="activeTab = 'appearance'" :class="{ 'active': activeTab === 'appearance' }" class="editor-tab" role="tab" id="editor-tab-appearance" aria-controls="editor-panel-appearance" :aria-selected="activeTab === 'appearance'" :tabindex="activeTab === 'appearance' ? 0 : -1">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                Design
            </button>
            <button type="button" @click="activeTab = 'settings'" :class="{ 'active': activeTab === 'settings' }" class="editor-tab" role="tab" id="editor-tab-settings" aria-controls="editor-panel-settings" :aria-selected="activeTab === 'settings'" :tabindex="activeTab === 'settings' ? 0 : -1">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </button>
            </div>
            <div class="tab-content">
            <div x-show="activeTab === 'profile'" x-cloak role="tabpanel" id="editor-panel-profile" aria-labelledby="editor-tab-profile" tabindex="0">
                @include('bio::editor.profile-tab')
            </div>
            <div x-show="activeTab === 'links'" x-cloak role="tabpanel" id="editor-panel-links" aria-labelledby="editor-tab-links" tabindex="0">
                @include('bio::editor.links-tab')
            </div>
            <div x-show="activeTab === 'socials'" x-cloak role="tabpanel" id="editor-panel-socials" aria-labelledby="editor-tab-socials" tabindex="0">
                @include('bio::editor.socials-tab')
            </div>
            <div x-show="activeTab === 'appearance'" x-cloak role="tabpanel" id="editor-panel-appearance" aria-labelledby="editor-tab-appearance" tabindex="0">
                @include('bio::editor.appearance-tab')
            </div>
            <div x-show="activeTab === 'settings'" x-cloak role="tabpanel" id="editor-panel-settings" aria-labelledby="editor-tab-settings" tabindex="0">
                @include('bio::editor.settings-tab')
            </div>
            </div>
        </section>
        <aside class="be-preview" aria-label="Live preview">
            <div class="be-preview-bar">
                <span>Live preview</span>
                <span class="flex items-center gap-3">
                    <a :href="'{{ public_url('b') }}/' + bioPage.slug" target="_blank" rel="noopener">Open in new tab
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <button type="button" class="be-iconbtn be-preview-close" @click="previewOpen = false" aria-label="Close preview">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
            </div>
            <div class="be-preview-stage">
                <div class="mobile-frame">
                    <div class="mobile-frame-inner">
                        <iframe title="Live preview of your bio page" x-ref="previewFrame" :src="previewUrl" @load="onPreviewLoad()"></iframe>
                    </div>
                </div>
            </div>
        </aside>
    </div>
    <button type="button" class="be-fab" @click="previewOpen = true">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5c-4.5 0-8.3 2.9-9.5 7 1.2 4.1 5 7 9.5 7s8.3-2.9 9.5-7C20.3 7.9 16.5 5 12 5zm0 10a3 3 0 100-6 3 3 0 000 6z"/></svg>
        Preview
    </button>
    <!-- Modals: each is created when it opens and removed when it closes, so the page stays small -->
    <!-- Link Icon Picker Modal -->
    <template x-if="showIconPicker">
    <div class="modal-overlay" @click.self="showIconPicker = false" style="z-index: 10001;">
        <div class="modal-content" x-modal="showIconPicker" style="max-width: 560px;" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Choose Icon</h3>
                <button type="button" @click.prevent.stop="showIconPicker = false" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body p-0">
                <!-- Custom Icon Upload -->
                <div class="p-4" style="border-bottom: 1px solid var(--editor-border); background: var(--editor-card-bg);">
                    <label class="block mb-2">
                        <span class="text-sm font-medium editor-text-muted">Upload Custom Icon</span>
                    </label>
                    <div class="flex gap-2">
                        <input aria-label="Upload custom icon" type="file" accept="image/*" @change="uploadCustomIcon($event)" 
                               class="flex-1 text-sm editor-text-muted file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:font-medium hover:file:bg-blue-700">
                    </div>
                    <p class="text-xs editor-text-muted mt-1">PNG, JPG, or SVG. Max 1MB.</p>
                </div>
                <!-- Search -->
                <div class="p-4" style="border-bottom: 1px solid var(--editor-border);">
                    <input aria-label="Search icons" type="text" x-model="iconSearch" placeholder="Search icons..." class="form-input">
                </div>
                <!-- Icon Grid - 4 columns -->
                <div class="p-4 max-h-80 overflow-y-auto">
                    <div class="grid grid-cols-4 gap-3">
                        <template x-for="(brand, id) in filteredBrands" :key="id">
                            <button @click="setBlockIcon(id)" 
                                    :class="{ 'ring-2 ring-blue-500 var(--editor-hover-bg)': editingBlock && editingBlock.brand === id }"
                                    class="p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 border border-(--editor-border) hover:border-(--editor-border) transition-colors flex flex-col items-center gap-2">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: brand.color || '#6b7280' }">
                                    <img :src="'/images/brands/' + (brand.icon || id + '.svg') + '?v=20251212'" class="w-6 h-6" style="filter: brightness(0) invert(1);" data-fallback="hide">
                                </div>
                                <span class="text-xs editor-text-muted truncate w-full text-center" x-text="brand.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>
    <template x-if="editingBlock">
    <div class="modal-overlay" @click.self="closeBlockEditor()">
        <div class="modal-content" x-modal="editingBlock" data-modal-close="closeBlockEditor()" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Edit Block</h3>
                <button @click="closeBlockEditor()" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <template x-if="editingBlock">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 p-3 rounded-lg" style="background: var(--editor-card-bg)">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" 
                                 :style="{ backgroundColor: editingBlock?.brand ? getPlatformColor(editingBlock?.brand) : '#6366f1' }">
                                <template x-if="editingBlock?.brand">
                                    <img :src="getPlatformIcon(editingBlock?.brand)" class="w-5 h-5" style="filter: brightness(0) invert(1);">
                                </template>
                                <template x-if="!editingBlock?.brand">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </template>
                            </div>
                            <div>
                                <div class="text-sm font-medium editor-text" x-text="editingBlock?.brand ? brands[editingBlock?.brand]?.name : 'Custom Link'"></div>
                                <div class="text-xs editor-text-muted" x-text="editingBlock?.type"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="bfindex-1" class="form-label">Title</label>
                            <input maxlength="100" id="bfindex-1" type="text" x-model="editingBlock.title" class="form-input" placeholder="Button title" autocomplete="off" data-lpignore="true" data-form-type="other">
                        </div>
                        <template x-if="editingBlock && editingBlock?.type === 'link'">
                            <div class="space-y-4">
                                <!-- Link Mode Selection (Only for Custom Links) -->
                                <template x-if="!editingBlock?.brand">
                                    <div class="form-group mb-4">
                                        <div class="flex gap-6">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="link_mode" value="create" x-model="linkInputMode" class="text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Create new</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="link_mode" value="existing" x-model="linkInputMode" class="text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Select existing link</span>
                                            </label>
                                        </div>
                                    </div>
                                </template>
                                <div class="form-group">
                                    <!-- Create New Mode -->
                                    <template x-if="editingBlock?.brand || linkInputMode === 'create'">
                                        <div>
                                            <label for="bfindex-2" class="form-label" x-text="editingBlock?.brand ? 'URL' : 'Destination URL'"></label>
                                            <input id="bfindex-2" type="text" :value="editingBlock?.url ?? ''"
                                                @input="if (editingBlock) { editingBlock.url = $event.target.value; validatePlatformUrl(editingBlock) }"
                                                @blur="processUrl(editingBlock, true); validatePlatformUrl(editingBlock)"
                                                @input.debounce.1000ms="fetchUrlTitle(editingBlock)"
                                                class="form-input" 
                                                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': editingBlock?._urlError }"
                                                :placeholder="editingBlock?.brand ? brands[editingBlock?.brand]?.placeholder : 'ex. https://longurl.com'"
                                                autocomplete="off" data-lpignore="true" data-form-type="other">
                                            <p class="text-xs mt-1" :class="editingBlock?._urlError ? 'text-red-400' : 'editor-text-muted'">
                                                <template x-if="editingBlock?._urlError">
                                                    <span x-text="editingBlock?._urlError"></span>
                                                </template>
                                                <template x-if="!editingBlock?._urlError && !editingBlock?.brand">
                                                    <span>Enter a long URL to automatically shorten it</span>
                                                </template>
                                            </p>
                                        </div>
                                    </template>
                                    <!-- Select Existing Mode -->
                                    <template x-if="!editingBlock?.brand && linkInputMode === 'existing'">
                                        <div class="relative" @click.outside="libraryLinks = []; librarySearch = ''">
                                            <label class="form-label">Select a link</label>
                                            <!-- Selected Link Display -->
                                            <template x-if="editingBlock?.url && !librarySearch">
                                                <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg flex items-center justify-between group">
                                                    <div class="overflow-hidden">
                                                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-0.5">Selected Link</div>
                                                        <div class="text-sm font-medium truncate" x-text="editingBlock?.title || 'Untitled'"></div>
                                                        <div class="text-xs text-slate-500 truncate" x-text="editingBlock?.url"></div>
                                                    </div>
                                                    <button aria-label="Clear selected link" @click="editingBlock.url = ''; editingBlock.title = '';" class="p-1 hover:bg-blue-100 dark:hover:bg-blue-800 rounded-sm text-blue-600 dark:text-blue-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <div class="relative" x-show="!editingBlock?.url || librarySearch">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                </div>
                                                <input aria-label="Search your links" type="text" 
                                                    x-model="librarySearch" 
                                                    @input.debounce.300ms="librarySearch ? fetchLibraryLinks() : (libraryLinks = [])"
                                                    @focus="librarySearch ? fetchLibraryLinks() : null"
                                                    class="form-input pl-10" 
                                                    placeholder="Type to search existing URL...">
                                            </div>
                                            <!-- Inline Results Dropdown -->
                                            <div x-show="librarySearch.length > 0 || (libraryLinks.length > 0 && !editingBlock?.url)" 
                                                 class="absolute z-60 w-full mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 max-h-60 overflow-y-auto custom-scrollbar">
                                                <template x-if="libraryLoading">
                                                    <div class="p-4 text-center text-sm text-slate-500">Loading...</div>
                                                </template>
                                                <template x-if="!libraryLoading && libraryLinks.length === 0">
                                                    <div class="p-4 text-center text-sm text-slate-500">No links found</div>
                                                </template>
                                                <template x-for="link in libraryLinks" :key="link.id">
                                                    <button @click="selectLibraryLink(link)" 
                                                            class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group">
                                                        <div class="flex justify-between items-start mb-1">
                                                            <span class="font-medium text-sm text-slate-900 dark:text-white group-hover:text-blue-600" x-text="link.title || 'Untitled'"></span>
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded-sm bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-mono">Short</span>
                                                        </div>
                                                        <div class="text-xs text-slate-500 truncate" x-text="link.url"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <!-- Icon Picker - Only for Custom Links (no brand) -->
                                <template x-if="!editingBlock?.brand">
                                    <div class="form-group">
                                        <label class="form-label">Icon</label>
                                        <button type="button" 
                                                @click.prevent.stop="openIconPickerSafe(editingBlock)" 
                                                class="w-full flex items-center gap-3 p-3 option-btn rounded-lg transition-colors text-left relative z-10"
                                                style="cursor: pointer; pointer-events: auto;">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 transition-colors" 
                                                 :style="{ backgroundColor: '#6366f1' }">
                                                <template x-if="editingBlock?.custom_icon">
                                                    <img :src="editingBlock?.custom_icon" class="w-5 h-5 object-contain">
                                                </template>
                                                <template x-if="!editingBlock?.custom_icon">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                                    </svg>
                                                </template>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium editor-text truncate" x-text="editingBlock?.custom_icon ? 'Custom icon' : 'No icon selected'"></div>
                                                <div class="text-xs editor-text-muted">Click to change icon</div>
                                            </div>
                                            <svg class="w-5 h-5 editor-text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <div class="form-group" x-data="{ showButtonStyle: true }">
                                    <button type="button" @click="showButtonStyle = !showButtonStyle" 
                                            class="w-full flex items-center justify-between p-3 option-btn rounded-lg transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center border-2"
                                                 :style="{
                                                     backgroundColor: isBlockAutoStyle(editingBlock) ? getEffectiveButtonBg() : (editingBlock?.btn_bg_color || getEffectiveButtonBg()),
                                                     borderColor: editingBlock?.btn_border_color || 'transparent'
                                                 }">
                                                <svg class="w-5 h-5" :style="{ color: isBlockAutoStyle(editingBlock) ? getEffectiveButtonText() : (editingBlock?.btn_text_color || getEffectiveButtonText()) }" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                            </div>
                                            <div class="text-left">
                                                <div class="text-sm font-medium editor-text">Button Style</div>
                                                <div class="text-xs editor-text-muted" x-text="isBlockAutoStyle(editingBlock) ? 'Auto (following theme)' : 'Custom style'"></div>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 editor-text-muted transition-transform" :class="{ 'rotate-180': showButtonStyle }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="showButtonStyle" x-collapse class="mt-3 space-y-3 p-3 editor-card-bg rounded-lg" style="border: 1px solid var(--editor-border);">
                                        <div class="form-group">
                                            <label class="form-label text-xs">Preview</label>
                                            <div class="flex justify-center p-4 rounded-lg" style="background: var(--editor-bg);">
                                                <div class="px-6 py-3 rounded-lg font-semibold text-center min-w-[200px] flex items-center justify-center gap-2 transition-all"
                                                     :style="{
                                                         backgroundColor: isBlockAutoStyle(editingBlock) ? getEffectiveButtonBg() : (editingBlock?.btn_bg_color || getEffectiveButtonBg()),
                                                         color: isBlockAutoStyle(editingBlock) ? getEffectiveButtonText() : (editingBlock?.btn_text_color || getEffectiveButtonText()),
                                                         border: isBlockAutoStyle(editingBlock) ? 'none' : (editingBlock?.btn_border_color ? '1px solid ' + editingBlock?.btn_border_color : 'none')
                                                     }">
                                                    <template x-if="editingBlock?.brand || editingBlock?.custom_icon">
                                                        <img :src="editingBlock?.custom_icon || getPlatformIcon(editingBlock?.brand)" 
                                                             class="w-5 h-5" 
                                                             :style="{ filter: isBlockAutoStyle(editingBlock) ? getAutoIconFilter() : (editingBlock?.btn_icon_invert == true ? 'brightness(0) invert(1)' : 'none') }">
                                                    </template>
                                                    <span x-text="editingBlock?.title || 'Button Preview'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Auto Style Checkbox -->
                                        <div class="flex items-center justify-between py-2 px-3 rounded-lg" style="background: var(--editor-bg);">
                                            <div class="flex items-center gap-3">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" 
                                                           :checked="isBlockAutoStyle(editingBlock)"
                                                           @change="toggleBlockAutoStyle(editingBlock, $event.target.checked)"
                                                           class="w-4 h-4 rounded-sm border-gray-600 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
                                                    <span class="text-sm font-medium editor-text">Auto (Follow Theme)</span>
                                                </label>
                                            </div>
                                            <span class="text-xs editor-text-muted">Uncheck to manually edit</span>
                                        </div>
                                        <!-- Manual Style Options - only shown when Auto is OFF -->
                                        <template x-if="!isBlockAutoStyle(editingBlock)">
                                            <div class="space-y-3">
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label for="bfindex-3" class="form-label text-xs">Background</label>
                                                        <div class="flex items-center gap-2">
                                                            <input id="bfindex-3" type="color" x-model="editingBlock.btn_bg_color" 
                                                                   :value="editingBlock.btn_bg_color || getEffectiveButtonBg()"
                                                                   class="w-10 h-10 rounded-sm cursor-pointer border-0 bg-transparent"
                                                                   @input="markBlockDirty(editingBlock.id)">
                                                            <input maxlength="30" aria-label="Button background hex value" type="text" x-model="editingBlock.btn_bg_color" 
                                                                   class="form-input text-xs font-mono flex-1" 
                                                                   :placeholder="getEffectiveButtonBg()"
                                                                   @change="markBlockDirty(editingBlock.id)">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <label class="form-label text-xs mb-0">Text Color</label>
                                                            <button type="button" 
                                                                    @click="editingBlock.btn_text_color = getContrastColor(editingBlock.btn_bg_color || getEffectiveButtonBg()); markBlockDirty(editingBlock.id)"
                                                                    class="text-[10px] px-1.5 py-0.5 rounded-sm bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 transition-colors"
                                                                    title="Auto-detect best text color">
                                                                Auto
                                                            </button>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <input aria-label="Button text color" type="color" x-model="editingBlock.btn_text_color" 
                                                                   :value="editingBlock.btn_text_color || getEffectiveButtonText()"
                                                                   class="w-10 h-10 rounded-sm cursor-pointer border-0 bg-transparent">
                                                            <input maxlength="30" aria-label="Button text color hex value" type="text" x-model="editingBlock.btn_text_color" 
                                                                   class="form-input text-xs font-mono flex-1" 
                                                                   :placeholder="getEffectiveButtonText()">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label for="bfindex-4" class="form-label text-xs">Border Color</label>
                                                        <div class="flex items-center gap-2">
                                                            <input id="bfindex-4" type="color" x-model="editingBlock.btn_border_color" 
                                                                   :value="editingBlock.btn_border_color || '#ffffff'"
                                                                   class="w-10 h-10 rounded-sm cursor-pointer border-0 bg-transparent">
                                                            <input maxlength="30" aria-label="Button border color hex value" type="text" x-model="editingBlock.btn_border_color" 
                                                                   class="form-input text-xs font-mono flex-1" placeholder="None">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="form-label text-xs">Icon Style</label>
                                                        <label class="toggle-switch mt-2">
                                                            <input type="checkbox" x-model="editingBlock.btn_icon_invert" aria-label="White icon">
                                                            <span class="toggle-slider"></span>
                                                        </label>
                                                        <span class="text-xs editor-text-muted ml-2">White icon</span>
                                                    </div>
                                                </div>
                                                <div class="flex justify-end pt-2 border-t border-(--editor-border)">
                                                    <button type="button" @click="resetBlockToTheme(editingBlock)" 
                                                            class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                        </svg>
                                                        Reset to Theme Default
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'text'">
                            <div class="form-group">
                                <label class="form-label flex items-center justify-between">
                                    <span>Content</span>
                                    <button type="button" @click="openTextEditor(editingBlock)" 
                                            class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Rich Editor
                                    </button>
                                </label>
                                <textarea aria-label="Text content" :value="editingBlock?.content ?? ''" @input="editingBlock && (editingBlock.content = $event.target.value)" class="form-input form-textarea" rows="3" placeholder="Enter content..."></textarea>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'image'">
                            <div class="space-y-4">
                                <div class="form-group" x-show="editingBlock?.content">
                                    <label class="form-label">Current Image</label>
                                    <div class="relative w-full h-40 rounded-lg overflow-hidden" style="background: var(--editor-input-bg);">
                                        <img :src="editingBlock?.content" class="w-full h-full object-contain">
                                        <button aria-label="Remove image" type="button" @click="editingBlock.content = ''; editingBlock.thumbnail_url = ''" 
                                                class="absolute top-2 right-2 p-1.5 bg-red-600 hover:bg-red-700 rounded-full text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-5" class="form-label">Image Source</label>
                                    <div class="space-y-3">
                                        <div class="flex gap-2">
                                            <input id="bfindex-5" type="url" :value="editingBlock?.content ?? ''" @input="editingBlock && (editingBlock.content = editingBlock.thumbnail_url = $event.target.value)" class="form-input flex-1" placeholder="https://example.com/image.jpg">
                                        </div>
                                        <div class="text-center editor-text-muted text-sm">or</div>
                                        <label class="block p-4 border-2 border-dashed border-(--editor-border) hover:border-indigo-500 rounded-lg cursor-pointer transition-colors text-center">
                                            <input aria-label="Upload image" type="file" class="hidden" accept="image/*" @change="uploadBlockImage($event, editingBlock)">
                                            <svg class="w-8 h-8 mx-auto editor-text-muted mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-sm editor-text-muted">Click to upload image</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-6" class="form-label">Link URL (optional)</label>
                                    <input maxlength="500" id="bfindex-6" type="url" x-model="editingBlock.url" class="form-input" placeholder="https://example.com">
                                    <p class="text-xs editor-text-muted mt-1">Where should this image link to when clicked?</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'youtube'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-7" class="form-label">YouTube Video URL</label>
                                    <input maxlength="500" id="bfindex-7" type="url" x-model="editingBlock.embed_url" class="form-input" placeholder="https://youtube.com/watch?v=...">
                                    <p class="text-xs editor-text-muted mt-1">Paste the full YouTube video URL</p>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-8" class="form-label">Title (Optional)</label>
                                    <input maxlength="100" id="bfindex-8" type="text" x-model="editingBlock.title" class="form-input" placeholder="My Video">
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'spotify'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-9" class="form-label">Spotify URL</label>
                                    <input maxlength="500" id="bfindex-9" type="url" x-model="editingBlock.embed_url" class="form-input" placeholder="https://open.spotify.com/track/...">
                                    <p class="text-xs editor-text-muted mt-1">Supports tracks, albums, playlists, and artists</p>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-10" class="form-label">Title (Optional)</label>
                                    <input maxlength="100" id="bfindex-10" type="text" x-model="editingBlock.title" class="form-input" placeholder="My Music">
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'soundcloud'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-11" class="form-label">SoundCloud URL</label>
                                    <input maxlength="500" id="bfindex-11" type="url" x-model="editingBlock.embed_url" class="form-input" placeholder="https://soundcloud.com/...">
                                    <p class="text-xs editor-text-muted mt-1">Paste any SoundCloud track or profile URL</p>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-12" class="form-label">Title (Optional)</label>
                                    <input maxlength="100" id="bfindex-12" type="text" x-model="editingBlock.title" class="form-input" placeholder="My Track">
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'code'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-13" class="form-label">Block Title</label>
                                    <input maxlength="100" id="bfindex-13" type="text" x-model="editingBlock.title" class="form-input" placeholder="Code Example">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-14" class="form-label">Code</label>
                                    <textarea id="bfindex-14" :value="editingBlock?.html_content ?? ''" @input="editingBlock && (editingBlock.html_content = $event.target.value)" class="form-input form-textarea font-mono text-sm" rows="8" placeholder="// Your code here..." style="min-height: 150px;"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-15" class="form-label">Language</label>
                                    <select id="bfindex-15" :value="editingBlock?.content ?? ''" @change="editingBlock && (editingBlock.content = $event.target.value)" class="form-input">
                                        <option value="javascript">JavaScript</option>
                                        <option value="python">Python</option>
                                        <option value="php">PHP</option>
                                        <option value="html">HTML</option>
                                        <option value="css">CSS</option>
                                        <option value="bash">Bash</option>
                                        <option value="json">JSON</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'map'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-16" class="form-label">Location Title</label>
                                    <input maxlength="100" id="bfindex-16" type="text" x-model="editingBlock.title" class="form-input" placeholder="Our Office">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-17" class="form-label">Address</label>
                                    <textarea maxlength="500" id="bfindex-17" x-model="editingBlock.map_address" class="form-input form-textarea" rows="2" placeholder="Jl. Example No. 123, Jakarta, Indonesia"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-18" class="form-label">Zoom Level</label>
                                    <input id="bfindex-18" type="range" x-model="editingBlock.map_zoom" min="10" max="18" class="w-full">
                                    <p class="text-sm editor-text-muted text-center" x-text="'Zoom: ' + (editingBlock?.map_zoom || 15)"></p>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'countdown'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-19" class="form-label">Countdown Label</label>
                                    <input maxlength="100" id="bfindex-19" type="text" x-model="editingBlock.countdown_label" class="form-input" placeholder="Event starts in...">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-20" class="form-label">Target Date & Time</label>
                                    <input id="bfindex-20" type="datetime-local" :value="toLocalInput(editingBlock.countdown_date)" @input="editingBlock.countdown_date = fromLocalInput($event.target.value)" class="form-input">
                                    <p class="text-xs editor-text-muted mt-1">In your time zone. Visitors see the time left until the same moment.</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'form'">
                            <div class="space-y-4">
                                <p class="text-xs editor-text-muted">Visitors leave their details on your page. You see them here and can download them as a CSV file. Only what you switch on is asked for.</p>
                                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                                    <label class="flex items-center gap-2"><input type="checkbox" :checked="editingBlock.settings?.ask_name ?? true" @change="setProductSetting('ask_name', $event.target.checked)"> Name</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" :checked="editingBlock.settings?.ask_email ?? true" @change="setProductSetting('ask_email', $event.target.checked)"> E-mail</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" :checked="editingBlock.settings?.ask_phone ?? false" @change="setProductSetting('ask_phone', $event.target.checked)"> WhatsApp number</label>
                                </div>
                                <div class="form-group"><label for="bfindex-f1" class="form-label">Button text</label><input id="bfindex-f1" type="text" maxlength="40" :value="editingBlock.settings?.button ?? ''" @input="setProductSetting('button', $event.target.value)" class="form-input" placeholder="Send"></div>
                                <div class="form-group"><label for="bfindex-f2" class="form-label">Thank-you message</label><input id="bfindex-f2" type="text" maxlength="200" :value="editingBlock.settings?.thanks ?? ''" @input="setProductSetting('thanks', $event.target.value)" class="form-input" placeholder="Thank you! I will write back soon."></div>
                                <div class="form-group"><label for="bfindex-f3" class="form-label">Consent line <span class="editor-text-muted font-normal">(the visitor ticks it)</span></label><input id="bfindex-f3" type="text" maxlength="200" :value="editingBlock.settings?.consent ?? ''" @input="setProductSetting('consent', $event.target.value)" class="form-input" placeholder="I agree that you keep these details to get back to me."></div>
                                <div class="form-group"><label for="bfindex-f4" class="form-label">Keep entries for (days)</label><input id="bfindex-f4" type="number" min="1" max="365" :value="editingBlock.settings?.retention_days ?? 90" @input="setProductSetting('retention_days', $event.target.value)" class="form-input"><p class="text-xs editor-text-muted mt-1">After this many days entries are deleted automatically. You are responsible for what you do with the details; tell visitors in your footer line.</p></div>
                                <div class="flex items-center justify-between gap-2 p-2 rounded-lg" style="background: var(--editor-input-bg)" x-show="editingBlock.id < 1000000000">
                                    <span class="text-sm"><b x-text="editingBlock.leads_count ?? 0"></b> entries</span>
                                    <span class="flex gap-2">
                                        <a :href="bioEditorUrl('/links/' + editingBlock.id + '/leads.csv')" class="btn btn-secondary text-xs py-1 px-2" x-show="(editingBlock.leads_count ?? 0) > 0">Download CSV</a>
                                        <button type="button" class="btn btn-secondary text-xs py-1 px-2" x-show="(editingBlock.leads_count ?? 0) > 0" @click="deleteLeads()">Delete all</button>
                                    </span>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'rss'">
                            <div class="space-y-4">
                                <div class="form-group"><label for="bfindex-r1" class="form-label">Feed address</label><input id="bfindex-r1" type="url" x-model="editingBlock.url" class="form-input" placeholder="https://your-blog.example/feed.xml"><p class="text-xs editor-text-muted mt-1">An RSS or Atom address: a blog, a Substack, a Medium user, a podcast. BioHop reads it about once an hour and shows the newest posts; nothing from the feed is stored.</p></div>
                                <div class="form-group"><label for="bfindex-r2" class="form-label">How many posts</label><select id="bfindex-r2" :value="editingBlock.settings?.count ?? 5" @change="setProductSetting('count', parseInt($event.target.value, 10))" class="form-input"><template x-for="n in [1,2,3,4,5,6,8,10]" :key="n"><option :value="n" x-text="n"></option></template></select></div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'app'">
                            <div class="space-y-4">
                                <p class="text-xs editor-text-muted">One button, three addresses: iPhone and iPad visitors go to the App Store, Android visitors to Google Play, everyone else (computers) to the website.</p>
                                <div class="form-group"><label for="bfindex-a1" class="form-label">App Store address</label><input id="bfindex-a1" type="url" :value="editingBlock.settings?.ios ?? ''" @input="setProductSetting('ios', $event.target.value)" class="form-input" placeholder="https://apps.apple.com/app/…"></div>
                                <div class="form-group"><label for="bfindex-a2" class="form-label">Google Play address</label><input id="bfindex-a2" type="url" :value="editingBlock.settings?.android ?? ''" @input="setProductSetting('android', $event.target.value)" class="form-input" placeholder="https://play.google.com/store/apps/details?id=…"></div>
                                <div class="form-group"><label for="bfindex-a3" class="form-label">Website (computers and others)</label><input id="bfindex-a3" type="url" :value="editingBlock.settings?.web ?? ''" @input="setProductSetting('web', $event.target.value)" class="form-input" placeholder="https://your-app.example"></div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'goal'">
                            <div class="space-y-4">
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="form-group"><label for="bfindex-g1" class="form-label">So far</label><input id="bfindex-g1" type="number" min="0" step="any" :value="editingBlock.settings?.current ?? ''" @input="setProductSetting('current', $event.target.value)" class="form-input" placeholder="7500000"></div>
                                    <div class="form-group"><label for="bfindex-g2" class="form-label">Goal</label><input id="bfindex-g2" type="number" min="0" step="any" :value="editingBlock.settings?.target ?? ''" @input="setProductSetting('target', $event.target.value)" class="form-input" placeholder="10000000"></div>
                                    <div class="form-group"><label for="bfindex-g3" class="form-label">Unit</label><input id="bfindex-g3" type="text" maxlength="20" :value="editingBlock.settings?.unit ?? ''" @input="setProductSetting('unit', $event.target.value)" class="form-input" placeholder="IDR"></div>
                                </div>
                                <p class="text-xs editor-text-muted">You update "So far" yourself; nothing here takes payments.</p>
                                <div class="form-group"><label for="bfindex-g4" class="form-label">Link <span class="editor-text-muted font-normal">(optional)</span></label><input id="bfindex-g4" type="url" x-model="editingBlock.url" class="form-input" placeholder="https://… where people can help"></div>
                                <div class="form-group"><label for="bfindex-g5" class="form-label">Link text <span class="editor-text-muted font-normal">(optional)</span></label><input id="bfindex-g5" type="text" maxlength="100" x-model="editingBlock.description" class="form-input" placeholder="Support the film"></div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'audio' || editingBlock?.type === 'pdf'">
                            <div class="space-y-3">
                                <input aria-label="Upload file" type="file" class="hidden" x-ref="mediaInput" :accept="editingBlock.type === 'audio' ? 'audio/mpeg,audio/mp4,audio/ogg,audio/wav,.mp3,.m4a,.ogg,.wav' : 'application/pdf,.pdf'" @change="uploadBlockMedia($event)">
                                <button type="button" class="btn btn-secondary text-sm py-2 w-full" @click="$refs.mediaInput.click()" x-text="editingBlock.content ? 'Replace file' : (editingBlock.type === 'audio' ? 'Upload audio clip' : 'Upload PDF')"></button>
                                <p class="text-xs editor-text-muted" x-text="editingBlock.type === 'audio' ? 'MP3, M4A, OGG or WAV, up to 12 MB. It never plays by itself.' : 'A PDF up to 15 MB (menu, price list, catalogue). It opens in a new tab.'"></p>
                                <audio controls preload="none" class="w-full" :src="editingBlock.content" x-show="editingBlock.type === 'audio' && editingBlock.content"></audio>
                                <p class="text-xs" x-show="editingBlock.type === 'pdf' && editingBlock.content"><a :href="editingBlock.content" target="_blank" rel="noopener" class="underline">Open the uploaded PDF</a></p>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'carousel'">
                            <div class="space-y-3">
                                <input aria-label="Upload pictures" type="file" accept="image/*" multiple class="hidden" x-ref="carouselInput" @change="uploadCarouselImage($event)">
                                <button type="button" class="btn btn-secondary text-sm py-2 w-full" @click="$refs.carouselInput.click()" x-show="(editingBlock.settings?.images || []).length < 10">+ Add pictures (up to 10)</button>
                                <div class="space-y-2">
                                    <template x-for="(img, i) in (editingBlock.settings?.images || [])" :key="i">
                                        <div class="flex items-center gap-2">
                                            <img :src="pageImageUrl(img.url)" alt="" class="w-14 h-14 rounded-lg object-cover shrink-0">
                                            <input type="text" maxlength="100" :value="img.caption ?? ''" @input="setCarouselCaption(i, $event.target.value)" class="form-input text-sm" placeholder="Caption (also the picture's description)" :aria-label="'Caption ' + (i + 1)">
                                            <button type="button" class="icon-btn" @click="removeCarouselImage(i)" :aria-label="'Remove picture ' + (i + 1)"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'whatsapp'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-w2" class="form-label">Pre-filled message <span class="editor-text-muted font-normal">(optional)</span></label>
                                    <input maxlength="500" id="bfindex-w2" type="text" :value="editingBlock.settings?.text ?? ''" @input="setProductSetting('text', $event.target.value)" class="form-input" placeholder="Hi, I came from your bio page…">
                                </div>
                                <div class="form-group">
                                    <div class="flex items-center justify-between">
                                        <label class="form-label mb-0">Numbers <span class="editor-text-muted font-normal">(each click goes to one of them)</span></label>
                                        <button type="button" class="btn btn-secondary text-xs py-1 px-2" @click="addWaNumber()" x-show="(editingBlock.settings?.numbers || []).length < 10">+ Add number</button>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(n, i) in (editingBlock.settings?.numbers || [])" :key="i">
                                            <div class="be-wa-row">
                                                <input type="tel" maxlength="24" :value="n.number" @input="setWaNumber(i, 'number', $event.target.value)" class="form-input" placeholder="+62 812 3456 7890" :aria-label="'Number ' + (i + 1)">
                                                <input type="text" maxlength="40" :value="n.label ?? ''" @input="setWaNumber(i, 'label', $event.target.value)" class="form-input" placeholder="Label, e.g. CS Rina" :aria-label="'Label ' + (i + 1)">
                                                <input type="number" min="1" max="100" :value="n.weight ?? 1" @input="setWaNumber(i, 'weight', $event.target.value)" class="form-input" placeholder="Weight" :aria-label="'Weight ' + (i + 1)" x-show="editingBlock.settings?.mode === 'weighted'">
                                                <button type="button" class="icon-btn" @click="removeWaNumber(i)" :aria-label="'Remove number ' + (i + 1)" title="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="text-xs editor-text-muted mt-1">With country code, digits only are kept. One number works too.</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">How clicks are shared</label>
                                    <div class="flex gap-2">
                                        <button type="button" class="btn btn-secondary text-xs py-1.5 flex-1" :class="{ 'ring-2 ring-blue-500': (editingBlock.settings?.mode || 'round_robin') === 'round_robin' }" @click="setProductSetting('mode', 'round_robin')">In turn (round robin)</button>
                                        <button type="button" class="btn btn-secondary text-xs py-1.5 flex-1" :class="{ 'ring-2 ring-blue-500': editingBlock.settings?.mode === 'weighted' }" @click="setProductSetting('mode', 'weighted')">By weight</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'product'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-p1" class="form-label">Product name</label>
                                    <input maxlength="100" id="bfindex-p1" type="text" x-model="editingBlock.title" class="form-input" placeholder="Linen shirt">
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="form-group col-span-2">
                                        <label for="bfindex-p2" class="form-label">Price</label>
                                        <input id="bfindex-p2" type="number" min="0" step="0.01" inputmode="decimal" :value="editingBlock.settings?.price ?? ''" @input="editingBlock.settings = Object.assign({}, editingBlock.settings, { price: $event.target.value })" class="form-input" placeholder="120000">
                                    </div>
                                    <div class="form-group">
                                        <label for="bfindex-p3" class="form-label">Currency</label>
                                        <select id="bfindex-p3" :value="editingBlock.settings?.currency ?? 'IDR'" @change="editingBlock.settings = Object.assign({}, editingBlock.settings, { currency: $event.target.value })" class="form-input">
                                            <template x-for="c in ['IDR','USD','EUR','GBP','JPY','SGD','MYR','AUD','BRL','CNY','KRW','INR']" :key="c"><option :value="c" x-text="c"></option></template>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="form-group">
                                        <label for="bfindex-p6" class="form-label">Was price <span class="editor-text-muted font-normal">(optional)</span></label>
                                        <input id="bfindex-p6" type="number" min="0" step="0.01" inputmode="decimal" :value="editingBlock.settings?.compare_price ?? ''" @input="setProductSetting('compare_price', $event.target.value)" class="form-input" placeholder="150000">
                                        <p class="text-xs editor-text-muted mt-1">Shown struck through next to the price.</p>
                                    </div>
                                    <div class="form-group">
                                        <label for="bfindex-p7" class="form-label">Stock <span class="editor-text-muted font-normal">(optional)</span></label>
                                        <input id="bfindex-p7" type="number" min="0" step="1" inputmode="numeric" :value="editingBlock.settings?.stock ?? ''" @input="setProductSetting('stock', $event.target.value)" class="form-input" placeholder="Leave empty if unlimited">
                                        <p class="text-xs editor-text-muted mt-1">0 shows "Sold out"; 1–5 shows "Only n left".</p>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="flex items-center justify-between">
                                        <label class="form-label mb-0">Variants <span class="editor-text-muted font-normal">(sizes, colours…)</span></label>
                                        <button type="button" class="btn btn-secondary text-xs py-1 px-2" @click="addVariant()" x-show="(editingBlock.settings?.variants || []).length < 20">+ Add variant</button>
                                    </div>
                                    <p class="text-xs editor-text-muted mt-1" x-show="!(editingBlock.settings?.variants || []).length">Each variant can have its own price, stock and buy address. Without them the product's own are used.</p>
                                    <div class="mt-2 space-y-2">
                                        <template x-for="(v, i) in (editingBlock.settings?.variants || [])" :key="i">
                                            <div class="be-variant">
                                                <input type="text" maxlength="40" :value="v.name" @input="setVariant(i, 'name', $event.target.value)" class="form-input" placeholder="Size M" :aria-label="'Variant ' + (i + 1) + ' name'">
                                                <input type="number" min="0" step="0.01" inputmode="decimal" :value="v.price ?? ''" @input="setVariant(i, 'price', $event.target.value)" class="form-input" placeholder="Price" :aria-label="'Variant ' + (i + 1) + ' price'">
                                                <input type="number" min="0" step="1" inputmode="numeric" :value="v.stock ?? ''" @input="setVariant(i, 'stock', $event.target.value)" class="form-input" placeholder="Stock" :aria-label="'Variant ' + (i + 1) + ' stock'">
                                                <input type="url" :value="v.url ?? ''" @input="setVariant(i, 'url', $event.target.value)" class="form-input" placeholder="https://… (optional)" :aria-label="'Variant ' + (i + 1) + ' address'">
                                                <button type="button" class="icon-btn" @click="removeVariant(i)" :aria-label="'Remove variant ' + (i + 1)" title="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-p4" class="form-label">Where to buy (address)</label>
                                    <input id="bfindex-p4" type="url" x-model="editingBlock.url" class="form-input" placeholder="https://your-shop.com/linen-shirt">
                                    <p class="text-xs editor-text-muted mt-1">Your own shop, a marketplace listing or an affiliate link. Visitors open it in a new tab.</p>
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-p5" class="form-label">Short description</label>
                                    <input maxlength="500" id="bfindex-p5" type="text" x-model="editingBlock.description" class="form-input" placeholder="Organic linen, three colours">
                                </div>
                                <div class="form-group" x-show="editingBlock?.thumbnail_url">
                                    <label class="form-label">Picture</label>
                                    <div class="relative w-full h-40 rounded-lg overflow-hidden" style="background: var(--editor-input-bg);">
                                        <img :src="editingBlock?.thumbnail_url" class="w-full h-full object-cover">
                                        <button aria-label="Remove picture" type="button" @click="editingBlock.thumbnail_url = ''; editingBlock.content = ''" class="absolute top-2 right-2 p-1.5 bg-red-600 hover:bg-red-700 rounded-full text-white">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <label class="block p-4 border-2 border-dashed border-(--editor-border) hover:border-indigo-500 rounded-lg cursor-pointer transition-colors text-center">
                                    <input aria-label="Upload product picture" type="file" class="hidden" accept="image/*" @change="uploadBlockImage($event, editingBlock)">
                                    <span class="text-sm editor-text-muted">Upload a picture (square looks best)</span>
                                </label>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" :checked="!!editingBlock.settings?.sold_out" @change="editingBlock.settings = Object.assign({}, editingBlock.settings, { sold_out: $event.target.checked })"> Sold out
                                </label>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'collection'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-c1" class="form-label">Collection name</label>
                                    <input maxlength="100" id="bfindex-c1" type="text" x-model="editingBlock.title" class="form-input" placeholder="New arrivals">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-c2" class="form-label">Layout</label>
                                    <select id="bfindex-c2" :value="editingBlock.settings?.layout ?? 'stack'" @change="editingBlock.settings = Object.assign({}, editingBlock.settings, { layout: $event.target.value })" class="form-input">
                                        <option value="stack">Stacked (one per row)</option>
                                        <option value="grid">Grid (two per row)</option>
                                    </select>
                                </div>
                                <p class="text-xs editor-text-muted">Everything placed under this collection belongs to it, until the next collection or a divider. Products under it are grouped on the Shop tab.</p>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'header'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-21" class="form-label">Header Text</label>
                                    <input maxlength="100" id="bfindex-21" type="text" x-model="editingBlock.title" class="form-input" placeholder="Section Header">
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'vcard'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-22" class="form-label">Full Name</label>
                                    <input maxlength="100" id="bfindex-22" type="text" x-model="editingBlock.vcard_name" class="form-input" placeholder="John Doe">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-23" class="form-label">Phone Number</label>
                                    <input maxlength="30" id="bfindex-23" type="tel" x-model="editingBlock.vcard_phone" class="form-input" placeholder="+1 234 567 8900">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-24" class="form-label">Email</label>
                                    <input maxlength="100" id="bfindex-24" type="email" x-model="editingBlock.vcard_email" class="form-input" placeholder="john@example.com">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-25" class="form-label">Company (Optional)</label>
                                    <input maxlength="100" id="bfindex-25" type="text" x-model="editingBlock.vcard_company" class="form-input" placeholder="Company Name">
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'html'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label for="bfindex-26" class="form-label">Block Title (Optional)</label>
                                    <input maxlength="100" id="bfindex-26" type="text" x-model="editingBlock.title" class="form-input" placeholder="Custom HTML Block">
                                </div>
                                <div class="form-group">
                                    <label for="bfindex-27" class="form-label">HTML Code</label>
                                    <textarea id="bfindex-27" :value="editingBlock?.html_content ?? ''" @input="editingBlock && (editingBlock.html_content = $event.target.value)" class="form-input form-textarea font-mono text-sm" rows="8" placeholder="<div>Your HTML here...</div>" style="min-height: 150px;"></textarea>
                                    <p class="text-xs editor-text-muted mt-1">⚠️ Be careful with custom HTML - ensure it's safe and valid</p>
                                </div>
                            </div>
                        </template>
                        <!-- Animation Settings - Only for link type blocks -->
                        <template x-if="editingBlock && !['divider', 'spacer'].includes(editingBlock.type)">
                            <div class="p-3 rounded-lg space-y-3" style="background: var(--editor-card-bg)">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-sm font-medium editor-text">Schedule</span>
                                    <span class="text-xs editor-text-muted" x-text="scheduleState(editingBlock) === 'scheduled' ? '· not live yet' : (scheduleState(editingBlock) === 'expired' ? '· expired' : '')"></span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="bfindex-s1" class="form-label text-xs mb-1">Goes live</label>
                                        <input id="bfindex-s1" type="datetime-local" :value="toLocalInput(editingBlock.publish_at)" @input="editingBlock.publish_at = fromLocalInput($event.target.value)" class="form-input text-xs">
                                    </div>
                                    <div>
                                        <label for="bfindex-s2" class="form-label text-xs mb-1">Ends</label>
                                        <input id="bfindex-s2" type="datetime-local" :value="toLocalInput(editingBlock.expire_at)" @input="editingBlock.expire_at = fromLocalInput($event.target.value)" class="form-input text-xs">
                                    </div>
                                </div>
                                <p class="text-[10px] editor-text-muted">Leave both empty to show it always. Times are in your time zone; outside the window the block is hidden from the page and its address stops working.</p>
                                <div x-show="['link', 'product', 'whatsapp', 'app'].includes(editingBlock.type)">
                                    <label for="bfindex-s3" class="form-label text-xs mb-1">PIN <span class="editor-text-muted font-normal">(optional, 4–8 digits)</span></label>
                                    <input id="bfindex-s3" type="text" inputmode="numeric" pattern="[0-9]{4,8}" maxlength="8" :value="editingBlock.settings?.pin ?? ''" @input="setProductSetting('pin', $event.target.value.replace(/\D/g, ''))" class="form-input text-xs" placeholder="e.g. 2468" autocomplete="off">
                                    <p class="text-[10px] editor-text-muted mt-1">Visitors enter it once before the address opens. Share the PIN yourself (a story, a newsletter).</p>
                                </div>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'link'">
                            <div class="p-3 rounded-lg space-y-3" style="background: var(--editor-card-bg)">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4"/></svg>
                                        <span class="text-sm font-medium editor-text">Send some visitors elsewhere</span>
                                    </div>
                                    <button type="button" class="btn btn-secondary text-xs py-1 px-2" @click="addRule()" x-show="(editingBlock.settings?.rules || []).length < 10">+ Add rule</button>
                                </div>
                                <p class="text-[10px] editor-text-muted" x-show="!(editingBlock.settings?.rules || []).length">Rules are checked top to bottom, the first that fits wins; everyone else goes to the address above. Match by country, phone or computer, iPhone or Android, and the hours of the day (your time zone).</p>
                                <template x-for="(r, i) in (editingBlock.settings?.rules || [])" :key="i">
                                    <div class="be-rule">
                                        <input type="text" maxlength="60" :value="r.country ?? ''" @input="setRule(i, 'country', $event.target.value)" class="form-input text-xs" placeholder="Countries: ID, SG" :aria-label="'Rule ' + (i + 1) + ' countries'">
                                        <select :value="r.device || 'any'" @change="setRule(i, 'device', $event.target.value)" class="form-input text-xs" :aria-label="'Rule ' + (i + 1) + ' device'"><option value="any">Any device</option><option value="mobile">Phone</option><option value="tablet">Tablet</option><option value="desktop">Computer</option></select>
                                        <select :value="r.os || 'any'" @change="setRule(i, 'os', $event.target.value)" class="form-input text-xs" :aria-label="'Rule ' + (i + 1) + ' system'"><option value="any">Any system</option><option value="ios">iPhone / iPad</option><option value="android">Android</option></select>
                                        <div class="flex items-center gap-1 text-xs"><input type="number" min="0" max="23" :value="r.from ?? ''" @input="setRule(i, 'from', $event.target.value)" class="form-input text-xs" placeholder="From h" :aria-label="'Rule ' + (i + 1) + ' from hour'"><span>–</span><input type="number" min="0" max="23" :value="r.to ?? ''" @input="setRule(i, 'to', $event.target.value)" class="form-input text-xs" placeholder="To h" :aria-label="'Rule ' + (i + 1) + ' to hour'"></div>
                                        <input type="url" :value="r.url ?? ''" @input="setRule(i, 'url', $event.target.value)" class="form-input text-xs be-rule-url" placeholder="https://… go here instead" :aria-label="'Rule ' + (i + 1) + ' address'">
                                        <button type="button" class="icon-btn" @click="removeRule(i)" :aria-label="'Remove rule ' + (i + 1)"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="editingBlock?.type === 'link'">
                            <div class="p-3 rounded-lg space-y-4" style="background: var(--editor-card-bg)">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                    </svg>
                                    <span class="text-sm font-medium editor-text">Animations</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="bfindex-28" class="form-label text-xs mb-1">Entrance Animation</label>
                                        <select id="bfindex-28" x-model="editingBlock.entrance_animation" class="form-input text-xs">
                                            <option value="none">None</option>
                                            <option value="fade">Fade In</option>
                                            <option value="slide-up">Slide Up</option>
                                            <option value="slide-down">Slide Down</option>
                                            <option value="pop">Pop/Zoom</option>
                                            <option value="bounce">Bounce</option>
                                            <option value="flip">Flip</option>
                                            <option value="stagger">Stagger</option>
                                        </select>
                                        <p class="text-[10px] editor-text-muted mt-1">Animation when link first appears</p>
                                    </div>
                                    <div>
                                        <label for="bfindex-29" class="form-label text-xs mb-1">Attention Animation</label>
                                        <select id="bfindex-29" x-model="editingBlock.attention_animation" class="form-input text-xs">
                                            <option value="none">None</option>
                                            <option value="pulse">Pulse</option>
                                            <option value="bounce">Bounce</option>
                                            <option value="shake">Shake</option>
                                            <option value="wiggle">Wiggle</option>
                                            <option value="glow">Glow</option>
                                            <option value="heartbeat">Heartbeat</option>
                                        </select>
                                        <p class="text-[10px] editor-text-muted mt-1">Continuous animation loop</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="flex items-center justify-between p-3 rounded-lg" style="background: var(--editor-card-bg)">
                            <div>
                                <div class="text-sm font-medium editor-text">Active</div>
                                <div class="text-xs editor-text-muted">Show this block on your page</div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="editingBlock.is_active" aria-label="Show this block on your page">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button @click="closeBlockEditor()" class="btn btn-secondary">Cancel</button>
                <button @click="saveEditingBlock()" 
                        :disabled="!isBlockValid"
                        :class="{ 'opacity-50 cursor-not-allowed': !isBlockValid }"
                        class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
    </template>
    <template x-if="showDiscardModal">
    <div class="modal-overlay" style="z-index: 9999;">
        <div class="modal-content" x-modal="showDiscardModal" data-modal-close="cancelDiscard()" style="max-width: 400px;" @click.stop>
            <div class="p-6 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-amber-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold editor-text mb-2">Discard Changes?</h3>
                <p class="editor-text-muted mb-6">You have unsaved changes. Are you sure you want to leave? All changes will be lost.</p>
                <div class="flex gap-3">
                    <button @click="cancelDiscard()" class="flex-1 px-4 py-2.5 btn-secondary font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmDiscard()" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                        Discard Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>
    <template x-if="showAddBlockModal">
    <div class="modal-overlay" @click.self="showAddBlockModal = false">
        <div class="modal-content" x-modal="showAddBlockModal" style="max-width: 600px;" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Add Block</h3>
                <button @click="showAddBlockModal = false" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                @include('bio::editor.add-block-modal')
            </div>
        </div>
    </div>
    </template>
    <template x-if="showLinkLibraryModal">
    <div class="modal-overlay" @click.self="showLinkLibraryModal = false" style="z-index: 10000;">
        <div class="modal-content" x-modal="showLinkLibraryModal" style="max-width: 500px;" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Link Library</h3>
                <button @click="showLinkLibraryModal = false" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <input aria-label="Search your links" type="text" x-model="librarySearch" @input.debounce.300ms="fetchLibraryLinks()" placeholder="Search your links..." class="form-input w-full">
                </div>
                <div class="space-y-2 max-h-[300px] overflow-y-auto custom-scrollbar">
                    <template x-if="libraryLoading">
                        <div class="text-center py-8 text-slate-500">
                            <svg class="animate-spin h-6 w-6 mx-auto mb-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading links...
                        </div>
                    </template>
                    <template x-if="!libraryLoading && libraryLinks.length === 0">
                        <div class="text-center py-8 text-slate-500">
                            <p>No links found.</p>
                            <p class="text-xs mt-1">Create short links in your dashboard to see them here.</p>
                        </div>
                    </template>
                    <template x-for="link in libraryLinks" :key="link.id">
                        <button @click="selectLibraryLink(link)" class="w-full text-left p-3 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700 group">
                            <div class="flex justify-between items-start">
                                <div class="font-medium text-sm text-slate-900 dark:text-white group-hover:text-blue-600 transition-colors" x-text="link.title || 'Untitled Link'"></div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-sm bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 font-mono">Short</span>
                            </div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 font-mono mt-1" x-text="link.short_url"></div>
                            <div class="text-xs text-slate-400 truncate mt-0.5" x-text="link.url"></div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
    </template>
    <template x-if="showSocialPicker">
    <div class="modal-overlay" @click.self="showSocialPicker = false">
        <div class="modal-content" x-modal="showSocialPicker" style="max-width: 480px;" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Add Social Icon</h3>
                <button @click="showSocialPicker = false" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="p-4 border-b border-(--editor-border)">
                    <input aria-label="Search platforms" type="text" x-model="socialSearch" placeholder="Search platforms..." 
                           class="form-input" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
                </div>
                <div class="max-h-80 overflow-y-auto">
                    <template x-for="(platform, id) in filteredPlatforms" :key="id">
                        <button @click="selectSocialPlatform(id)" 
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-100 dark:hover:bg-slate-700/50 border-b border-(--editor-border)/50 transition-colors">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" 
                                 :style="{ backgroundColor: platform.color || '#6b7280' }">
                                <img :src="'/images/brands/' + (platform.icon || id + '.svg') + '?v=20251212'" 
                                     class="w-6 h-6" style="filter: brightness(0) invert(1);" 
                                     data-fallback-src="/images/brands/link.svg?v=20251212">
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-medium editor-text" x-text="platform.name"></div>
                                <div class="text-xs editor-text-muted" x-text="platform.placeholder"></div>
                            </div>
                            <svg class="w-5 h-5 editor-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
    </template>
    <!-- Social Link Editor Modal -->
    <div x-show="showSocialEditor" x-cloak class="modal-overlay" @click.self="cancelSocialEdit()">
        <template x-if="editingSocial">
            <div class="modal-content" x-modal="showSocialEditor" data-modal-close="cancelSocialEdit()" style="max-width: 420px;" @click.stop>
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" 
                             :style="{ backgroundColor: platforms[editingSocial.platform]?.color || '#6b7280' }">
                            <img :src="getIconUrl(editingSocial.platform || 'link', 'ffffff')" 
                                 class="w-6 h-6"
                                 x-on:error="if (!$el.dataset.fallbackDone) { $el.dataset.fallbackDone = 1; $el.src = '/images/brands/' + (editingSocial.platform || 'link') + '.svg?v=20251212'; }">
                        </div>
                        <h3 class="modal-title" x-text="'Add ' + (platforms[editingSocial.platform]?.name || 'Social') + ' Link'"></h3>
                    </div>
                    <button @click="cancelSocialEdit()" class="modal-close" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="bfindex-30" class="form-label" x-text="platforms[editingSocial.platform]?.name + ' Username or URL'"></label>
                        <input id="bfindex-30" type="text" 
                               x-model="editingSocial.value"
                               :placeholder="platforms[editingSocial.platform]?.placeholder || 'Enter username or URL'"
                               class="form-input"
                               autocomplete="off" data-lpignore="true" data-1p-ignore="true"
                               @keydown.enter="saveSocialEdit()">
                        <p class="text-xs editor-text-muted mt-2">
                            <span x-text="'Example: ' + (platforms[editingSocial.platform]?.placeholder || 'username')"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer flex justify-end gap-3">
                    <button @click="cancelSocialEdit()" class="btn btn-secondary">Cancel</button>
                    <button @click="saveSocialEdit()" class="btn btn-primary" :disabled="!editingSocial.value?.trim()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save
                    </button>
                </div>
            </div>
        </template>
    </div>
    <template x-if="showTextEditor">
    <div class="modal-overlay" @click.self="showTextEditor = false">
        <div class="modal-content" x-modal="showTextEditor" style="max-width: 600px;" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Edit Text Block</h3>
                <button @click="showTextEditor = false" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex flex-wrap gap-1 p-2 rounded-t-lg" style="background: var(--editor-card-bg);">
                    <button aria-label="Bold" @click="formatText('bold')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                        </svg>
                    </button>
                    <button aria-label="Italic" @click="formatText('italic')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Italic">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="19" y1="4" x2="10" y2="4" stroke-width="2"/>
                            <line x1="14" y1="20" x2="5" y2="20" stroke-width="2"/>
                            <line x1="15" y1="4" x2="9" y2="20" stroke-width="2"/>
                        </svg>
                    </button>
                    <button aria-label="Underline" @click="formatText('underline')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v7a5 5 0 0010 0V4"/>
                            <line x1="4" y1="21" x2="20" y2="21" stroke-width="2"/>
                        </svg>
                    </button>
                    <button aria-label="Strikethrough" @click="formatText('strikeThrough')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Strikethrough">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.5 9.5c0-2.5-2-4.5-4.5-4.5s-4.5 2-4.5 4.5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 14.5c0 2.5 2 4.5 4.5 4.5s4.5-2 4.5-4.5"/>
                            <line x1="4" y1="12" x2="20" y2="12" stroke-width="2"/>
                        </svg>
                    </button>
                    <div class="w-px h-6 mx-1 self-center" style="background: var(--editor-border);"></div>
                    <button aria-label="Align Left" @click="formatText('justifyLeft')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Align Left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="3" y1="6" x2="21" y2="6" stroke-width="2"/>
                            <line x1="3" y1="12" x2="15" y2="12" stroke-width="2"/>
                            <line x1="3" y1="18" x2="18" y2="18" stroke-width="2"/>
                        </svg>
                    </button>
                    <button aria-label="Align Center" @click="formatText('justifyCenter')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Align Center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="3" y1="6" x2="21" y2="6" stroke-width="2"/>
                            <line x1="6" y1="12" x2="18" y2="12" stroke-width="2"/>
                            <line x1="4" y1="18" x2="20" y2="18" stroke-width="2"/>
                        </svg>
                    </button>
                    <button aria-label="Align Right" @click="formatText('justifyRight')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-sm" title="Align Right">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <line x1="3" y1="6" x2="21" y2="6" stroke-width="2"/>
                            <line x1="9" y1="12" x2="21" y2="12" stroke-width="2"/>
                            <line x1="6" y1="18" x2="21" y2="18" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
                <div x-ref="richTextEditor" 
                     contenteditable="true" 
                     class="min-h-[250px] p-4 rounded-b-lg focus:outline-hidden editor-text"
                     style="min-height: 200px; background: var(--editor-input-bg); border: 1px solid var(--editor-border); border-top: none;"
                     @input="updateTextBlockContent($event)">
                </div>
            </div>
            <div class="modal-footer">
                <button @click="showTextEditor = false" class="btn btn-secondary">Cancel</button>
                <button @click="saveTextBlock()" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
    </template>
    <template x-if="showImageUploader">
    <div class="modal-overlay" @click.self="showImageUploader = false">
        <div class="modal-content" x-modal="showImageUploader" style="max-width: 500px;" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">Add Image</h3>
                <button @click="showImageUploader = false" class="modal-close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex gap-2 mb-4">
                    <button @click="imageUploadTab = 'upload'" 
                            :class="imageUploadTab === 'upload' ? 'bg-blue-600 text-white' : 'btn-secondary'"
                            class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors">
                        Upload File
                    </button>
                    <button @click="imageUploadTab = 'url'" 
                            :class="imageUploadTab === 'url' ? 'bg-blue-600 text-white' : 'btn-secondary'"
                            class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors">
                        From URL
                    </button>
                </div>
                <div x-show="imageUploadTab === 'upload'">
                    <div class="border-2 border-dashed border-(--editor-border) rounded-xl p-8 text-center">
                        <input aria-label="Upload image" type="file" accept="image/*" class="hidden" x-ref="imageBlockInput" @change="uploadImageBlock($event)">
                        <svg class="w-12 h-12 mx-auto editor-text-muted mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="editor-text-muted mb-2">Drag and drop or click to upload</p>
                        <button @click="$refs.imageBlockInput.click()" class="btn btn-secondary">Choose File</button>
                    </div>
                </div>
                <!-- URL Tab -->
                <div x-show="imageUploadTab === 'url'">
                    <div class="form-group">
                        <label for="bfindex-31" class="form-label">Image URL</label>
                        <input id="bfindex-31" type="url" x-model="imageBlockUrl" class="form-input" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="form-group">
                        <label for="bfindex-32" class="form-label">Alt Text (optional)</label>
                        <input id="bfindex-32" type="text" x-model="imageBlockAlt" class="form-input" placeholder="Image description">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button @click="showImageUploader = false" class="btn btn-secondary">Cancel</button>
                <button @click="saveImageBlock()" class="btn btn-primary">Add Image</button>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script nonce="{{ Vite::cspNonce() }}">
(function () {
    // Pauses file inputs while the server is low on disk (see UploadStorage), checked every 30 s while visible.
    function refreshStorage() {
        fetch(@json(route('bio.upload-status')), { cache: 'no-store', headers: { Accept: 'application/json' } })
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(status => {
                const warning = document.getElementById('bio-storage-warning');
                if (!warning) return;
                warning.hidden = status.available;
                warning.textContent = status.message;
                document.querySelectorAll('input[type="file"]').forEach(input => {
                    if (!status.available && !input.disabled) { input.dataset.storagePaused = '1'; input.disabled = true; }
                    else if (status.available && input.dataset.storagePaused) { delete input.dataset.storagePaused; input.disabled = false; }
                });
            }).catch(() => {});
    }
    refreshStorage();
    setInterval(() => { if (!document.hidden) refreshStorage(); }, 30000);
})();
</script>
@endpush
