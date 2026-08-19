@props([
    'field',
    'values',
    'allLanguages',
    'sortedLanguages',
    'activeLanguage',
    'isRemovable',
    'renderedFields' => [],
])

@php
    $fieldName = $field->getColumn();
    $existingLanguages = array_keys($values);
    $rowsJson = json_encode(
        collect($existingLanguages)->map(fn ($lang) => ['key' => $lang, 'value' => $values[$lang] ?? ''])->values()
    );
    $sortedLanguagesJson = json_encode(array_values($sortedLanguages));
    $isEditor = !in_array($field->getInputField(), [
        \MoonShine\UI\Fields\Text::class,
        \MoonShine\UI\Fields\Textarea::class,
    ]);
@endphp

<div
    x-data="{
        rows: {{ $rowsJson }},
        existingLanguages: {{ $rowsJson }}.map(r => r.key),
        allLanguages: {{ $sortedLanguagesJson }},
        fieldName: '{{ $fieldName }}',
        activeLanguage: '{{ $activeLanguage }}',
        isRemovable: {{ $isRemovable ? 'true' : 'false' }},
        showAddMenu: false,
        selectedLanguageToAdd: '',

        init() {
            this.$el.addEventListener('input', () => this.syncActiveField());
        },

        get activeValue() {
            const row = this.rows.find(r => r.key === this.activeLanguage);
            return row ? row.value : '';
        },
        set activeValue(val) {
            const row = this.rows.find(r => r.key === this.activeLanguage);
            if (row) row.value = val;
        },

        // ── Language switching ────────────────────────────────────
        switchLanguage(code) {
            if (code === this.activeLanguage) return;
            this.syncActiveField();
            this.activeLanguage = code;
            this.$nextTick(() => this.restoreActiveField());
        },

        addLanguage(lang) {
            if (!lang || this.existingLanguages.includes(lang)) return;

            this.rows.push({ key: lang, value: '' });
            this.existingLanguages.push(lang);
            this.activeLanguage = lang;
            this.showAddMenu = false;
            this.selectedLanguageToAdd = '';
        },

        removeLanguage(lang) {
            this.rows = this.rows.filter(row => row.key !== lang);
            this.existingLanguages = this.existingLanguages.filter(l => l !== lang);

            if (this.activeLanguage === lang) {
                this.activeLanguage = this.existingLanguages.length > 0
                    ? this.existingLanguages[0]
                    : '';
            }
        },

        // ── Generic field value sync ──────────────────────────────
        // Scoped to the active language's own [data-lang] wrapper — every
        // declared language renders its own real input, so an unscoped
        // selector would always match the first-declared one instead of
        // whichever tab is actually open.
        syncActiveField() {
            const wrap = this.$el.querySelector(`[data-lang-field] [data-lang='${this.activeLanguage}']`);
            if (! wrap) return;

            const input = wrap.querySelector('textarea, input:not([type=hidden])');
            if (! input) return;

            const row = this.rows.find(r => r.key === this.activeLanguage);
            if (row) row.value = input.value;
        },

        restoreActiveField() {
            const wrap = this.$el.querySelector(`[data-lang-field] [data-lang='${this.activeLanguage}']`);
            if (! wrap) return;

            const input = wrap.querySelector('textarea, input:not([type=hidden])');
            if (! input) return;

            const row = this.rows.find(r => r.key === this.activeLanguage);
            if (row) input.value = row.value;
        },
    }"
    x-id="['translatable-compact']"
    :id="$id('translatable-compact')"
    class="translatable-compact"
    data-show-when-field="{{ $field->getAttribute('data-show-when-field', $field->getNameAttribute()) }}"
>
    <!-- Language badges -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <template x-for="(row, idx) in rows" :key="row.key">
            <button
                type="button"
                @click="switchLanguage(row.key)"
                :class="activeLanguage === row.key
                    ? 'tcomp-badge tcomp-badge--active'
                    : 'tcomp-badge'"
                :title="row.value ? (row.key.toUpperCase() + ': ' + row.value) : row.key.toUpperCase()"
            >
                <span x-text="row.key.toUpperCase()"></span>
                <span
                    x-show="isRemovable"
                    @click.stop="removeLanguage(row.key)"
                    class="tcomp-badge-remove"
                >&times;</span>
            </button>
        </template>

        <button
            type="button"
            @click="showAddMenu = !showAddMenu"
            class="tcomp-add-btn"
            x-show="rows.length < allLanguages.length"
            x-text="showAddMenu ? '{{ __('moonshine::ui.cancel') }}' : '{{ __('moonshine::ui.add') }}'"
        ></button>
    </div>

    <!-- Add language panel -->
    <div
        x-show="showAddMenu"
        @click.away="showAddMenu = false"
        x-cloak
        class="tcomp-add-panel"
    >
        <div class="flex items-center gap-3">
            <select
                x-model="selectedLanguageToAdd"
                class="form-input"
                @change="addLanguage(selectedLanguageToAdd)"
            >
                <option value="">—</option>
                <template x-for="lang in allLanguages" :key="lang">
                    <option
                        :value="lang"
                        x-text="lang.toUpperCase()"
                        :disabled="existingLanguages.includes(lang)"
                    ></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Hidden rows for form submission -->
    <template x-for="(row, idx) in rows" :key="row.key">
        <div>
            <input type="hidden" :name="fieldName + '[' + idx + '][key]'" :value="row.key" />
            <input type="hidden" :name="fieldName + '[' + idx + '][value]'" :value="row.value" />
        </div>
    </template>

    <!-- Active language input card -->
    <div x-show="rows.length > 0 && activeLanguage">
        <div class="tcomp-input-card">
            <div class="tcomp-input-card-body">
                <div class="tcomp-input-wrap{{ $isEditor ? ' tcomp-input-wrap--editor' : '' }}" data-lang-field>
                    @foreach ($renderedFields as $langCode => $html)
                        <div data-lang="{{ $langCode }}" x-show="activeLanguage === '{{ $langCode }}'" x-transition:enter.duration.100ms>
                            {!! $html !!}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Empty state -->
    <div x-show="rows.length === 0" class="tcomp-empty-state">
        <p>@lang('moonshine::ui.notfound')</p>
    </div>
</div>
