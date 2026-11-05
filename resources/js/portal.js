const editor = document.querySelector('#portal-editor');
const form = document.querySelector('#portal-editor-form');
const feedback = document.querySelector('#portal-feedback');
const saved = sessionStorage.getItem('portal-saved');
if (feedback && saved) {
    feedback.textContent = saved;
    feedback.hidden = false;
    sessionStorage.removeItem('portal-saved');
}
async function send(url, method, data) {
    const response = await fetch(url, {method, headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:JSON.stringify(data)});
    const result = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(response.status === 419 || response.status === 401 ? 'Sesi berakhir. Muat ulang halaman dan masuk kembali.' : Object.values(result.errors || {}).flat().join(' ') || result.message || 'Permintaan gagal. Silakan coba lagi.');
    return result;
}
function openEditor(row = null) {
    form.reset();
    form.dataset.id = row ? String(row.id ?? row.nis) : '';
    document.querySelector('#editor-title').textContent = `${row ? 'Edit' : 'Tambah'} ${form.dataset.kind === 'pengguna' ? 'pengguna' : 'siswa'}`;
    document.querySelector('#editor-error').hidden = true;
    document.querySelector('#active-field').hidden = !row;
    const identity = form.elements.namedItem(form.dataset.kind === 'pengguna' ? 'username' : 'nis');
    identity.disabled = Boolean(row);
    if (row) for (const [key, value] of Object.entries(row)) {
        const field = form.elements.namedItem(key);
        if (field) field.value = typeof value === 'boolean' ? (value ? '1' : '0') : value;
    }
    const password = form.elements.namedItem('password');
    if (password) {
        password.required = !row;
        document.querySelector('#password-label').textContent = row ? 'Kata sandi baru (opsional)' : 'Kata sandi awal';
    }
    editor.showModal();
}
document.querySelector('[data-new]')?.addEventListener('click', () => openEditor());
document.querySelectorAll('[data-edit]').forEach(button => button.addEventListener('click', () => openEditor(JSON.parse(button.dataset.edit))));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
form?.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('[type="submit"]');
    const error = document.querySelector('#editor-error');
    const data = Object.fromEntries(new FormData(form));
    const id = form.dataset.id;
    if (!data.password) delete data.password;
    if (id) data.aktif = data.aktif === '1'; else delete data.aktif;
    if (data.kelas) data.kelas = Number(data.kelas);
    button.disabled = true; button.textContent = 'Menyimpan…'; error.hidden = true;
    form.setAttribute('aria-busy','true');
    try {
        await send(`/admin/${form.dataset.kind}${id ? `/${encodeURIComponent(id)}` : ''}`, id ? 'PATCH' : 'POST', data);
        sessionStorage.setItem('portal-saved', 'Data berhasil disimpan.');
        location.reload();
    } catch (e) { error.textContent = e.message; error.hidden = false; }
    finally { button.disabled = false; button.textContent = 'Simpan'; form.removeAttribute('aria-busy'); }
});
const accountDialog = document.querySelector('#account-dialog');
const accountCreate = document.querySelector('#account-create');
let accountCreated = false;
document.querySelectorAll('[data-account]').forEach(button => button.addEventListener('click', () => {
    accountCreate.dataset.nis = button.dataset.account;
    accountCreate.hidden = false; accountCreate.disabled = false;
    accountCreated = false;
    document.querySelector('#account-description').textContent = `${button.dataset.name} · NIS ${button.dataset.account}`;
    document.querySelector('#account-result').textContent = 'Buat akun login untuk siswa ini? Kata sandi awal ditampilkan sekali dan wajib diganti saat pertama masuk.';
    accountDialog.showModal();
}));
accountCreate?.addEventListener('click', async () => {
    accountCreate.disabled = true; accountCreate.textContent = 'Membuat…';
    const result = document.querySelector('#account-result');
    try {
        const data = await send(`/admin/siswa/${encodeURIComponent(accountCreate.dataset.nis)}/akun`, 'POST', {});
        result.replaceChildren();
        for (const [label, value] of [['Username',data.username],['Kata sandi awal',data.password_awal]]) {
            const field = document.createElement('label'); field.textContent = label;
            const input = document.createElement('input'); input.readOnly = true; input.value = value;
            input.addEventListener('click', () => input.select()); field.append(input); result.append(field);
        }
        const note = document.createElement('p'); note.textContent = 'Simpan informasi ini sebelum menutup. Kata sandi awal tidak dapat ditampilkan kembali.'; result.append(note);
        accountCreate.hidden = true; accountCreated = true;
    } catch (e) { result.textContent = e.message; }
    finally { accountCreate.disabled = false; accountCreate.textContent = 'Buat akun'; }
});
accountDialog?.addEventListener('close', () => { if (accountCreated) location.reload(); });
document.querySelector('#student-lookup')?.addEventListener('submit', event => {
    event.preventDefault();
    location.assign(`/laporan/siswa/${encodeURIComponent(event.currentTarget.elements.nis.value.trim())}`);
});
