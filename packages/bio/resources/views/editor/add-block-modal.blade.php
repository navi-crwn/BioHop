<!-- Add Block Modal Content -->
<div class="space-y-4">
    <!-- Search Bar -->
    <div class="relative flex items-center">
        <svg class="absolute left-3 w-4 h-4 pointer-events-none" style="color: var(--editor-text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input aria-label="Search blocks or platforms" type="text" x-model="blockSearch" placeholder="Search blocks or platforms..." class="form-input pl-10 pr-10 py-2 w-full" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
        <button aria-label="Clear search" x-show="blockSearch" @click="blockSearch = ''" class="absolute right-3" style="color: var(--editor-text-muted);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <!-- Block catalogue (same list as the command palette) -->
    <template x-for="g in blockGroups()" :key="g.title">
        <div>
            <h4 class="text-xs font-medium mb-2" style="color: var(--editor-text-muted);" x-text="g.title"></h4>
            <div class="grid grid-cols-2 gap-2">
                <template x-for="item in g.items" :key="item[0]">
                    <button type="button" @click="runCatalogItem(item)" class="block-modal-btn bento-card">
                        <span class="bento-icon" :style="{ background: item[3] }"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item[4]"/></svg></span>
                        <span class="bento-text"><b x-text="item[1]"></b><small x-text="item[2]"></small></span>
                    </button>
                </template>
            </div>
        </div>
    </template>
    <p class="text-sm text-center py-2" style="color: var(--editor-text-muted);" x-show="blockSearch && !blockGroups().length">No block matches "<span x-text="blockSearch"></span>". Platform links are below.</p>
    <!-- Brand Link Templates -->
    <div>
        <h4 class="text-sm font-medium mb-3" style="color: var(--editor-text-muted);">Platform Links</h4>
        <!-- Category Filter -->
        <div class="flex flex-wrap gap-2 mb-3">
            <button @click="blockCategoryFilter = 'all'" 
                :class="blockCategoryFilter === 'all' ? 'bg-blue-600 text-white' : 'filter-inactive'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-all">All</button>
            <button @click="blockCategoryFilter = 'social'" 
                :class="blockCategoryFilter === 'social' ? 'bg-blue-600 text-white' : 'filter-inactive'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-all">Social</button>
            <button @click="blockCategoryFilter = 'music'" 
                :class="blockCategoryFilter === 'music' ? 'bg-blue-600 text-white' : 'filter-inactive'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-all">Music</button>
            <button @click="blockCategoryFilter = 'gaming'" 
                :class="blockCategoryFilter === 'gaming' ? 'bg-blue-600 text-white' : 'filter-inactive'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-all">Gaming</button>
            <button @click="blockCategoryFilter = 'developer'" 
                :class="blockCategoryFilter === 'developer' ? 'bg-blue-600 text-white' : 'filter-inactive'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-all">Dev</button>
            <button @click="blockCategoryFilter = 'messaging'" 
                :class="blockCategoryFilter === 'messaging' ? 'bg-blue-600 text-white' : 'filter-inactive'"
                class="px-3 py-1 rounded-full text-xs font-medium transition-all">Messaging</button>
        </div>
        <div class="grid grid-cols-5 gap-2 max-h-64 overflow-y-auto pr-2">
            @foreach(config('brands.platforms', []) as $brandId => $brand)
            <button 
                @click="addBrandBlock('{{ $brandId }}')"
                x-show="(blockCategoryFilter === 'all' || '{{ $brand['category'] ?? 'generic' }}' === blockCategoryFilter) && (blockSearch === '' || '{{ strtolower($brand['name'] ?? $brandId) }}'.includes(blockSearch.toLowerCase()) || '{{ $brandId }}'.includes(blockSearch.toLowerCase()))"
                class="block-modal-btn p-3 rounded-lg hover:border-blue-500 hover:shadow-md transition-all group"
            >
                <div class="w-10 h-10 rounded-lg flex items-center justify-center mx-auto mb-1" style="background-color: {{ $brand['color'] ?? '#6b7280' }}">
                    <img src="/images/brands/{{ $brand['icon'] ?? $brandId . '.svg' }}" class="w-6 h-6" style="filter: brightness(0) invert(1);" alt="{{ $brand['name'] ?? $brandId }}" data-fallback="hide">
                </div>
                <p class="text-xs text-center font-medium truncate" style="color: var(--editor-text-muted);">{{ $brand['name'] ?? ucfirst($brandId) }}</p>
            </button>
            @endforeach
        </div>
    </div>
</div>
<style>
    .block-modal-btn {
        border: 1px solid var(--editor-border);
    }
    .block-modal-btn:hover {
        background: var(--editor-hover-bg);
    }
    .filter-inactive {
        background: var(--editor-card-bg);
        color: var(--editor-text);
        border: 1px solid var(--editor-border);
    }
</style>
