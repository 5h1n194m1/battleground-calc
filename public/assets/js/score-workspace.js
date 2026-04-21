(function () {
    const app = window.BGApp || {};

    const qs = (selector, scope = document) => scope.querySelector(selector);
    const qsa = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char] || char));

    const showFlash = (message, type = 'success') => {
        if (message && typeof app.showFlash === 'function') {
            app.showFlash(message, type);
        }
    };

    const updateCsrf = (payload) => {
        if (!payload || typeof app.updateCsrf !== 'function') {
            return;
        }

        if (payload.csrfTokenName && payload.csrfHash) {
            app.updateCsrf(payload.csrfTokenName, payload.csrfHash);
        }
    };

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
            ...options,
        });

        const contentType = response.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');
        const payload = isJson ? await response.json() : null;

        updateCsrf(payload);

        if (!response.ok) {
            const message = payload?.message || 'Terjadi kesalahan saat memproses request.';
            throw new Error(message);
        }

        return payload || { status: 'success', message: 'Berhasil diproses.' };
    };

    const ensureFormDataCsrf = (formData) => {
        const tokenName = app.csrfTokenName || 'csrf_test_name';
        const tokenHash = app.csrfHash || '';

        try {
            formData.delete(tokenName);
        } catch (error) {
            // ignore
        }

        formData.append(tokenName, tokenHash);
    };

    const clearPendingScoreSaves = () => {
        qsa('.js-score-bulk-form').forEach((form) => {
            if (form._saveTimer) {
                clearTimeout(form._saveTimer);
                form._saveTimer = null;
            }
        });
    };

    const getPlacement = (placementMap, rank) => {
        if (!Number.isInteger(rank)) {
            return 0;
        }

        return Number(placementMap[String(rank)] ?? placementMap[rank] ?? 0);
    };

    const updateRowTotal = (row, placementMap) => {
        let total = 0;

        qsa('[data-col-role="rank"]', row).forEach((rankCell) => {
            const gameNo = rankCell.dataset.gameCol;
            const rankInput = qs('input', rankCell);
            const killInput = qs('[data-col-role="kill"][data-game-col="' + gameNo + '"] input', row);
            const placementCell = qs('[data-col-role="placement"][data-game-col="' + gameNo + '"]', row);

            const rank = Number.parseInt(rankInput?.value || '', 10);
            const kill = Number.parseInt(killInput?.value || '', 10);
            const placement = getPlacement(placementMap, rank);

            if (placementCell) {
                placementCell.textContent = String(placement);
            }

            total += placement + (Number.isNaN(kill) ? 0 : kill);
        });

        const totalCell = qs('.total-cell', row);
        if (totalCell) {
            totalCell.textContent = String(total);
        }
    };

    const updateAllRows = (table, placementMap) => {
        qsa('tbody tr[data-team-row]', table).forEach((row) => updateRowTotal(row, placementMap));
    };

    const updateCompactVars = (form) => {
        const host = form.closest('.pot-module-card') || form;
        const shell = qs('.score-table-shell', form);
        const gameCount = Number.parseInt(qs('.js-game-count', form)?.value || '1', 10);
        const safeGameCount = Number.isNaN(gameCount) ? 1 : Math.max(1, gameCount);
        const allowSideScroll = safeGameCount > 8;
        const teamWidth = allowSideScroll ? 136 : Math.max(136, 300 - ((safeGameCount - 1) * 24));
        const scoreWidth = allowSideScroll ? 40 : Math.max(40, 52 - ((safeGameCount - 1) * 2));
        const gameColWidth = allowSideScroll ? 40 : Math.max(40, 56 - ((safeGameCount - 1) * 2));
        const placementWidth = allowSideScroll ? 36 : Math.max(36, 46 - ((safeGameCount - 1) * 2));

        host.style.setProperty('--team-col-width', String(teamWidth) + 'px');
        host.style.setProperty('--score-col-width', String(scoreWidth) + 'px');
        host.style.setProperty('--game-col-width', String(gameColWidth) + 'px');
        host.style.setProperty('--placement-col-width', String(placementWidth) + 'px');

        if (shell) {
            shell.classList.toggle('is-scrollable', allowSideScroll);
        }
    };

    const getStatusEl = (form) => {
        const card = form.closest('.pot-module-card');
        return qs('.auto-save-status', card || document);
    };

    const setStatus = (form, state, message) => {
        const statusEl = getStatusEl(form);
        if (!statusEl) {
            return;
        }

        statusEl.dataset.state = state;
        statusEl.textContent = message;
    };

    const hasTeamRows = (form) => Boolean(qs('tr[data-team-row]', form));

    const savePotMeta = async (form) => {
        const url = form.dataset.potUpdateUrl;
        const potId = form.dataset.potId;
        const formData = new FormData();

        ensureFormDataCsrf(formData);
        formData.append('tournament_id', qs('input[name="tournament_id"]', form)?.value || '');
        formData.append('name', qs('input[name="pot_name"]', form)?.value || '');
        formData.append('sort_order', qs('input[name="pot_sort_order"]', form)?.value || '');
        formData.append('redirect_to', qs('input[name="redirect_to"]', form)?.value || window.location.href);

        if (!url || !potId) {
            return { status: 'success', message: 'Perubahan disimpan.' };
        }

        return request(url, {
            method: 'POST',
            body: formData,
        });
    };

    const saveBulk = async (form) => {
        const formData = new FormData(form);
        ensureFormDataCsrf(formData);

        return request(form.action, {
            method: 'POST',
            body: formData,
        });
    };

    const scheduleSave = (form, saver) => {
        if (form.dataset.canManage !== '1') {
            return;
        }

        clearTimeout(form._saveTimer);
        setStatus(form, 'dirty', '');
        form._saveTimer = window.setTimeout(saver, 450);
    };

    const createHeadCell = (gameNo) => {
        const th = document.createElement('th');
        th.colSpan = 3;
        th.className = 'text-center game-group-head';
        th.dataset.gameGroup = String(gameNo);
        th.textContent = 'Game ' + gameNo;
        return th;
    };

    const createSubCell = (gameNo, role, label) => {
        const th = document.createElement('th');
        th.className = 'text-center game-subhead';
        th.dataset.gameCol = String(gameNo);
        th.dataset.colRole = role;
        th.textContent = label;
        return th;
    };

    const createInputCell = (teamId, gameNo, role) => {
        const td = document.createElement('td');
        td.dataset.gameCol = String(gameNo);
        td.dataset.colRole = role;

        if (role === 'placement') {
            td.className = 'score-placement-cell';
            td.textContent = '0';
            return td;
        }

        td.className = 'game-cell';

        const input = document.createElement('input');
        input.type = 'number';
        input.min = role === 'rank' ? '1' : '0';
        if (role === 'rank') {
            input.max = '12';
        }
        input.className = 'form-control form-control-sm score-input js-score-input';
        input.name = 'scores[' + teamId + '][' + gameNo + '][' + (role === 'rank' ? 'rank_no' : 'kill_point') + ']';

        td.appendChild(input);
        return td;
    };

    const buildPotLookupIndex = (potCard) => {
        const rows = qsa('tr[data-team-row]', potCard);
        return rows.map((row) => {
            const teamNameInput =
                qs('.team-name-input', row) ||
                qs('.js-team-input', row) ||
                qs('input[name^="team_names["]', row);

            const teamName = (teamNameInput?.value || '').trim();
            const membersRaw = (row.dataset.searchPlayers || '').trim();
            const members = membersRaw
                .split(/\r?\n|,/)
                .map((item) => item.trim())
                .filter(Boolean);
            const label = (row.dataset.searchLabel || '').trim() || [teamName, members.join(', ')].filter(Boolean).join(', ');

            return {
                row,
                teamName,
                members,
                label,
            };
        });
    };

    const fillLookupDatalist = (listEl, items) => {
        if (!listEl) {
            return;
        }

        listEl.innerHTML = '';

        const seen = new Set();
        items.forEach((item) => {
            const value = (item.label || '').trim();
            if (!value || seen.has(value)) {
                return;
            }

            seen.add(value);
            const option = document.createElement('option');
            option.value = value;
            listEl.appendChild(option);
        });
    };

    const maybeFocusMatchedRow = (potCard, value) => {
        const keyword = (value || '').trim().toLowerCase();
        if (!keyword) {
            return;
        }

        const match = buildPotLookupIndex(potCard).find((item) => (item.label || '').trim().toLowerCase() === keyword);
        if (!match?.row) {
            return;
        }

        match.row.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'nearest',
        });
    };

    const bindLookupToolbars = () => {
        qsa('.pot-module-card').forEach((potCard) => {
            const teamInput =
                qs('.js-team-lookup', potCard) ||
                qs('.js-lookup-team', potCard) ||
                qs('[data-lookup-input="team"]', potCard);

            const playerInput =
                qs('.js-player-lookup', potCard) ||
                qs('.js-lookup-player', potCard) ||
                qs('[data-lookup-input="player"]', potCard);

            const teamList =
                qs('.js-team-lookup-list', potCard) ||
                (teamInput?.getAttribute('list') ? document.getElementById(teamInput.getAttribute('list')) : null);

            const playerList =
                qs('.js-player-lookup-list', potCard) ||
                (playerInput?.getAttribute('list') ? document.getElementById(playerInput.getAttribute('list')) : null);

            const bindOne = (input, type, listEl) => {
                if (!input || input.dataset.lookupBound === '1') {
                    return;
                }

                input.dataset.lookupBound = '1';

                const run = () => {
                    const keyword = (input.value || '').trim().toLowerCase();
                    const index = buildPotLookupIndex(potCard);

                    if (!keyword) {
                        fillLookupDatalist(listEl, index);
                        return;
                    }

                    if (type === 'team') {
                        const matches = index.filter((item) =>
                            item.teamName.toLowerCase().includes(keyword)
                        );
                        fillLookupDatalist(listEl, matches);
                    } else {
                        const matches = index.filter((item) =>
                            item.members.some((member) => member.toLowerCase().includes(keyword))
                        );
                        fillLookupDatalist(listEl, matches);
                    }
                };

                input.addEventListener('input', run);
                input.addEventListener('focus', run);
                input.addEventListener('change', () => {
                    maybeFocusMatchedRow(potCard, input.value || '');
                });
            };

            bindOne(teamInput, 'team', teamList);
            bindOne(playerInput, 'player', playerList);
        });
    };

const bindTeamManagerWorkspace = () => {
    qsa('.js-team-manager-workspace').forEach((workspace) => {
        if (workspace.dataset.managerBound === '1') {
            return;
        }

        workspace.dataset.managerBound = '1';

        const endpoint = workspace.dataset.endpoint || '';
        const createUrl = workspace.dataset.createUrl || '';
        const updateUrlBase = workspace.dataset.updateUrlBase || '';
        const deleteUrlBase = workspace.dataset.deleteUrlBase || '';
        const canManage = workspace.dataset.canManage === '1';
        const managerContext = workspace.dataset.context || 'import';
        const currentTournamentId = Number.parseInt(workspace.dataset.currentTournamentId || '0', 10);
        const currentPotId = Number.parseInt(workspace.dataset.currentPotId || '0', 10);

        const tournamentSelect = qs('.js-manager-tournament', workspace);
        const potSelect = qs('.js-manager-pot', workspace);
        const listEl = qs('.js-manager-list', workspace);
        const countEl = qs('.js-manager-count', workspace);
        const currentScopeEl = qs('.js-manager-current-scope', workspace);
        const teamIdInput = qs('.js-manager-team-id', workspace);
        const teamNameInput = qs('.js-manager-team-name', workspace);
        const memberTextInput = qs('.js-manager-member-text', workspace);
        const searchInput = qs('.js-manager-search', workspace);
        const resetButton = qs('.js-manager-reset', workspace);
        const createButton = qs('.js-manager-create', workspace);
        const updateButton = qs('.js-manager-update', workspace);
        const deleteButton = qs('.js-manager-delete', workspace);

        const state = {
            teams: [],
            activeTeamId: 0,
            query: '',
        };

        const setLoading = (loading) => {
            workspace.dataset.loading = loading ? '1' : '0';

            [tournamentSelect, potSelect, teamNameInput, memberTextInput, searchInput, createButton, updateButton, deleteButton, resetButton]
                .filter(Boolean)
                .forEach((el) => {
                    if (!canManage && (el === teamNameInput || el === memberTextInput || el === createButton || el === updateButton || el === deleteButton || el === resetButton)) {
                        return;
                    }

                    if (loading) {
                        el.setAttribute('disabled', 'disabled');
                    } else if (canManage || (el !== teamNameInput && el !== memberTextInput && el !== createButton && el !== updateButton && el !== deleteButton && el !== resetButton)) {
                        el.removeAttribute('disabled');
                    }
                });
        };

        const fillPotOptions = (pots, selectedPotId) => {
            if (!potSelect) {
                return;
            }

            potSelect.innerHTML = '<option value="">Pilih pot</option>';
            pots.forEach((pot) => {
                const option = document.createElement('option');
                option.value = String(pot.id);
                option.textContent = pot.name;
                option.selected = Number(pot.id) === Number(selectedPotId);
                potSelect.appendChild(option);
            });
        };

        const getFilteredTeams = () => {
            const keyword = (state.query || '').trim().toLowerCase();
            if (!keyword) {
                return state.teams;
            }

            return state.teams.filter((team) => {
                const haystack = [team.name, team.member_text, (team.members || []).join(', ')].join(' ').toLowerCase();
                return haystack.includes(keyword);
            });
        };

        const resetEditor = () => {
            state.activeTeamId = 0;
            if (teamIdInput) teamIdInput.value = '';
            if (teamNameInput) teamNameInput.value = '';
            if (memberTextInput) memberTextInput.value = '';
            qsa('.team-manager-list-item', workspace).forEach((item) => {
                item.classList.toggle('is-active', false);
            });
        };

        const fillEditor = (team) => {
            if (!team) {
                resetEditor();
                return;
            }

            state.activeTeamId = Number(team.id);
            if (teamIdInput) teamIdInput.value = String(team.id);
            if (teamNameInput) teamNameInput.value = team.name || '';
            if (memberTextInput) memberTextInput.value = team.member_text || '';

            qsa('.team-manager-list-item', workspace).forEach((item) => {
                item.classList.toggle('is-active', Number(item.dataset.teamId || '0') === Number(team.id));
            });
        };

        const renderList = () => {
            if (!listEl) {
                return;
            }

            const filteredTeams = getFilteredTeams();

            if (countEl) {
                countEl.textContent = state.teams.length === filteredTeams.length
                    ? state.teams.length + ' team'
                    : filteredTeams.length + ' / ' + state.teams.length + ' team';
            }

            if (currentScopeEl) {
                const isCurrentScope = Number.parseInt(tournamentSelect?.value || '0', 10) === currentTournamentId
                    && Number.parseInt(potSelect?.value || '0', 10) === currentPotId;
                currentScopeEl.textContent = isCurrentScope ? 'Pot yang sedang ditampilkan' : 'Pot lain / mode global';
            }

            if (!filteredTeams.length) {
                listEl.innerHTML = '<div class="team-manager-empty">Tidak ada team yang cocok di pot ini.</div>';
                if (!state.teams.length) {
                    resetEditor();
                }
                return;
            }

            listEl.innerHTML = filteredTeams.map((team) => {
                const preview = team.member_text || 'Belum ada member';

                return `
                    <button type="button" class="team-manager-list-item${Number(team.id) === Number(state.activeTeamId) ? ' is-active' : ''}" data-team-id="${team.id}">
                        <span class="team-manager-list-top">
                            <span class="team-manager-list-name">${escapeHtml(team.name || '-')}</span>
                            <span class="team-manager-list-order">#${Number(team.sort_order || 0)}</span>
                        </span>
                        <span class="team-manager-list-meta">
                            <span>${Number(team.member_count || 0)} member</span>
                            <span>${Number(team.games_played || 0)} game</span>
                            <span>${Number(team.total_score || 0)} pts</span>
                        </span>
                        <span class="team-manager-list-preview">${escapeHtml(preview)}</span>
                    </button>
                `;
            }).join('');

            qsa('.team-manager-list-item', listEl).forEach((button) => {
                button.addEventListener('click', () => {
                    const team = state.teams.find((item) => Number(item.id) === Number(button.dataset.teamId || '0'));
                    fillEditor(team || null);
                });
            });
        };

        const syncScoreViews = () => {
            bindLookupToolbars();
            if (typeof window.initScoreWorkspace === 'function') {
                window.initScoreWorkspace();
            }
        };

        const loadData = async ({ keepSelection = false } = {}) => {
            if (!endpoint) {
                return;
            }

            const previousTeamId = keepSelection ? state.activeTeamId : 0;
            const params = new URLSearchParams();

            if (tournamentSelect?.value) {
                params.set('tournament_id', tournamentSelect.value);
            }

            if (potSelect?.value) {
                params.set('pot_id', potSelect.value);
            }

            setLoading(true);

            try {
                const payload = await request(endpoint + '?' + params.toString(), {
                    method: 'GET',
                });

                updateCsrf(payload);

                if (tournamentSelect && payload.selectedTournamentId) {
                    tournamentSelect.value = String(payload.selectedTournamentId);
                }

                fillPotOptions(payload.pots || [], payload.selectedPotId || 0);
                state.teams = payload.teams || [];
                state.activeTeamId = previousTeamId;
                renderList();

                const selectedTeam = state.teams.find((team) => Number(team.id) === Number(previousTeamId));
                fillEditor(selectedTeam || null);
            } catch (error) {
                if (listEl) {
                    listEl.innerHTML = '<div class="team-manager-empty">Gagal memuat team manager.</div>';
                }
            } finally {
                setLoading(false);
            }
        };

        const submitManagerAction = async (mode) => {
            const selectedTournamentId = Number.parseInt(tournamentSelect?.value || '', 10);
            const potId = Number.parseInt(potSelect?.value || '', 10);
            const teamId = Number.parseInt(teamIdInput?.value || '', 10);
            const teamName = (teamNameInput?.value || '').trim();
            const memberText = (memberTextInput?.value || '').trim();

            if (!potId) {
                showFlash('Pilih pot terlebih dahulu.', 'warning');
                return;
            }

            if ((mode === 'update' || mode === 'delete') && !teamId) {
                showFlash('Pilih team dari daftar terlebih dahulu.', 'warning');
                return;
            }

            if ((mode === 'create' || mode === 'update') && !teamName) {
                showFlash('Nama team wajib diisi.', 'warning');
                teamNameInput?.focus();
                return;
            }

            let url = createUrl;
            if (mode === 'update') {
                url = updateUrlBase.replace(/\/$/, '') + '/' + teamId;
            } else if (mode === 'delete') {
                url = deleteUrlBase.replace(/\/$/, '') + '/' + teamId;
            }

            const formData = new FormData();
            ensureFormDataCsrf(formData);
            formData.append('pot_id', String(potId));
            formData.append('name', teamName);
            formData.append('member_text', memberText);
            formData.append('redirect_to', window.location.href);

            setLoading(true);

            try {
                await request(url, {
                    method: 'POST',
                    body: formData,
                });

                showFlash(
                    mode === 'create'
                        ? 'Team berhasil ditambahkan.'
                        : mode === 'update'
                            ? 'Team berhasil diperbarui.'
                            : 'Team berhasil dihapus.',
                    'success'
                );

                const touchesCurrentScorePage = managerContext === 'score'
                    && selectedTournamentId === currentTournamentId
                    && potId === currentPotId;

                if (touchesCurrentScorePage) {
                    window.setTimeout(() => {
                        window.location.reload();
                    }, 120);
                    return;
                }

                resetEditor();
                await loadData();
                syncScoreViews();
            } catch (error) {
                showFlash(error.message || 'Gagal memproses team manager.', 'error');
            } finally {
                setLoading(false);
            }
        };

        tournamentSelect?.addEventListener('change', async () => {
            resetEditor();
            if (potSelect) {
                potSelect.value = '';
            }
            await loadData();
        });

        potSelect?.addEventListener('change', async () => {
            resetEditor();
            await loadData();
        });

        searchInput?.addEventListener('input', () => {
            state.query = searchInput.value || '';
            renderList();
        });

        resetButton?.addEventListener('click', () => {
            resetEditor();
            teamNameInput?.focus();
        });

        createButton?.addEventListener('click', async () => {
            await submitManagerAction('create');
        });

        updateButton?.addEventListener('click', async () => {
            await submitManagerAction('update');
        });

        deleteButton?.addEventListener('click', async () => {
            const ok = await openDeleteConfirmPopup({
                title: 'Hapus Team',
                message: 'Yakin ingin menghapus team ini beserta score yang terkait?',
                submitText: 'Hapus',
            });

            if (!ok) {
                return;
            }

            await submitManagerAction('delete');
        });

        loadData();
    });
};

    const bindQuickAddTeamForms = () => {
        qsa('form[action*="teams/store"]').forEach((form) => {
            if (form.dataset.quickTeamBound === '1') {
                return;
            }

            form.dataset.quickTeamBound = '1';

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (form.dataset.submitting === '1') {
                    return;
                }

                const submitButton = event.submitter || qs('button[type="submit"]', form);

                form.dataset.submitting = '1';
                clearPendingScoreSaves();

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.dataset.originalText = submitButton.textContent;
                    submitButton.textContent = '...';
                }

                try {
                    const formData = new FormData(form);
                    ensureFormDataCsrf(formData);

                    const payload = await request(form.action, {
                        method: 'POST',
                        body: formData,
                    });

                    window.setTimeout(() => {
                        window.location.href = payload.redirectUrl || window.location.href;
                    }, 100);
                } catch (error) {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = submitButton.dataset.originalText || 'Add Team';
                    }

                    form.dataset.submitting = '0';
                }
            });
        });
    };

    const ensureDeleteConfirmPopup = () => {
        let overlay = document.getElementById('appDeleteConfirmOverlay');
        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = 'appDeleteConfirmOverlay';
        overlay.className = 'app-confirm-overlay';
        overlay.hidden = true;

        overlay.innerHTML = `
            <div class="app-confirm-card" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle">
                <div class="app-confirm-head">
                    <div class="app-confirm-title" id="appConfirmTitle">Konfirmasi</div>
                </div>
                <div class="app-confirm-body" id="appConfirmMessage">
                    Yakin ingin melanjutkan?
                </div>
                <div class="app-confirm-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm app-confirm-cancel">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm app-confirm-ok">Hapus</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                overlay._resolver?.(false);
            }
        });

        qs('.app-confirm-cancel', overlay)?.addEventListener('click', () => {
            overlay._resolver?.(false);
        });

        qs('.app-confirm-ok', overlay)?.addEventListener('click', () => {
            overlay._resolver?.(true);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay && !overlay.hidden) {
                overlay._resolver?.(false);
            }
        });

        return overlay;
    };

    const openDeleteConfirmPopup = ({ title, message, submitText }) => {
        const overlay = ensureDeleteConfirmPopup();
        const titleEl = qs('#appConfirmTitle', overlay);
        const messageEl = qs('#appConfirmMessage', overlay);
        const okBtn = qs('.app-confirm-ok', overlay);

        if (titleEl) titleEl.textContent = title || 'Konfirmasi';
        if (messageEl) messageEl.textContent = message || 'Yakin ingin melanjutkan?';
        if (okBtn) okBtn.textContent = submitText || 'Hapus';

        overlay.hidden = false;

        return new Promise((resolve) => {
            overlay._resolver = (result) => {
                overlay.hidden = true;
                overlay._resolver = null;
                resolve(Boolean(result));
            };
        });
    };

    const bindDeleteConfirmForms = () => {
        qsa('.js-confirm-delete-form').forEach((form) => {
            if (form.dataset.confirmBound === '1') {
                return;
            }

            form.dataset.confirmBound = '1';

            form.addEventListener('submit', async (event) => {
                if (form.dataset.confirmApproved === '1') {
                    form.dataset.confirmApproved = '0';
                    return;
                }

                event.preventDefault();

                const ok = await openDeleteConfirmPopup({
                    title: form.dataset.confirmTitle || 'Konfirmasi',
                    message: form.dataset.confirmMessage || 'Yakin ingin menghapus?',
                    submitText: form.dataset.confirmSubmit || 'Hapus',
                });

                if (!ok) {
                    return;
                }

                try {
                    sessionStorage.setItem('bgcalc-scroll-y', String(window.scrollY));
                } catch (error) {
                    // ignore
                }

                form.dataset.confirmApproved = '1';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        });
    };

    const restoreScrollAfterDelete = () => {
        try {
            const y = sessionStorage.getItem('bgcalc-scroll-y');
            if (!y) return;

            sessionStorage.removeItem('bgcalc-scroll-y');

            window.requestAnimationFrame(() => {
                window.scrollTo({
                    top: Number.parseInt(y, 10) || 0,
                    behavior: 'auto',
                });
            });
        } catch (error) {
            // ignore
        }
    };

    const bindManagerDrawer = () => {
        qsa('[data-team-manager-toggle]').forEach((toggle) => {
            if (toggle.dataset.drawerBound === '1') {
                return;
            }

            toggle.dataset.drawerBound = '1';

            const target = toggle.getAttribute('data-team-manager-toggle');
            const host = target ? document.getElementById(target) : null;

            if (!host) {
                return;
            }

            const syncExpandedState = () => {
                toggle.setAttribute('aria-expanded', host.classList.contains('is-manager-open') ? 'true' : 'false');
            };

            syncExpandedState();

            toggle.addEventListener('click', () => {
                host.classList.toggle('is-manager-open');
                syncExpandedState();
            });
        });

        qsa('[data-team-manager-close]').forEach((button) => {
            if (button.dataset.drawerCloseBound === '1') {
                return;
            }

            button.dataset.drawerCloseBound = '1';

            button.addEventListener('click', () => {
                const workspace = button.closest('.score-page-workspace');
                if (!workspace) {
                    return;
                }

                workspace.classList.remove('is-manager-open');
                qsa('[data-team-manager-toggle="' + workspace.id + '"]').forEach((toggle) => {
                    toggle.setAttribute('aria-expanded', 'false');
                });
            });
        });
    };

    const bindFormInputs = (form) => {
        const table = qs('.score-table', form);
        const placementMap = JSON.parse(form.dataset.placementMap || '{}');

        updateCompactVars(form);

        const saveNow = async () => {
            if (form.dataset.canManage !== '1' || form.dataset.saving === '1') {
                return;
            }

            form.dataset.saving = '1';
            setStatus(form, 'saving', '');

            try {
                const payload = hasTeamRows(form) ? await saveBulk(form) : await savePotMeta(form);
                setStatus(form, 'saved', payload.message || '');
            } catch (error) {
                setStatus(form, 'error', '');
            } finally {
                form.dataset.saving = '0';
            }
        };

        qsa('.js-score-input, .js-team-input, .js-member-input, .js-pot-input', form).forEach((input) => {
            if (input.dataset.inputBound === '1') {
                return;
            }

            input.dataset.inputBound = '1';

            const queueSave = () => {
                const row = input.closest('tr[data-team-row]');
                if (row) {
                    updateRowTotal(row, placementMap);
                }

                if (input.classList.contains('team-name-input') || input.classList.contains('team-members-inline')) {
                    bindLookupToolbars();
                }

                scheduleSave(form, saveNow);
            };

            input.addEventListener('input', queueSave);
            input.addEventListener('change', queueSave);
            input.addEventListener('blur', queueSave);
        });

        const addGameButton = qs('.js-add-game', form.closest('.pot-module-card') || document);
        const removeGameButton = qs('.js-remove-game', form.closest('.pot-module-card') || document);
        const gameCountInput = qs('.js-game-count', form);

        if (table && gameCountInput && addGameButton && addGameButton.dataset.gameBound !== '1') {
            addGameButton.dataset.gameBound = '1';

            addGameButton.addEventListener('click', () => {
                const nextGameNo = Number.parseInt(gameCountInput.value || '1', 10) + 1;
                const firstHeaderRow = qs('thead tr:first-child', table);
                const secondHeaderRow = qs('thead tr:nth-child(2)', table);
                const totalHead = qs('.js-total-head', firstHeaderRow);

                if (!firstHeaderRow || !secondHeaderRow || !totalHead) {
                    return;
                }

                firstHeaderRow.insertBefore(createHeadCell(nextGameNo), totalHead);
                secondHeaderRow.appendChild(createSubCell(nextGameNo, 'rank', 'Rank'));
                secondHeaderRow.appendChild(createSubCell(nextGameNo, 'placement', 'P.Rank'));
                secondHeaderRow.appendChild(createSubCell(nextGameNo, 'kill', 'Kill'));

                qsa('tbody tr[data-team-row]', table).forEach((row) => {
                    const totalCell = qs('.score-total-cell', row);
                    const teamId = row.dataset.teamId;
                    if (!totalCell || !teamId) {
                        return;
                    }

                    row.insertBefore(createInputCell(teamId, nextGameNo, 'rank'), totalCell);
                    row.insertBefore(createInputCell(teamId, nextGameNo, 'placement'), totalCell);
                    row.insertBefore(createInputCell(teamId, nextGameNo, 'kill'), totalCell);
                });

                gameCountInput.value = String(nextGameNo);
                updateCompactVars(form);
                bindFormInputs(form);
                updateAllRows(table, placementMap);
                scheduleSave(form, saveNow);
            });
        }

        if (table && gameCountInput && removeGameButton && removeGameButton.dataset.gameBound !== '1') {
            removeGameButton.dataset.gameBound = '1';

            removeGameButton.addEventListener('click', () => {
                const currentGameNo = Number.parseInt(gameCountInput.value || '1', 10);
                if (currentGameNo <= 1) {
                    return;
                }

                qs('[data-game-group="' + currentGameNo + '"]', table)?.remove();
                qsa('[data-game-col="' + currentGameNo + '"]', table).forEach((cell) => cell.remove());
                gameCountInput.value = String(currentGameNo - 1);
                updateCompactVars(form);
                updateAllRows(table, placementMap);
                scheduleSave(form, saveNow);
            });
        }

        if (table) {
            updateAllRows(table, placementMap);
        }
    };

    const initScoreWorkspace = () => {
        qsa('.js-score-bulk-form').forEach((form) => bindFormInputs(form));
        bindQuickAddTeamForms();
        bindDeleteConfirmForms();
        bindLookupToolbars();
        bindTeamManagerWorkspace();
        bindManagerDrawer();
        restoreScrollAfterDelete();
    };

    document.addEventListener('DOMContentLoaded', initScoreWorkspace);
    window.initScoreWorkspace = initScoreWorkspace;
})();
