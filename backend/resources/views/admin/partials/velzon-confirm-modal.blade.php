<!-- Reusable Velzon Confirmation Modal -->
<div class="modal fade" id="velzonConfirmModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(3px); z-index: 9999;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.12) !important;">
      <div class="modal-body p-4 text-center">
        <div id="velzonConfirmAvatar" class="avatar-md rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fs-28" style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.15); color: #ef4444;">
          <i id="velzonConfirmIcon" class="ri-delete-bin-line"></i>
        </div>
        <h5 id="velzonConfirmTitle" class="fs-16 fw-bold text-white mb-2">Konfirmasi Tindakan</h5>
        <p id="velzonConfirmMessage" class="fs-13 mb-4" style="color: #cbd5e1; white-space: pre-line;"></p>
        <div class="d-flex gap-2 justify-content-center">
          <button type="button" class="btn btn-sm btn-light border px-3.5 py-1.5 rounded-3 text-muted" data-bs-dismiss="modal" id="velzonConfirmCancelBtn">Batal</button>
          <button type="button" class="btn btn-sm btn-danger px-3.5 py-1.5 rounded-3 text-white shadow-sm" id="velzonConfirmOkBtn">Ya, Lanjutkan</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  if (typeof window.velzonConfirm !== 'function') {
    window.velzonConfirm = function({ title, message, type = 'danger', confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', icon }) {
      return new Promise((resolve) => {
        const modalEl = document.getElementById('velzonConfirmModal');
        if (!modalEl || typeof bootstrap === 'undefined') {
          resolve(window.confirm(message));
          return;
        }
        const titleEl = document.getElementById('velzonConfirmTitle');
        const msgEl = document.getElementById('velzonConfirmMessage');
        const okBtn = document.getElementById('velzonConfirmOkBtn');
        const cancelBtn = document.getElementById('velzonConfirmCancelBtn');
        const avatarEl = document.getElementById('velzonConfirmAvatar');
        const iconEl = document.getElementById('velzonConfirmIcon');

        titleEl.textContent = title || 'Konfirmasi Tindakan';
        msgEl.textContent = message || '';
        okBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;

        if (type === 'danger') {
          avatarEl.style.background = 'rgba(239, 68, 68, 0.15)';
          avatarEl.style.color = '#ef4444';
          iconEl.className = icon || 'ri-delete-bin-line';
          okBtn.className = 'btn btn-sm btn-danger px-3.5 py-1.5 rounded-3 text-white shadow-sm';
        } else if (type === 'warning') {
          avatarEl.style.background = 'rgba(245, 158, 11, 0.15)';
          avatarEl.style.color = '#f59e0b';
          iconEl.className = icon || 'ri-alert-line';
          okBtn.className = 'btn btn-sm btn-warning px-3.5 py-1.5 rounded-3 text-dark fw-semibold shadow-sm';
        } else if (type === 'success') {
          avatarEl.style.background = 'rgba(16, 185, 129, 0.15)';
          avatarEl.style.color = '#10b981';
          iconEl.className = icon || 'ri-checkbox-circle-line';
          okBtn.className = 'btn btn-sm btn-success px-3.5 py-1.5 rounded-3 text-white shadow-sm';
        } else {
          avatarEl.style.background = 'rgba(59, 130, 246, 0.15)';
          avatarEl.style.color = '#3b82f6';
          iconEl.className = icon || 'ri-information-line';
          okBtn.className = 'btn btn-sm btn-primary px-3.5 py-1.5 rounded-3 text-white shadow-sm';
        }

        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        let resolved = false;

        const onOk = () => {
          resolved = true;
          cleanup();
          modalInstance.hide();
          resolve(true);
        };

        const onCancel = () => {
          if (!resolved) {
            resolved = true;
            cleanup();
            resolve(false);
          }
        };

        const onHidden = () => {
          if (!resolved) {
            resolved = true;
            cleanup();
            resolve(false);
          }
        };

        function cleanup() {
          okBtn.removeEventListener('click', onOk);
          cancelBtn.removeEventListener('click', onCancel);
          modalEl.removeEventListener('hidden.bs.modal', onHidden);
        }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        modalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });

        modalInstance.show();
      });
    };
  }
</script>
