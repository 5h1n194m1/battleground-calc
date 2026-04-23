<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="dashboard-page roster-page-shell">
    <div class="page-header dashboard-page-header">
        <div class="page-title-block">
            <h1 class="h3 mb-1">Teams</h1>
            <p class="page-subtitle mb-0">Nama team, anggota, dan keterangan pot bisa diubah dari satu halaman.</p>
        </div>
    </div>

    <div class="dashboard-card card">
        <div class="dashboard-card-header card-header roster-card-header">
            <h2>Teams</h2>
            <?php if ($rows !== []): ?>
                <div class="roster-toolbar js-roster-search-shell">
                    <div class="roster-search-field">
                        <input type="search" class="form-control form-control-sm js-roster-search-input" placeholder="Cari team atau player..." autocomplete="off">
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm app-btn js-roster-search-clear">Reset</button>
                    <span class="roster-search-meta js-roster-search-meta"><?= esc((string) count($rows)) ?> team</span>
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
                                <th style="width: 22%;">Nama Team</th>
                                <th>Anggota</th>
                                <th style="width: 24%;">Keterangan Pot</th>
                                <th style="width: 180px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): ?>
                                <?php
                                $updateFormId = 'team-roster-update-' . $row['id'];
                                $deleteFormId = 'team-roster-delete-' . $row['id'];
                                $searchText = strtolower(trim(implode(' ', [
                                    (string) ($row['name'] ?? ''),
                                    (string) ($row['member_text'] ?? ''),
                                    (string) ($row['scope_label'] ?? ''),
                                    (string) ($row['pot_name'] ?? ''),
                                    (string) ($row['tournament_name'] ?? ''),
                                ])));
                                ?>
                                <tr data-roster-row data-search-text="<?= esc($searchText) ?>">
                                    <td class="text-center fw-semibold"><?= esc((string) ($index + 1)) ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            name="name"
                                            form="<?= esc($updateFormId) ?>"
                                            class="form-control form-control-sm roster-input"
                                            value="<?= esc($row['name']) ?>"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        >
                                    </td>
                                    <td>
                                        <textarea
                                            name="member_text"
                                            form="<?= esc($updateFormId) ?>"
                                            class="form-control form-control-sm roster-textarea"
                                            rows="2"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        ><?= esc($row['member_text'] === '-' ? '' : $row['member_text']) ?></textarea>
                                        <div class="form-text roster-help">Pisahkan anggota dengan koma atau baris baru.</div>
                                    </td>
                                    <td>
                                        <select
                                            name="pot_id"
                                            form="<?= esc($updateFormId) ?>"
                                            class="form-select form-select-sm roster-pot-select"
                                            <?= $row['is_locked'] ? 'disabled' : '' ?>
                                        >
                                            <option value="">Belum Masuk Pot</option>
                                            <?php foreach ($potOptions as $potOption): ?>
                                                <option value="<?= esc((string) $potOption['id']) ?>" <?= (int) ($row['pot_id'] ?? 0) === (int) $potOption['id'] ? 'selected' : '' ?>>
                                                    <?= esc($potOption['label']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text roster-help">
                                            <?= esc($row['scope_label'] !== '' ? $row['scope_label'] : 'Belum Masuk Pot') ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form id="<?= esc($updateFormId) ?>" action="<?= site_url('teams/update/' . $row['id']) ?>" method="post" class="d-none">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="manager_context" value="import">
                                            <input type="hidden" name="redirect_to" value="<?= esc(current_url()) ?>">
                                        </form>
                                        <form id="<?= esc($deleteFormId) ?>" action="<?= site_url('teams/delete/' . $row['id']) ?>" method="post" class="d-none">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="redirect_to" value="<?= esc(current_url()) ?>">
                                        </form>
                                        <div class="dashboard-action-group roster-action-group">
                                            <button
                                                type="submit"
                                                form="<?= esc($updateFormId) ?>"
                                                class="btn btn-sm btn-outline-primary app-btn"
                                                <?= $row['is_locked'] ? 'disabled' : '' ?>
                                            >
                                                Simpan
                                            </button>
                                            <button
                                                type="submit"
                                                form="<?= esc($deleteFormId) ?>"
                                                class="btn btn-sm btn-outline-danger app-btn"
                                                onclick="return confirm('Hapus team ini langsung dari sistem?');"
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
<script>
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
    const total = rows.length;

    const render = () => {
        const keyword = String(input?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {
            const haystack = String(row.getAttribute('data-search-text') || '').toLowerCase();
            const matched = keyword === '' || haystack.includes(keyword);
            row.hidden = !matched;
            if (matched) {
                visibleCount += 1;
            }
        });

        if (meta) {
            meta.textContent = keyword === '' ? `${total} team` : `${visibleCount} hasil`;
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount !== 0);
        }
    };

    input?.addEventListener('input', render);
    clearButton?.addEventListener('click', () => {
        if (!input) {
            return;
        }
        input.value = '';
        render();
        input.focus();
    });

    render();
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
