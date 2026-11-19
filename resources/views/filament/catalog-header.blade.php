<div x-data="{}">
    <template x-teleport="#catalog-page-heading">
        <h1 class="fi-header-heading">
            {{ $this->getHeading() }}
        </h1>
    </template>

    <x-filament-panels::header :breadcrumbs="filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : []" :actions="$this->getCachedHeaderActions()" :actions-alignment="$this->getHeaderActionsAlignment()" :subheading="$this->getSubheading()" />
</div>
