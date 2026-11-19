<?php

namespace App\Filament\Actions;

use Filament\Actions\BulkActionGroup;

class CatalogBulkActionGroup extends BulkActionGroup
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Actions'));
        $this->labeledFrom(null);

        $this->extraAttributes([
            'x-bind:disabled' => '! getSelectedRecordsCount()',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraDropdownAttributes(): array
    {
        return [
            ...parent::getExtraDropdownAttributes(),
            'x-show' => 'true',
            'x-on:mousedown.capture' => 'if (! getSelectedRecordsCount()) $event.stopImmediatePropagation()',
            'x-on:keydown.capture' => 'if (! getSelectedRecordsCount()) $event.stopImmediatePropagation()',
        ];
    }

    public function toEmbeddedHtml(): string
    {
        return '<template x-teleport="#catalog-bulk-actions">'
            .parent::toEmbeddedHtml()
            .'</template>';
    }
}
