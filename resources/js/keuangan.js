const $ = (s) => document.querySelector(s);
const money = (n) => `Rp${Number(n || 0).toLocaleString('id-ID')}`;
const jenis = { spp: 'SPP', daftar_ulang: 'Daftar ulang', jemputan: 'Jemputan' };
function feedback(message, error = false, receipt = null) {
    const box = $('#feedback');
    if (!box) return;
    box.hidden = false;
    box.classList.toggle('error', error);
    box.textContent = message;
    if (receipt) {
        const link = document.createElement('a');
        link.href = `/keuangan/kuitansi/${receipt}`;
        link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Cetak kuitansi ↗';
        box.append(link);
    }
    box.scrollIntoView({ block: 'nearest' });
}
async function api(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]')?.content, ...options.headers },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(response.status === 419 || response.status === 401 ? 'Sesi berakhir. Silakan masuk kembali.' : Object.values(data.errors || {}).flat().join(' ') || data.message || 'Permintaan gagal. Silakan coba kembali.');
    return data;
}
async function submit(form, task) {
    const buttons = [...form.querySelectorAll('button')];
    buttons.forEach(b => b.disabled = true);
    form.setAttribute('aria-busy', 'true');
    try { await task(); } catch (error) { feedback(error.message || 'Koneksi bermasalah. Silakan coba kembali.', true); }
    finally { buttons.forEach(b => b.disabled = false); form.removeAttribute('aria-busy'); }
}
// Dialog tanya sebagai pengganti prompt() dan confirm(). Mengembalikan true, teks isian, atau null bila dibatalkan.
function ask({ title, message = '', label = '', kind = null, value = '', hint = '', okText = 'Lanjut', danger = false }) {
    return new Promise(resolve => {
        const dialog = $('#ask-dialog'), form = $('#ask-form'), error = $('#ask-error');
        const input = kind === 'number' ? $('#ask-number') : $('#ask-text');
        $('#ask-title').textContent = title;
        $('#ask-message').textContent = message;
        $('#ask-message').hidden = !message;
        $('#ask-field').hidden = !kind;
        $('#ask-label').textContent = label;
        $('#ask-hint').textContent = hint;
        $('#ask-number').hidden = kind !== 'number';
        $('#ask-text').hidden = kind !== 'text';
        input.value = value;
        error.hidden = true;
        const ok = $('#ask-ok');
        ok.textContent = okText;
        ok.className = `button ${danger ? 'danger' : 'primary'}`;
        const done = result => { dialog.close(); resolve(result); };
        form.onsubmit = e => {
            e.preventDefault();
            if (!kind) return done(true);
            const text = input.value.trim();
            const valid = kind === 'number' ? /^\d+$/.test(text) && Number(text) > 0 && Number.isSafeInteger(Number(text)) : text.length > 0 && text.length <= 500;
            if (!valid) { error.textContent = kind === 'number' ? 'Masukkan nominal rupiah bulat lebih dari nol, tanpa titik.' : 'Isi alasan 1 sampai 500 karakter.'; error.hidden = false; input.focus(); return; }
            done(text);
        };
        dialog.querySelectorAll('[data-ask-cancel]').forEach(b => b.onclick = () => done(null));
        dialog.oncancel = () => resolve(null);
        dialog.showModal();
        (kind ? input : ok).focus();
    });
}
function setSidebar(collapsed) {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    document.querySelectorAll('.sidebar-toggle').forEach(button => {
        button.setAttribute('aria-expanded', String(!collapsed));
        button.setAttribute('aria-label', collapsed ? 'Buka sidebar' : 'Ciutkan sidebar');
        button.title = button.getAttribute('aria-label');
    });
}
document.querySelectorAll('.sidebar-toggle').forEach(button => button.addEventListener('click', () => {
    if (matchMedia('(max-width:760px)').matches) {
        document.body.classList.remove('menu-open');
        $('.mobile-menu')?.setAttribute('aria-expanded', 'false');
    } else {
        setSidebar(!document.body.classList.contains('sidebar-collapsed'));
        try { localStorage.setItem('siapa-sidebar', document.body.classList.contains('sidebar-collapsed') ? 'ciut' : 'lebar'); } catch {}
    }
}));
try { if (localStorage.getItem('siapa-sidebar') === 'ciut' && !matchMedia('(max-width:760px)').matches) setSidebar(true); } catch {}
$('.mobile-menu')?.addEventListener('click', e => { document.body.classList.toggle('menu-open'); e.currentTarget.setAttribute('aria-expanded', String(document.body.classList.contains('menu-open'))); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { document.body.classList.remove('menu-open'); $('.mobile-menu')?.setAttribute('aria-expanded', 'false'); } });
$('.print-receipt')?.addEventListener('click', () => window.print());

let bills = [];
function updateSummary() {
    const bill = bills.find(b => String(b.id) === $('#payment-form input[name="tagihan"]:checked')?.value);
    if (!bill) return;
    const amount = Number($('#payment-amount').value);
    $('#summary-total').textContent = money(bill.nominal);
    $('#summary-paid').textContent = money(bill.dibayar);
    $('#summary-amount').textContent = money(amount);
    $('#summary-left').textContent = money(Math.max(0, bill.nominal - bill.dibayar - amount));
}
function clearPaymentSummary() {
    ['total','paid','amount','left'].forEach(key => $(`#summary-${key}`).textContent = money(0));
}
function loadStudent(form, keepFeedback = false) {
    bills = [];
    clearPaymentSummary();
    if (!keepFeedback) {
        $('#feedback').hidden = true;
        $('#feedback').textContent = '';
        $('#feedback').classList.toggle('error', false);
    }
    $('#payment-form').reset();
    syncPaymentMethod();
    $('#payment-form').hidden = true;
    $('#student-result').textContent = 'Mencari siswa…';
    return submit(form, async () => {
        let data;
        try { data = await api(`/keuangan/siswa/${encodeURIComponent(form.elements.nis.value.trim())}`); }
        catch (error) { $('#student-result').textContent = 'Pencarian belum berhasil. Periksa NIS dan coba kembali.'; throw error; }
        const result = $('#student-result'); result.replaceChildren();
        const card = document.createElement('div'); card.className = 'student-result-card';
        const name = document.createElement('strong'); name.textContent = data.siswa.nama;
        const detail = document.createElement('small'); detail.textContent = `NIS ${data.siswa.nis} / Kelas ${data.siswa.kelas} / ${data.siswa.aktif ? 'Aktif' : 'Tidak aktif'}`;
        card.append(name, detail); result.append(card);
        // Backend reserves pending QRIS amounts; refresh this list after each submission.
        bills = data.tagihan.filter(b => b.status !== 'lunas' && b.sisa_bisa_dibayar > 0);
        const options = $('#bill-options'); options.querySelectorAll('label').forEach(el => el.remove());
        if (!bills.length) { const text = document.createElement('p'); text.textContent = 'Tidak ada tagihan yang dapat dibayar. Tagihan sudah lunas atau sedang menunggu verifikasi.'; result.append(text); return; }
        bills.forEach((bill, index) => {
            const label = document.createElement('label'); label.className = 'bill-option';
            const radio = document.createElement('input'); radio.type = 'radio'; radio.name = 'tagihan'; radio.value = bill.id; radio.required = true; radio.checked = index === 0;
            const text = document.createElement('span'); text.textContent = `${jenis[bill.jenis]} ${bill.periode}`;
            const due = document.createElement('small'); due.textContent = `Jatuh tempo ${bill.jatuh_tempo}`; text.append(due);
            const amount = document.createElement('strong'); amount.textContent = money(bill.sisa_bisa_dibayar);
            radio.addEventListener('change', () => { $('#payment-amount').max = bill.sisa_bisa_dibayar; $('#payment-amount').value = bill.sisa_bisa_dibayar; updateSummary(); });
            if (bill.status_tampil === 'menunggu_verifikasi') {
                const pending = document.createElement('small'); pending.textContent = 'Ada QRIS menunggu verifikasi. Nominal di kanan adalah sisa yang masih dapat dibayar.'; text.append(pending);
            }
            label.append(radio, text, amount); options.append(label);
        });
        $('#payment-amount').max = bills[0].sisa_bisa_dibayar;
        $('#payment-amount').value = bills[0].sisa_bisa_dibayar;
        $('#payment-form').hidden = false;
        updateSummary();
    });
}
$('#student-search')?.addEventListener('submit', e => { e.preventDefault(); loadStudent(e.currentTarget); });
$('#payment-amount')?.addEventListener('input', updateSummary);
function syncPaymentMethod() {
    const qris = $('#payment-method').value === 'qris';
    $('#proof-field').hidden = !qris;
    $('#proof-field input').required = qris;
    if (!qris) $('#proof-field input').value = '';
}
$('#payment-method')?.addEventListener('change', syncPaymentMethod);
$('#payment-form')?.addEventListener('submit', e => {
    e.preventDefault(); const form = e.currentTarget;
    submit(form, async () => {
        const bill = form.querySelector('input[name="tagihan"]:checked');
        const data = new FormData(form);
        if (data.get('metode') === 'manual') data.delete('bukti');
        const payment = await api(`/keuangan/tagihan/${bill.value}/pembayaran`, { method: 'POST', body: data });
        await loadStudent($('#student-search'), true);
        feedback(payment.status === 'diterima' ? 'Pembayaran diterima. Saldo tagihan sudah diperbarui.' : 'Pengajuan QRIS tersimpan dan menunggu verifikasi.', false, payment.status === 'diterima' ? payment.id : null);
    });
});
if ($('#student-search input')?.value) $('#student-search').requestSubmit();
$('#bill-type')?.addEventListener('change', e => {
    const type = e.target.value;
    $('#bill-year').hidden = type !== 'daftar_ulang'; $('#bill-period').hidden = type === 'daftar_ulang'; $('#transport-fields').hidden = type !== 'jemputan';
    $('#bill-period input').required = type !== 'daftar_ulang';
    $('#bill-year input').required = type === 'daftar_ulang';
    $('#transport-fields').querySelectorAll('input').forEach(input => input.required = type === 'jemputan');
});
$('#bill-form')?.addEventListener('submit', e => {
    e.preventDefault(); const form = e.currentTarget;
    submit(form, async () => {
        const type = form.elements.jenis.value;
        const data = new FormData(form);
        const result = await api(`/keuangan/tagihan/${type === 'daftar_ulang' ? 'daftar-ulang' : type}`, { method: 'POST', body: data });
        feedback(type === 'jemputan' ? 'Tagihan jemputan berhasil dibuat.' : `${result.dibuat} tagihan baru berhasil dibuat. Tagihan yang sudah ada tetap dipertahankan.`);
    });
});
$('#verify-form')?.addEventListener('submit', async e => {
    e.preventDefault(); const form = e.currentTarget;
    const accept = e.submitter?.value === 'terima';
    if (!accept && !form.elements.alasan.value.trim()) { form.elements.alasan.setCustomValidity('Isi alasan penolakan.'); form.elements.alasan.reportValidity(); return; }
    form.elements.alasan.setCustomValidity('');
    if (!await ask(accept ? { title: 'Terima pembayaran?', message: 'Bukti ini akan dimasukkan ke penerimaan sekolah dan saldo tagihan diperbarui.', okText: 'Terima' } : { title: 'Tolak bukti pembayaran?', message: 'Alasan penolakan akan dicatat.', okText: 'Tolak bukti', danger: true })) return;
    submit(form, async () => {
        await api(`/keuangan/pembayaran/${form.dataset.id}/verifikasi`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ terima: accept, alasan: form.elements.alasan.value }) });
        window.location.assign('/keuangan/halaman/verifikasi');
    });
});
$('#verify-form textarea')?.addEventListener('input', e => e.target.setCustomValidity(''));
document.querySelectorAll('.cancel-payment').forEach(button => button.addEventListener('click', async () => {
    const reason = await ask({ title: 'Batalkan pembayaran', message: 'Penerimaan dan saldo tagihan akan dihitung ulang. Data pembayaran lama tetap tersimpan.', label: 'Alasan pembatalan', kind: 'text', hint: 'Wajib diisi, maksimal 500 karakter.', okText: 'Batalkan pembayaran', danger: true });
    if (reason === null) return;
    submit(button.closest('tr'), async () => { await api(`/keuangan/pembayaran/${button.dataset.id}/batal`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({alasan:reason})}); window.location.reload(); });
}));

document.querySelectorAll('.edit-transport').forEach(button => button.addEventListener('click', async () => {
    const value = await ask({ title: 'Ubah nominal jemputan', label: 'Nominal baru (Rp)', kind: 'number', value: button.dataset.amount, hint: 'Rupiah penuh, tanpa titik. Hanya bisa diubah sebelum ada pembayaran.', okText: 'Simpan' });
    if (value === null) return;
    submit(button.closest('tr'), async () => {
        await api(`/keuangan/tagihan/${button.dataset.id}/nominal`, {method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({nominal:Number(value)})});
        window.location.reload();
    });
}));
