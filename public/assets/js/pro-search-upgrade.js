
/*
|--------------------------------------------------------------------------
| BGCalc Professional Search Upgrade
|--------------------------------------------------------------------------
| Upgrade:
| - Debounce search
| - Fast indexed search
| - Tokenized lookup
| - Better UX feedback
| - No-lag filtering
| - Handles large roster better
*/

document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.js-roster-search-shell');

    if (!shell) {
        return;
    }

    const input = shell.querySelector('.js-roster-search-input');
    const clearButton = shell.querySelector('.js-roster-search-clear');
    const meta = shell.querySelector('.js-roster-search-meta');
    const emptyState = document.querySelector('.js-roster-search-empty');

    const rows = Array.from(document.querySelectorAll('[data-roster-row]'));

    if (!rows.length || !input) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Build Search Index
    |--------------------------------------------------------------------------
    */

    const indexedRows = rows.map((row) => {
        const raw = String(row.dataset.searchText || '')
            .toLowerCase()
            .trim();

        return {
            row,
            raw,
            tokens: raw
                .split(/\s+/)
                .map((token) => token.trim())
                .filter(Boolean),
        };
    });

    const totalRows = indexedRows.length;

    /*
    |--------------------------------------------------------------------------
    | Smart Search
    |--------------------------------------------------------------------------
    */

    const normalize = (value) => String(value || '')
        .toLowerCase()
        .replace(/[^\w\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const renderSearch = () => {
        const keyword = normalize(input.value);

        if (keyword === '') {
            indexedRows.forEach(({ row }) => {
                row.hidden = false;
            });

            if (meta) {
                meta.textContent = `${totalRows} team`;
            }

            if (emptyState) {
                emptyState.classList.add('d-none');
            }

            return;
        }

        const terms = keyword.split(' ').filter(Boolean);

        let visibleCount = 0;

        indexedRows.forEach((item) => {
            const matched = terms.every((term) => {
                if (item.raw.includes(term)) {
                    return true;
                }

                return item.tokens.some((token) => token.startsWith(term));
            });

            item.row.hidden = !matched;

            if (matched) {
                visibleCount++;
            }
        });

        if (meta) {
            meta.textContent = visibleCount > 0
                ? `${visibleCount} hasil ditemukan`
                : 'Tidak ada hasil';
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
        }
    };

    /*
    |--------------------------------------------------------------------------
    | Debounce
    |--------------------------------------------------------------------------
    */

    let timer = null;

    const queueRender = () => {
        clearTimeout(timer);

        timer = setTimeout(() => {
            window.requestAnimationFrame(renderSearch);
        }, 120);
    };

    input.addEventListener('input', queueRender);

    /*
    |--------------------------------------------------------------------------
    | Keyboard UX
    |--------------------------------------------------------------------------
    */

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            input.value = '';
            renderSearch();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Clear Button
    |--------------------------------------------------------------------------
    */

    clearButton?.addEventListener('click', () => {
        input.value = '';
        renderSearch();
        input.focus();
    });

    /*
    |--------------------------------------------------------------------------
    | Initial Render
    |--------------------------------------------------------------------------
    */

    renderSearch();
});
