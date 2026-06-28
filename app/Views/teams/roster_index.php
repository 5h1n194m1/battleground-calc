<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="dashboard-page roster-page-shell">
    <div class="page-header dashboard-page-header">
        <div class="page-title-block">
            <h1 class="h3 mb-1">Daftar Team</h1>
            <p class="page-subtitle mb-0">Semua team sistem, termasuk yang belum punya tournament, bisa dirapikan dari satu halaman.</p>
        </div>
        <?php if (($tournaments ?? []) !== []): ?>
            <div class="d-flex gap-2 align-items-end flex-wrap">
                <div class="roster-header-filter">
                    <label class="form-label mb-1">Tournament</label>
                    <select class="form-select form-select-sm js-roster-tournament-filter">
                        <option value="all" <?= (string) ($selectedTournamentKey ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Team</option>
                        <option value="unassigned" <?= (string) ($selectedTournamentKey ?? 'all') === 'unassigned' ? 'selected' : '' ?>>Tim Tanpa Pot</option>
                        <option value="none" <?= (string) ($selectedTournamentKey ?? 'all') === 'none' ? 'selected' : '' ?>>Belum Ada Tournament</option>
                        <?php foreach ($tournaments as $tournament): ?>
                            <option value="<?= esc((string) $tournament['id']) ?>" <?= (string) ($selectedTournamentKey ?? 'all') === (string) $tournament['id'] ? 'selected' : '' ?>>
                                <?= esc($tournament['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ((int) ($selectedTournamentId ?? 0) > 0): ?>
                    <a href="<?= site_url('teams/export-template?tournament_id=' . (int) $selectedTournamentId) ?>" class="btn btn-outline-primary btn-sm app-btn">Export CSV</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-card card">
        <?php if ($rows !== []): ?>
            <form id="team-roster-bulk-form" action="<?= site_url('teams/bulk-update') ?>" method="post" class="roster-bulk-form">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect_to" value="<?= esc(current_url() . (! empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                <input type="hidden" name="manager_context" value="import">
            </form>
        <?php endif; ?>
        <div class="dashboard-card-header card-header roster-card-header">
            <h2>Teams</h2>
            <?php if ($rows !== []): ?>
                <div class="roster-toolbar js-roster-search-shell">
                    <div class="roster-search-field">
                        <input type="search" class="form-control form-control-sm js-roster-search-input" placeholder="Cari team atau player..." autocomplete="off">
                    </div>
                    <button type="submit" form="team-roster-bulk-form" class="btn btn-primary btn-sm app-btn">Simpan Semua</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-roster-search-clear">Reset</button>
                    <span class="roster-search-meta js-roster-search-meta"><?= esc((string) count($rows)) ?> team</span>
                    <span class="roster-save-meta js-roster-save-meta d-none"></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card-body card-body p-0">
            <?php if ($rows === []): ?>
                <div class="dashboard-empty">Belum ada team atau player yang tersimpan.</div>
            <?php else: ?>
                <div class="table-responsive roster-table-wrap">
                    <table class="table table-hover align-middle dashboard-table roster-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 68px;" class="text-center">No</th>
                                <th style="width: 20%;">Nama Team</th>
                                <th>Anggota</th>
                                <th style="width: 18%;">Tournament</th>
                                <th style="width: 22%;">Pot</th>
                                <th style="width: 180px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $deleteFormId = 'team-roster-delete-' . $row['id'];
                                $searchText = strtolower(trim(implode(' ', [
                                    (string) ($row['name'] ?? ''),
                                    (string) ($row['member_text'] ?? ''),
                                    (string) ($row['scope_label'] ?? ''),
                                    (string) ($row['pot_name'] ?? ''),
                                    (string) ($row['tournament_name'] ?? ''),
                                ])));
                                ?>
                                <tr
                                    data-roster-row
                                    data-search-text="<?= esc($searchText) ?>"
                                    data-original-name="<?= esc($row['name']) ?>"
                                    data-original-members="<?= esc($row['member_text'] === '-' ? '' : implode(', ', $row['members'] ?? [])) ?>"
                                    data-original-tournament-id="<?= esc((string) ($row['tournament_id'] ?? '')) ?>"
                                    data-original-pot-id="<?= esc((string) ($row['pot_id'] ?? '')) ?>"
                                >
                                    <td class="text-center fw-semibold"><?= esc((string) ($index + 1)) ?></td>
                                    <td>
                                        <input type="hidden" name="teams[<?= esc((string) $row['id']) ?>][__changed]" form="team-roster-bulk-form" value="0" class="js-roster-row-changed">
                                        <input
                                            type="text"
                                            name="teams[<?= esc((string) $row['id']) ?>][name]"
                                            form="team-roster-bulk-form"
                                            class="form-control form-control-sm roster-input"
                                            value="<?= esc($row['name']) ?>"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        >
                                    </td>
                                    <td>
                                        <textarea
                                            name="teams[<?= esc((string) $row['id']) ?>][member_text]"
                                            form="team-roster-bulk-form"
                                            class="form-control form-control-sm roster-textarea"
                                            rows="2"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        ><?= esc($row['member_text'] === '-' ? '' : $row['member_text']) ?></textarea>
                                        <div class="form-text roster-help">Pisahkan anggota dengan koma atau baris baru.</div>
                                    </td>
                                    <td>
                                        <select
                                            name="teams[<?= esc((string) $row['id']) ?>][tournament_id]"
                                            form="team-roster-bulk-form"
                                            class="form-select form-select-sm roster-tournament-select"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        >
                                            <option value="">Belum Ada Tournament</option>
                                            <?php foreach ($tournaments as $tournament): ?>
                                                <option value="<?= esc((string) $tournament['id']) ?>" <?= (int) ($row['tournament_id'] ?? 0) === (int) $tournament['id'] ? 'selected' : '' ?>>
                                                    <?= esc($tournament['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text roster-help">
                                            <?= esc($row['tournament_name'] !== '' ? $row['tournament_name'] : 'Belum Ada Tournament') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <select
                                            name="teams[<?= esc((string) $row['id']) ?>][pot_id]"
                                            form="team-roster-bulk-form"
                                            class="form-select form-select-sm roster-pot-select"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        >
                                            <option value="">Belum Masuk Pot</option>
                                            <?php foreach ($potOptions as $potOption): ?>
                                                <option
                                                    value="<?= esc((string) $potOption['id']) ?>"
                                                    data-tournament-id="<?= esc((string) $potOption['tournament_id']) ?>"
                                                    <?= (int) ($row['pot_id'] ?? 0) === (int) $potOption['id'] ? 'selected' : '' ?>
                                                >
                                                    <?= esc($potOption['label']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text roster-help">
                                            <?= esc($row['pot_name'] !== '' ? $row['pot_name'] : 'Belum Masuk Pot') ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form id="<?= esc($deleteFormId) ?>" action="<?= site_url('teams/delete/' . $row['id']) ?>" method="post" class="d-none">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="redirect_to" value="<?= esc(current_url() . (! empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
                                        </form>
                                        <div class="dashboard-action-group roster-action-group">
                                            <button
                                                type="submit"
                                                form="team-roster-bulk-form"
                                                name="save_single_team_id"
                                                value="<?= esc((string) $row['id']) ?>"
                                                class="btn btn-sm btn-outline-primary app-btn"
                                                <?= $row['is_locked'] ? 'disabled' : '' ?>
                                            >
                                                Simpan
                                            </button>
                                            <button
                                                type="submit"
                                                form="<?= esc($deleteFormId) ?>"
                                                class="btn btn-sm btn-outline-danger app-btn"
                                                data-confirm-click="Hapus team ini langsung dari sistem?"
                                                <?= $row['is_locked'] ? 'disabled' : '' ?>
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                        <?php if ($row['is_locked']): ?>
                                            <div class="form-text roster-help text-end mb-0">Tournament selesai, team dikunci.</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="dashboard-empty roster-search-empty d-none js-roster-search-empty">Tidak ada hasil.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($rows !== []): ?>
<script {csp-script-nonce}>
document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.js-roster-search-shell');
    const tournamentFilter = document.querySelector('.js-roster-tournament-filter');
    const bulkForm = document.getElementById('team-roster-bulk-form');
    const url = new URL(window.location.href);

    tournamentFilter?.addEventListener('change', () => {
        const value = String(tournamentFilter.value || '').trim();
        if (value === '') {
            url.searchParams.delete('tournament_id');
        } else {
            url.searchParams.set('tournament_id', value);
        }
        window.location.href = url.toString();
    });

    if (!shell) {
        return;
    }

    const input = shell.querySelector('.js-roster-search-input');
    const clearButton = shell.querySelector('.js-roster-search-clear');
    const meta = shell.querySelector('.js-roster-search-meta');
    const saveMeta = shell.querySelector('.js-roster-save-meta');
    const emptyState = document.querySelector('.js-roster-search-empty');
    const rows = Array.from(document.querySelectorAll('[data-roster-row]'));
    const total = rows.length;
    let submitMode = 'bulk';

    const syncPotOptions = (row) => {
        const tournament = row.querySelector('select[name$="[tournament_id]"]');
        const pot = row.querySelector('select[name$="[pot_id]"]');
        if (!tournament || !pot) {
            return;
        }

        const tournamentId = String(tournament.value || '').trim();
        let selectedStillVisible = String(pot.value || '').trim() === '';

        Array.from(pot.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const optionTournamentId = String(option.dataset.tournamentId || '').trim();
            const matchesTournament = tournamentId !== '' && optionTournamentId === tournamentId;
            option.hidden = !matchesTournament;
            option.disabled = !matchesTournament;

            if (matchesTournament && String(option.value || '').trim() === String(pot.value || '').trim()) {
                selectedStillVisible = true;
            }
        });

        if (!selectedStillVisible) {
            pot.value = '';
        }
    };

    const normalizeMemberText = (value) => String(value || '')
        .replace(/;/g, ',')
        .replace(/\r\n?/g, '\n')
        .split(/\n|,/) 
        .map((chunk) => chunk.trim().replace(/\s+/g, ' '))
        .filter(Boolean)
        .slice(0, 6)
        .join(', ');

    const rowHasChanges = (row) => {
        const teamName = row.querySelector('input[name$="[name]"]');
        const memberText = row.querySelector('textarea[name$="[member_text]"]');
        const tournament = row.querySelector('select[name$="[tournament_id]"]');
        const pot = row.querySelector('select[name$="[pot_id]"]');

        return String(teamName?.value || '').trim() !== String(row.dataset.originalName || '').trim()
            || normalizeMemberText(memberText?.value || '') !== normalizeMemberText(row.dataset.originalMembers || '')
            || String(tournament?.value || '').trim() !== String(row.dataset.originalTournamentId || '').trim()
            || String(pot?.value || '').trim() !== String(row.dataset.originalPotId || '').trim();
    };

    const prepareBulkSubmission = () => {
        let changedCount = 0;

        rows.forEach((row) => {
            const changed = rowHasChanges(row);
            const changedField = row.querySelector('.js-roster-row-changed');
            if (changedField) {
                changedField.value = changed ? '1' : '0';
            }

            row.querySelectorAll('input, textarea, select').forEach((field) => {
                if (field.form !== bulkForm || field.disabled || field.classList.contains('js-roster-row-changed')) {
                    return;
                }

                field.disabled = !changed;
            });

            if (changed) {
                changedCount += 1;
            }
        });

        if (saveMeta) {
            saveMeta.textContent = changedCount > 0 ? `${changedCount} team siap disimpan` : 'Belum ada perubahan';
            saveMeta.classList.remove('d-none');
        }
    };

    bulkForm?.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.addEventListener('click', () => {
            submitMode = button.name === 'save_single_team_id' ? 'single' : 'bulk';
        });
    });

    /*
    |--------------------------------------------------------------------------
    | PROFESSIONAL SEARCH ENGINE
    |--------------------------------------------------------------------------
    */

    const normalize = (value) => String(value || '')
        .toLowerCase()
        .replace(/[^\w\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const indexedRows = rows.map((row) => {
        const raw = normalize(
            String(row.getAttribute('data-search-text') || '')
        );

        return {
            row,
            raw,
            tokens: raw.split(' ').filter(Boolean),
        };
    });

    let debounceTimer = null;

    const render = () => {

        const keyword = normalize(input?.value || '');

        if (keyword === '') {

            indexedRows.forEach(({ row }) => {
                row.hidden = false;
            });

            if (meta) {
                meta.textContent = `${total} team`;
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

                return item.tokens.some((token) =>
                    token.startsWith(term)
                );
            });

            item.row.hidden = !matched;

            if (matched) {
                visibleCount++;
            }
        });

        if (meta) {
            meta.textContent =
                visibleCount > 0
                    ? `${visibleCount} hasil ditemukan`
                    : 'Tidak ada hasil';
        }

        if (emptyState) {
            emptyState.classList.toggle(
                'd-none',
                visibleCount > 0
            );
        }
    };

    input?.addEventListener('input', () => {

        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            requestAnimationFrame(render);
        }, 120);
    });
    clearButton?.addEventListener('click', () => {
        if (!input) {
            return;
        }
        input.value = '';
        render();
        input.focus();
    });

    bulkForm?.addEventListener('submit', () => {
        if (submitMode === 'bulk') {
            prepareBulkSubmission();
        }

        if (window.BGApp?.ensureFormCsrf) {
            window.BGApp.ensureFormCsrf(bulkForm);
        }
    });

    rows.forEach((row) => {
        const tournament = row.querySelector('select[name$="[tournament_id]"]');
        syncPotOptions(row);
        tournament?.addEventListener('change', () => {
            syncPotOptions(row);
        });
    });

    render();
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
