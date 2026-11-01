<!-- Settings Tab Content -->
<!-- SEO Settings with Google Preview -->
<div class="editor-card">
    <div class="editor-card-header">
        <h3 class="editor-card-title">SEO Settings</h3>
    </div>
    <div class="space-y-4">
        <!-- Google Preview -->
        <div class="bg-white border border-slate-200 rounded-lg p-4 dark:bg-slate-900 dark:border-(--editor-border)">
            <p class="text-xs editor-text-muted mb-3 uppercase tracking-wide font-medium">Google Search Preview</p>
            <div class="space-y-1">
                <p class="text-sm text-blue-700 dark:text-blue-400 truncate" x-text="'{{ public_url('b') }}/' + bioPage.slug"></p>
                <h4 class="text-lg text-blue-800 dark:text-blue-300 font-medium truncate hover:underline cursor-pointer" 
                    x-text="(bioPage.seo_title || bioPage.title || 'Your Page Title') + ' | By LinkHop'"></h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-2" 
                   x-text="bioPage.seo_description || bioPage.bio || 'Your page description will appear here. Write a compelling description to improve click-through rates from search results.'"></p>
            </div>
        </div>
        <div class="form-group">
            <label for="bfsettin-1" class="form-label">Page Title</label>
            <input id="bfsettin-1" type="text" x-model="bioPage.seo_title" class="form-input" placeholder="Leave empty to use display name" maxlength="60" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
            <div class="flex justify-between mt-1">
                <p class="text-xs editor-text-muted">Appears in browser tab and search results (+ " | By LinkHop")</p>
                <p class="text-xs" :class="(bioPage.seo_title || '').length > 52 ? 'text-orange-500' : 'editor-text-muted'" x-text="(bioPage.seo_title || '').length + '/60'"></p>
            </div>
            <p class="be-field-error" x-show="fieldErrors.seo_title" x-cloak x-text="fieldErrors.seo_title"></p>
        </div>
        <div class="form-group">
            <label for="bfsettin-2" class="form-label">Meta Description</label>
            <textarea id="bfsettin-2" x-model="bioPage.seo_description" class="form-input form-textarea" placeholder="Brief description for search engines" rows="2" maxlength="160" autocomplete="off" data-lpignore="true" data-1p-ignore="true"></textarea>
            <div class="flex justify-between mt-1">
                <p class="text-xs editor-text-muted">Recommended: 120-160 characters</p>
                <p class="text-xs" :class="(bioPage.seo_description || '').length > 150 ? 'text-orange-500' : 'editor-text-muted'" x-text="(bioPage.seo_description || '').length + '/160'"></p>
            </div>
            <p class="be-field-error" x-show="fieldErrors.seo_description" x-cloak x-text="fieldErrors.seo_description"></p>
        </div>
    </div>
</div>
<!-- Sharing: picture and favicon -->
<div class="editor-card">
    <div class="editor-card-header">
        <h3 class="editor-card-title">Sharing</h3>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label">Share picture (Open Graph)</label>
            <p class="text-xs editor-text-muted mb-2">Shown when the page is shared on WhatsApp, Telegram, X or Facebook. 1200×630 works best; without one the profile picture is used.</p>
            <input aria-label="Upload share picture" type="file" accept="image/*" class="hidden" x-ref="ogInput" @change="uploadPageImage('og_image', $event)">
            <div class="flex items-center gap-2">
                <button type="button" @click="$refs.ogInput.click()" class="btn btn-secondary text-sm py-2 flex-1">Upload picture</button>
                <button type="button" x-show="bioPage.og_image" @click="bioPage.og_image = ''" class="btn btn-secondary text-sm py-2">Remove</button>
            </div>
            <template x-if="bioPage.og_image"><img :src="pageImageUrl(bioPage.og_image)" alt="Share picture" class="mt-2 w-full h-24 object-cover rounded-lg"></template>
        </div>
        <div>
            <label class="form-label">Favicon</label>
            <p class="text-xs editor-text-muted mb-2">The small icon in the browser tab. A square PNG, 64×64 or larger.</p>
            <input aria-label="Upload favicon" type="file" accept="image/png,image/x-icon,image/svg+xml,image/webp" class="hidden" x-ref="favInput" @change="uploadPageImage('favicon', $event)">
            <div class="flex items-center gap-2">
                <button type="button" @click="$refs.favInput.click()" class="btn btn-secondary text-sm py-2 flex-1">Upload favicon</button>
                <button type="button" x-show="bioPage.favicon" @click="bioPage.favicon = ''" class="btn btn-secondary text-sm py-2">Remove</button>
            </div>
            <template x-if="bioPage.favicon"><img :src="pageImageUrl(bioPage.favicon)" alt="Favicon" class="mt-2 w-10 h-10 rounded-md object-contain" style="background: var(--editor-input-bg)"></template>
        </div>
    </div>
</div>
<!-- Privacy & Visibility -->
<div class="editor-card">
    <div class="editor-card-header">
        <h3 class="editor-card-title">Privacy & Visibility</h3>
    </div>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">Public Page</p>
                <p class="text-sm editor-text-muted">Anyone with the link can view your page</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" x-model="bioPage.is_published" aria-label="Public page">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">Search Engine Indexing</p>
                <p class="text-sm editor-text-muted">Allow search engines to index this page</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" x-model="bioPage.allow_indexing" aria-label="Search engine indexing">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <!-- Unlisted: not in search engines, not in the directory; the address still works -->
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">Unlisted</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Only people with the address find the page: no search engines, not in the directory.</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" :checked="!bioPage.allow_indexing && !bioPage.show_in_directory" @change="bioPage.allow_indexing = !$event.target.checked; bioPage.show_in_directory = !$event.target.checked" aria-label="Unlisted">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <!-- Privacy mode: no counting at all -->
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">Privacy mode</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Stops counting views and clicks for this page. Analytics stay as they are and stop growing.</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" x-model="bioPage.privacy_mode" aria-label="Privacy mode">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <!-- Fallback address while unpublished -->
        <div x-show="!bioPage.is_published" x-transition>
            <label for="bfsettin-fb" class="form-label">While unpublished, send visitors to</label>
            <input id="bfsettin-fb" type="url" x-model="bioPage.fallback_url" class="form-input" placeholder="https://your-site.example" autocomplete="off">
            <p class="text-xs editor-text-muted mt-1">Leave empty to show the usual "not found" page.</p>
        </div>
        <!-- UTM on outgoing links -->
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-900 dark:text-white">Add UTM to outgoing links</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Appends utm_source=biohop, utm_medium=bio and utm_campaign=<span x-text="bioPage.slug"></span> to every http(s) link that has no UTM of its own, so clicks show up in the destination's analytics.</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" x-model="bioPage.auto_utm" aria-label="Add UTM parameters to outgoing links">
                <span class="toggle-slider"></span>
            </label>
        </div>
        <!-- 18+ Adult Content & Warning Gate -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        {{ __('Content warning') }}
                    </p>
                    <p class="text-sm editor-text-muted">{{ __('The creator has marked this page as containing sensitive material.') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer text-xs font-medium"
                       :class="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) === 'none' ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-(--editor-border)'">
                    <input type="radio" name="content_warning" value="none" 
                           :checked="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) === 'none'"
                           @change="bioPage.content_warning = 'none'; bioPage.is_adult_content = false" class="sr-only">
                    <span>{{ __('None') }}</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer text-xs font-medium"
                       :class="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) === 'sensitive' ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300' : 'border-(--editor-border)'">
                    <input type="radio" name="content_warning" value="sensitive" 
                           :checked="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) === 'sensitive'"
                           @change="bioPage.content_warning = 'sensitive'; bioPage.is_adult_content = false" class="sr-only">
                    <span>{{ __('Sensitive content') }}</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer text-xs font-medium"
                       :class="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) === 'adult' ? 'border-red-500 bg-red-50 dark:bg-red-950/30 text-red-700 dark:text-red-300' : 'border-(--editor-border)'">
                    <input type="radio" name="content_warning" value="adult" 
                           :checked="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) === 'adult'"
                           @change="bioPage.content_warning = 'adult'; bioPage.is_adult_content = true" class="sr-only">
                    <span>{{ __('Adult 18+') }}</span>
                </label>
            </div>
            <div x-show="(bioPage.content_warning || (bioPage.is_adult_content ? 'adult' : 'none')) !== 'none'" x-collapse>
                <label for="bio-warning-reason" class="form-label text-xs">{{ __('Reason (optional)') }}</label>
                <input id="bio-warning-reason" type="text" x-model="bioPage.warning_reason" class="form-input text-xs" maxlength="255">
            </div>
        </div>
        <!-- Password Protection -->
        <div class="border-t border-(--editor-border) pt-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Password Protection
                    </p>
                    <p class="text-sm editor-text-muted">Require a password to view this page</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" aria-label="Password protection" x-model="bioPage.password_enabled" @change="if(!$event.target.checked) bioPage.password = ''">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div x-show="bioPage.password_enabled" x-collapse>
                <div class="form-group mb-0">
                    <label for="bfsettin-3" class="form-label">Password</label>
                    <div class="relative">
                        <input id="bfsettin-3" :type="showPassword ? 'text' : 'password'" 
                               x-model="bioPage.password" 
                               class="form-input pr-10" 
                               placeholder="Enter password for your page"
                               maxlength="50"
                               autocomplete="new-password"
                               data-lpignore="true"
                               data-1p-ignore="true"
                               data-form-type="other">
                        <button :aria-label="showPassword ? 'Hide password' : 'Show password'" type="button" @click="showPassword = !showPassword" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 editor-text-muted hover:editor-text">
                            <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs editor-text-muted mt-1">Visitors must enter this password to access your page</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Analytics -->
<div class="editor-card">
    <div class="editor-card-header">
        <h3 class="editor-card-title">Analytics</h3>
    </div>
    <div class="space-y-4">
        <div class="form-group">
            <label for="bfsettin-4" class="form-label">Google Analytics ID</label>
            <input id="bfsettin-4" type="text" x-model="bioPage.google_analytics_id" class="form-input" placeholder="G-XXXXXXXXXX" pattern="G-[A-Z0-9]+" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-form-type="other" inputmode="text">
            <p class="text-xs editor-text-muted mt-1">Format: G-XXXXXXXXXX (e.g., G-ABC123DEF4)</p>
        </div>
        <div class="form-group">
            <label for="bfsettin-5" class="form-label">Facebook Pixel ID</label>
            <input id="bfsettin-5" type="text" x-model="bioPage.facebook_pixel_id" class="form-input" placeholder="123456789012345" pattern="\d{10,20}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-form-type="other" inputmode="numeric">
            <p class="text-xs editor-text-muted mt-1">Format: 15 digit number (e.g., 123456789012345)</p>
        </div>
        <div class="form-group">
            <label for="bfsettin-6" class="form-label">TikTok Pixel ID</label>
            <input id="bfsettin-6" type="text" x-model="bioPage.tiktok_pixel_id" class="form-input" placeholder="CXXXXXXXXXXXXXXXXXX" pattern="[A-Z0-9]{20,30}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-form-type="other" inputmode="text">
            <p class="text-xs editor-text-muted mt-1">Format: 20-30 character ID (e.g., C123ABC456DEF789GHI)</p>
        </div>
        <div class="form-group">
            <label for="bfsettin-7" class="form-label">Google Tag Manager</label>
            <input id="bfsettin-7" type="text" x-model="bioPage.gtm_id" class="form-input" placeholder="GTM-XXXXXXX" pattern="GTM-[A-Z0-9]{4,12}" autocomplete="off" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
            <p class="text-xs editor-text-muted mt-1">Like the pixels, it loads only after the visitor agrees in the consent banner.</p>
        </div>
    </div>
</div>
<!-- Footer line -->
<div class="editor-card">
    <div class="editor-card-header">
        <h3 class="editor-card-title">Footer</h3>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="form-group">
            <label for="bfsettin-8" class="form-label">Footer line</label>
            <input id="bfsettin-8" type="text" maxlength="200" x-model="bioPage.footer_note" class="form-input" placeholder="Prices in IDR · Affiliate links marked" autocomplete="off">
            <p class="text-xs editor-text-muted mt-1">A short disclaimer, imprint or thank-you under the blocks.</p>
        </div>
        <div class="form-group">
            <label for="bfsettin-9" class="form-label">Footer link <span class="editor-text-muted font-normal">(optional)</span></label>
            <input id="bfsettin-9" type="url" x-model="bioPage.footer_url" class="form-input" placeholder="https://your-site.example/terms" autocomplete="off">
        </div>
    </div>
    <p class="text-xs editor-text-muted mt-2">The "Powered by" line is in Design → Footer.</p>
</div>
<!-- Danger Zone -->
<div class="editor-card border-red-200 dark:border-red-900">
    <div class="editor-card-header">
        <h3 class="editor-card-title text-red-600">Danger Zone</h3>
    </div>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <div>
                <p class="font-medium text-red-800 dark:text-red-300">Delete Page</p>
                <p class="text-sm text-red-600 dark:text-red-400">Permanently delete this bio page and all its data</p>
            </div>
            <button @click="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                Delete Page
            </button>
        </div>
    </div>
</div>
