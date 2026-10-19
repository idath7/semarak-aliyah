<!-- Modal Kustomisasi Rubrik Penjurian (Velzon Style) -->
<div class="modal fade" id="rubricCustomizerModal" tabindex="-1" aria-labelledby="rubricCustomizerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom p-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-xs">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16">
                            <i class="ri-equalizer-line"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title fs-15 fw-bold text-dark mb-0" id="rubricCustomizerModalLabel">Kustomisasi Rubrik Penjurian</h5>
                        <small class="text-muted fs-12" id="modal-comp-subtitle">Memuat data cabang lomba...</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="rubric-customizer-form" method="POST">
                @csrf
                <input type="hidden" id="modal-competition-id" name="competition_id" value="">

                <div class="modal-body p-4">
                    <!-- Format Bobot Selector: Pilihan Skor/Poin vs Prosentase -->
                    <div class="card bg-light border p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <label class="form-label fs-13 fw-bold text-dark mb-0">
                                    <i class="ri-sound-module-line text-primary me-1"></i> Tipe Format Bobot:
                                </label>
                                <p class="text-muted fs-11 mb-0">Pilih mode kalkulasi skor dewan juri</p>
                            </div>
                            <div class="col-md-7 mt-2 mt-md-0">
                                <div class="btn-group w-100" role="group" aria-label="Format Bobot">
                                    <input type="radio" class="btn-check" name="rubric_type" id="type-percentage" value="percentage" checked autocomplete="off">
                                    <label class="btn btn-outline-primary btn-sm py-2 fs-12 fw-semibold" for="type-percentage" id="label-type-percentage">
                                        <i class="ri-percent-line me-1"></i> Prosentase (%)
                                    </label>

                                    <input type="radio" class="btn-check" name="rubric_type" id="type-point" value="point" autocomplete="off">
                                    <label class="btn btn-outline-primary btn-sm py-2 fs-12 fw-semibold" for="type-point" id="label-type-point">
                                        <i class="ri-numbers-line me-1"></i> Skor / Poin Maksimal
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Mode Helper Alert -->
                    <div class="alert alert-info border-0 d-flex align-items-start gap-2 mb-3 py-2" id="modal-rubric-hint">
                        <i class="ri-information-line fs-18 text-info mt-1"></i>
                        <div class="fs-12" id="modal-rubric-hint-text">
                            <strong>Mode Prosentase:</strong> Bobot tiap aspek adalah persentase (total ideal 100%). Nilai akhir dihitung secara proporsional.
                        </div>
                    </div>

                    <!-- Live Total Weight / Score Banner -->
                    <div class="card bg-light border p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fs-12 fw-semibold text-dark" id="modal-calc-label">
                                <i class="ri-scales-3-line text-primary me-1"></i> Total Akumulasi Bobot (%):
                            </span>
                            <span class="badge bg-success fs-12 px-3 py-1" id="modal-total-weight-badge">100% (Sempurna & Ideal)</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" id="modal-total-weight-progress" role="progressbar" style="width: 100%;"></div>
                        </div>
                    </div>

                    <!-- Criteria Item Container -->
                    <div class="table-responsive border rounded mb-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light fs-12">
                                <tr>
                                    <th style="width: 45px;" class="text-center">No</th>
                                    <th>Aspek & Kriteria Penilaian</th>
                                    <th style="width: 175px;" class="text-center" id="modal-col-weight-header">Bobot Nilai (%)</th>
                                    <th style="width: 50px;" class="text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody id="modal-criteria-table-body" class="fs-13">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat kriteria...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="modal-btn-add-row">
                            <i class="ri-add-circle-line me-1"></i> Tambah Kriteria Baru
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3" id="modal-btn-reset-default">
                            <i class="ri-restart-line me-1"></i> Reset ke Standar Juknis
                        </button>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                    <span class="fs-12 text-muted" id="modal-status-text"></span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" id="modal-btn-save">
                            <i class="ri-save-line me-1"></i> Simpan Kustomisasi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let activeCompetitionId = null;
    let activeCompetitionTitle = '';
    let currentRubricData = {};
    let defaultRubricData = {};
    let currentRubricType = 'percentage'; // 'percentage' or 'point'

    function getRubricModalInstance() {
        const modalEl = document.getElementById('rubricCustomizerModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            return window.bootstrap.Modal.getOrCreateInstance(modalEl);
        }
        return null;
    }

    function showRubricModal() {
        const modalEl = document.getElementById('rubricCustomizerModal');
        const bsModal = getRubricModalInstance();
        if (bsModal) {
            bsModal.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.removeAttribute('aria-hidden');
            if (!document.querySelector('.modal-backdrop')) {
                const bd = document.createElement('div');
                bd.className = 'modal-backdrop fade show';
                document.body.appendChild(bd);
            }
            document.body.classList.add('modal-open');
        }
    }

    function hideRubricModal() {
        const modalEl = document.getElementById('rubricCustomizerModal');
        const bsModal = getRubricModalInstance();
        if (bsModal) {
            bsModal.hide();
        } else {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            const bd = document.querySelector('.modal-backdrop');
            if (bd) bd.remove();
            document.body.classList.remove('modal-open');
        }
    }

    function setRubricType(type) {
        currentRubricType = type === 'point' ? 'point' : 'percentage';

        const radioPct = document.getElementById('type-percentage');
        const radioPnt = document.getElementById('type-point');
        if (radioPct && radioPnt) {
            radioPct.checked = currentRubricType === 'percentage';
            radioPnt.checked = currentRubricType === 'point';
        }

        const colHeader = document.getElementById('modal-col-weight-header');
        const hintText = document.getElementById('modal-rubric-hint-text');
        const calcLabel = document.getElementById('modal-calc-label');

        if (currentRubricType === 'point') {
            if (colHeader) colHeader.textContent = 'Batas Skor Maksimal (Poin)';
            if (hintText) hintText.innerHTML = '<strong>Mode Skor / Poin Maksimal:</strong> Juri memberikan skor riil dari 0 sampai batas poin masing-masing aspek (contoh: 100 Poin per aspek). Total skor akhir adalah akumulasi poin.';
            if (calcLabel) calcLabel.innerHTML = '<i class="ri-numbers-line text-primary me-1"></i> Total Skor Maksimal (Poin):';
        } else {
            if (colHeader) colHeader.textContent = 'Bobot Nilai (%)';
            if (hintText) hintText.innerHTML = '<strong>Mode Prosentase:</strong> Bobot tiap aspek adalah persentase (total ideal 100%). Nilai akhir dihitung secara proporsional.';
            if (calcLabel) calcLabel.innerHTML = '<i class="ri-scales-3-line text-primary me-1"></i> Total Akumulasi Bobot (%):';
        }

        // Update addon labels in rows
        document.querySelectorAll('#modal-criteria-table-body .weight-addon-label').forEach(label => {
            label.textContent = currentRubricType === 'point' ? 'Poin' : '%';
        });

        // Update inputs max
        document.querySelectorAll('#modal-criteria-table-body .input-criterion-weight').forEach(input => {
            input.setAttribute('max', currentRubricType === 'point' ? '1000' : '100');
        });

        updateTotalWeightCalculation();
    }

    function openRubricModal(compId, compTitle) {
        activeCompetitionId = compId;
        activeCompetitionTitle = compTitle;

        document.getElementById('modal-competition-id').value = compId;
        document.getElementById('modal-comp-subtitle').textContent = `Cabang Lomba: ${compTitle}`;
        document.getElementById('modal-status-text').textContent = '';

        const tbody = document.getElementById('modal-criteria-table-body');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Mengambil data rubrik...</td></tr>`;

        showRubricModal();

        // Fetch criteria from backend
        fetch(`/admin/jury/rubric/${compId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentRubricType = data.rubric_type || 'percentage';
                    currentRubricData = data.active_rubric;
                    defaultRubricData = data.default_rubric;

                    setRubricType(currentRubricType);
                    renderRubricRows(currentRubricData);
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center py-3">Gagal memuat kriteria.</td></tr>`;
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="4" class="text-danger text-center py-3">Terjadi kesalahan jaringan: ${err.message}</td></tr>`;
            });
    }

    function renderRubricRows(rubric) {
        const tbody = document.getElementById('modal-criteria-table-body');
        tbody.innerHTML = '';

        let index = 1;
        const entries = Object.entries(rubric);

        if (entries.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-muted text-center py-3">Belum ada kriteria. Klik "+ Tambah Kriteria Baru" di bawah.</td></tr>`;
            updateTotalWeightCalculation();
            return;
        }

        entries.forEach(([name, weight]) => {
            appendRubricRow(name, weight, index++);
        });

        updateTotalWeightCalculation();
    }

    function appendRubricRow(name = '', weight = null, index = null) {
        const tbody = document.getElementById('modal-criteria-table-body');
        
        // Remove empty placeholder row if exists
        const placeholder = tbody.querySelector('td[colspan="4"]');
        if (placeholder) {
            tbody.innerHTML = '';
        }

        const currCount = tbody.querySelectorAll('tr').length;
        const rowNum = index || (currCount + 1);

        const defaultWeightVal = weight !== null ? weight : (currentRubricType === 'point' ? 100 : 10);
        const maxVal = currentRubricType === 'point' ? 1000 : 100;
        const addonUnit = currentRubricType === 'point' ? 'Poin' : '%';

        const tr = document.createElement('tr');
        tr.className = 'rubric-modal-row';
        tr.innerHTML = `
            <td class="text-center text-muted fw-bold row-number">${rowNum}</td>
            <td>
                <input type="text" name="criteria_names[]" class="form-control form-control-sm input-criterion-name" 
                       value="${escapeHtml(name)}" placeholder="Contoh: Teknik Vokal, Penampilan, dsb." required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="criteria_weights[]" class="form-control form-control-sm text-end fw-bold input-criterion-weight" 
                           value="${defaultWeightVal}" min="0.5" max="${maxVal}" step="0.5" required>
                    <span class="input-group-text bg-light weight-addon-label">${addonUnit}</span>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-ghost-danger text-danger p-0 btn-delete-row" title="Hapus Aspek Ini">
                    <i class="ri-delete-bin-line fs-16"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        // Attach listeners
        const weightInput = tr.querySelector('.input-criterion-weight');
        weightInput.addEventListener('input', updateTotalWeightCalculation);

        const deleteBtn = tr.querySelector('.btn-delete-row');
        deleteBtn.addEventListener('click', () => {
            tr.remove();
            reindexRows();
            updateTotalWeightCalculation();
        });
    }

    function reindexRows() {
        const rows = document.querySelectorAll('#modal-criteria-table-body tr.rubric-modal-row');
        rows.forEach((row, idx) => {
            const numCell = row.querySelector('.row-number');
            if (numCell) numCell.textContent = idx + 1;
        });
    }

    function updateTotalWeightCalculation() {
        const weightInputs = document.querySelectorAll('#modal-criteria-table-body .input-criterion-weight');
        let total = 0;

        weightInputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
        });

        // Format to 1 decimal if needed
        total = Math.round(total * 10) / 10;

        const badge = document.getElementById('modal-total-weight-badge');
        const progress = document.getElementById('modal-total-weight-progress');

        if (!badge || !progress) return;

        if (currentRubricType === 'point') {
            badge.className = 'badge bg-info fs-12 px-3 py-1';
            badge.innerHTML = `<i class="ri-checkbox-circle-fill me-1"></i> Total Batas: ${total} Poin`;
            progress.className = 'progress-bar bg-info';
            progress.style.width = '100%';
        } else {
            // Mode Percentage
            const cappedPct = Math.min(100, Math.max(0, total));
            progress.style.width = `${cappedPct}%`;

            if (total === 100) {
                badge.className = 'badge bg-success fs-12 px-3 py-1';
                badge.textContent = '100% (Sempurna & Ideal)';
                progress.className = 'progress-bar bg-success';
            } else if (total > 100) {
                badge.className = 'badge bg-danger fs-12 px-3 py-1';
                badge.textContent = `Total: ${total}% (Melebihi 100%)`;
                progress.className = 'progress-bar bg-danger';
            } else {
                badge.className = 'badge bg-warning text-dark fs-12 px-3 py-1';
                badge.textContent = `Total: ${total}% (Kurang dari 100%)`;
                progress.className = 'progress-bar bg-warning';
            }
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.openRubricModal = openRubricModal;
    window.showRubricModal = showRubricModal;
    window.hideRubricModal = hideRubricModal;
    window.setRubricType = setRubricType;

    // Modal Events
    document.addEventListener('DOMContentLoaded', () => {
        // Delegated click handler for any rubric customization button
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-kustomisasi-rubrik, [onclick*="openRubricModal"]');
            if (btn) {
                const compId = btn.getAttribute('data-comp-id');
                const compTitle = btn.getAttribute('data-comp-title');
                if (compId && compTitle) {
                    e.preventDefault();
                    openRubricModal(compId, compTitle);
                }
            }
        });

        // Modal Dismiss Handler
        document.querySelectorAll('#rubricCustomizerModal [data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', hideRubricModal);
        });

        // Rubric Type Radio change
        document.querySelectorAll('input[name="rubric_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                setRubricType(e.target.value);
            });
        });

        // Add new row button
        const addRowBtn = document.getElementById('modal-btn-add-row');
        if (addRowBtn) {
            addRowBtn.addEventListener('click', () => {
                appendRubricRow('', null);
                reindexRows();
                updateTotalWeightCalculation();
                const inputs = document.querySelectorAll('#modal-criteria-table-body .input-criterion-name');
                if (inputs.length) {
                    inputs[inputs.length - 1].focus();
                }
            });
        }

        // Reset to default button
        const resetBtn = document.getElementById('modal-btn-reset-default');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (!confirm(`Kembalikan rubrik cabang "${activeCompetitionTitle}" ke standar baku resmi Juknis?`)) {
                    return;
                }

                const statusText = document.getElementById('modal-status-text');
                statusText.innerHTML = '<span class="text-info"><i class="ri-loader-4-line ri-spin"></i> Mereset ke Juknis...</span>';

                fetch(`/admin/jury/rubric/${activeCompetitionId}/reset`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        currentRubricType = data.rubric_type || 'percentage';
                        setRubricType(currentRubricType);
                        currentRubricData = data.rubric;
                        renderRubricRows(data.rubric);
                        statusText.innerHTML = `<span class="text-success"><i class="ri-check-line"></i> ${data.message}</span>`;
                        setTimeout(() => { window.location.reload(); }, 900);
                    }
                })
                .catch(err => {
                    statusText.innerHTML = `<span class="text-danger">Gagal mereset: ${err.message}</span>`;
                });
            });
        }

        // Form Submit
        const form = document.getElementById('rubric-customizer-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const rows = document.querySelectorAll('#modal-criteria-table-body tr.rubric-modal-row');
                if (rows.length === 0) {
                    alert('Harap tambahkan minimal 1 kriteria penilaian!');
                    return;
                }

                const saveBtn = document.getElementById('modal-btn-save');
                const originalHtml = saveBtn.innerHTML;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

                const statusText = document.getElementById('modal-status-text');
                statusText.innerHTML = '<span class="text-primary"><i class="ri-loader-4-line ri-spin"></i> Menyimpan kustomisasi...</span>';

                const formData = new FormData(form);

                fetch(`/admin/jury/rubric/${activeCompetitionId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalHtml;

                    if (data.success) {
                        statusText.innerHTML = `<span class="text-success fw-bold"><i class="ri-checkbox-circle-line me-1"></i> ${data.message}</span>`;
                        setTimeout(() => {
                            window.location.reload();
                        }, 700);
                    } else {
                        statusText.innerHTML = `<span class="text-danger"><i class="ri-error-warning-line me-1"></i> Gagal menyimpan kustomisasi.</span>`;
                    }
                })
                .catch(err => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalHtml;
                    statusText.innerHTML = `<span class="text-danger">Kesalahan jaringan: ${err.message}</span>`;
                });
            });
        }
    });
</script>
