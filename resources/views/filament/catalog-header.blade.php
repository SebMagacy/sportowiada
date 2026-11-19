<div x-data="{}" class="catalog-page-header">
    <template x-teleport="#catalog-page-heading">
        <h1 class="fi-header-heading">
            {{ $this->getHeading() }}
        </h1>
    </template>

    <x-filament-panels::header
        :breadcrumbs="filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : []"
        :subheading="$this->getSubheading()"
    />

    <x-catalog-toolbar :actions="$this->getCachedHeaderActions()" />
</div>