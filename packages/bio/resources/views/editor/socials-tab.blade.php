<!-- Socials Tab Content -->
<div class="space-y-4">
    <!-- Social Icons Position -->
    <div class="editor-card">
        <div class="editor-card-header">
            <h3 class="editor-card-title">Position</h3>
        </div>
        <div class="p-3">
            <div class="be-seg" role="group" aria-label="{{ __('Position') }}">
                <button type="button" @click="bioPage.social_icons_position = 'below_bio'" :aria-pressed="String(bioPage.social_icons_position === 'below_bio' || !bioPage.social_icons_position)" :class="{ 'is-on': bioPage.social_icons_position === 'below_bio' || !bioPage.social_icons_position }">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Below Bio
                </button>
                <button type="button" @click="bioPage.social_icons_position = 'bottom_page'" :aria-pressed="String(bioPage.social_icons_position === 'bottom_page')" :class="{ 'is-on': bioPage.social_icons_position === 'bottom_page' }">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    Bottom
                </button>
            </div>
        </div>
    </div>
    <!-- Active Social Icons -->
    <div class="editor-card">
        <div class="editor-card-header">
            <h3 class="editor-card-title">Social Icons</h3>
            <span class="text-xs editor-text-muted tabular-nums" x-text="(bioPage.social_links || []).length + '/{{ \Hop\Bio\Models\BioPage::SOCIAL_MAX }}'"></span>
        </div>
        <div class="p-4">
            <!-- Social Links List -->
            <div id="socials-list" class="space-y-2 mb-4" x-show="bioPage.social_links && bioPage.social_links.length > 0">
                <template x-for="(item, idx) in (bioPage.social_links || [])" :key="item._uid || (item.platform + '-' + idx)">
                    <div class="block-item is-compact" :data-id="item._uid" x-show="item">
                        <!-- Drag Handle -->
                        <div class="social-drag-handle block-drag-handle" role="button" tabindex="0"
                             :aria-label="'Move ' + (platforms[item.platform]?.name || item.platform) + '. Use the up and down arrow keys.'"
                             @keydown.arrow-up.prevent="moveSocial(idx, -1)" @keydown.arrow-down.prevent="moveSocial(idx, 1)">
                            <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </div>
                        <!-- Icon -->
                        <div class="block-icon" :title="platforms[item.platform]?.name || item.platform" :style="{ backgroundColor: getPlatformColor(item.platform) }">
                            <img :src="getPlatformIcon(item.platform)" alt="" class="w-5 h-5" style="filter: brightness(0) invert(1);" data-fallback="hide">
                        </div>
                        <!-- Input -->
                        <div class="block-content">
                            <input type="text" 
                                   x-model="item.value" 
                                   :placeholder="getSocialPlaceholder(item.platform)"
                                   @blur="validateSocialInput(idx)"
                                   autocomplete="off"
                                   data-lpignore="true"
                                   data-form-type="other"
                                   :aria-label="(platforms[item.platform]?.name || item.platform) + ' username or URL'"
                                   class="form-input">
                        </div>
                        <!-- Actions -->
                        <div class="block-actions">
                            <label class="toggle-switch">
                                <input type="checkbox" x-model="item.enabled" :aria-label="'Show ' + (platforms[item.platform]?.name || item.platform)">
                                <span class="toggle-slider"></span>
                            </label>
                            <button type="button" @click="removeSocial(idx)" class="block-action-btn delete" :aria-label="'Remove ' + (platforms[item.platform]?.name || item.platform)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <!-- Empty State -->
            <div x-show="!bioPage.social_links || bioPage.social_links.length === 0" class="text-center py-8">
                <svg class="w-12 h-12 mx-auto editor-text-muted mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="editor-text-muted mb-1">No social icons yet</p>
                <p class="text-sm editor-text-muted">Add your social profiles</p>
            </div>
            <!-- Add Social Button -->
            <button @click="showSocialPicker = true" 
                    :disabled="bioPage.social_links && bioPage.social_links.length >= {{ \Hop\Bio\Models\BioPage::SOCIAL_MAX }}"
                    class="btn btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span x-text="(bioPage.social_links && bioPage.social_links.length >= {{ \Hop\Bio\Models\BioPage::SOCIAL_MAX }}) ? @js(__('Maximum :max icons reached', ['max' => \Hop\Bio\Models\BioPage::SOCIAL_MAX])) : @js(__('Add Social Icon'))"></span>
            </button>
        </div>
    </div>
</div>
