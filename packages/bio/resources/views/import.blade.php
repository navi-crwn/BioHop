<x-app-layout>
    <x-slot name="pageTitle">{{ __('Import from Linktree') }}</x-slot>
    <x-page-header title="{{ __('Import from Linktree') }}" subtitle="{{ __('Paste your Linktree address. Your title, picture, bio, links, groups and social icons come over as a draft page you can finish here.') }}" />

    <x-flash />

    <form method="POST" action="{{ route('bio.import.store') }}" class="glass-card mt-4 max-w-xl space-y-4">
        @csrf
        <div>
            <label for="linktree" class="form-label">{{ __('Your Linktree address') }}</label>
            <input id="linktree" name="linktree" type="text" value="{{ old('linktree') }}" required autocomplete="off" placeholder="linktr.ee/yourname" class="form-input w-full">
            @error('linktree')<p class="mt-1 text-sm text-rose-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="slug" class="form-label">{{ __('New address here') }}</label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted whitespace-nowrap">{{ public_host() }}/b/</span>
                <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required minlength="{{ $slugMin }}" maxlength="20" pattern="[a-zA-Z0-9]+" autocomplete="off" placeholder="{{ __('yourname') }}" class="form-input w-full">
            </div>
            <p class="mt-1 text-xs text-muted">{{ __('Letters and numbers only. You can change it later.') }}</p>
            @error('slug')<p class="mt-1 text-sm text-rose-500">{{ $message }}</p>@enderror
        </div>
        <ul class="list-disc space-y-1 pl-5 text-xs text-muted">
            <li>{{ __('Only the public page is read; nothing is signed in to and nothing is changed on Linktree.') }}</li>
            <li>{{ __('Groups become collections, headers stay headers, link pictures are kept as thumbnails.') }}</li>
            <li>{{ __('Colours and fonts are not copied: pick a theme in the editor afterwards.') }}</li>
        </ul>
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="btn-primary btn-sm">{{ __('Import my page') }}</button>
            <a href="{{ route('bio.create') }}" class="btn-soft btn-sm">{{ __('Start from scratch instead') }}</a>
        </div>
    </form>
</x-app-layout>
