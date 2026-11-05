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
document.querySelectorAll('.sidebar-toggle').forEach(button => button.addEventListener('click', () => {
    if (matchMedia('(max-width:760px)').matches) {
        document.body.classList.remove('menu-open');
        $('.mobile-menu')?.setAttribute('aria-expanded', 'false');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        button.setAttribute('aria-expanded', String(!document.body.classList.contains('sidebar-collapsed')));
        button.setAttribute('aria-label', document.body.classList.contains('sidebar-collapsed') ? 'Buka sidebar' : 'Ciutkan sidebar');
        button.title = button.getAttribute('aria-label');
    }
}));
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
$('#student-search')?.addEventListener('submit', e => {
    e.preventDefault();
    const form = e.currentTarget;
    $('#payment-form').hidden = true;
    $('#student-result').textContent = 'Mencari siswa…';
    submit(form, async () => {
        let data;
        try { data = await api(`/keuangan/siswa/${encodeURIComponent(form.elements.nis.value.trim())}`); }
        catch (error) { $('#student-result').textContent = 'Pencarian belum berhasil. Periksa NIS dan coba kembali.'; throw error; }
        const result = $('#student-result'); result.replaceChildren();
        const card = document.createElement('div'); card.className = 'student-result-card';
        const name = document.createElement('strong'); name.textContent = data.siswa.nama;
        const detail = document.createElement('small'); detail.textContent = `NIS ${data.siswa.nis} / Kelas ${data.siswa.kelas} / ${data.siswa.aktif ? 'Aktif' : 'Tidak aktif'}`;
        card.append(name, detail); result.append(card);
        // Backend reserves pending QRIS amounts; refresh this list after each submission.
        bills = data.tagihan.filter(b => b.status !== 'lunas' && b.status_tampil !== 'menunggu_verifikasi' && b.nominal > b.dibayar);
        const options = $('#bill-options'); options.querySelectorAll('label').forEach(el => el.remove());
        if (!bills.length) { const text = document.createElement('p'); text.textContent = 'Tidak ada tagihan yang dapat dibayar. Tagihan sudah lunas atau sedang menunggu verifikasi.'; result.append(text); return; }
        bills.forEach((bill, index) => {
            const label = document.createElement('label'); label.className = 'bill-option';
            const radio = document.createElement('input'); radio.type = 'radio'; radio.name = 'tagihan'; radio.value = bill.id; radio.required = true; radio.checked = index === 0;
            const text = document.createElement('span'); text.textContent = `${jenis[bill.jenis]} ${bill.periode}`;
            const due = document.createElement('small'); due.textContent = `Jatuh tempo ${bill.jatuh_tempo}`; text.append(due);
            const amount = document.createElement('strong'); amount.textContent = money(bill.nominal - bill.dibayar);
            radio.addEventListener('change', () => { $('#payment-amount').max = bill.nominal - bill.dibayar; $('#payment-amount').value = bill.nominal - bill.dibayar; updateSummary(); });
            label.append(radio, text, amount); options.append(label);
        });
        $('#payment-amount').max = bills[0].nominal - bills[0].dibayar;
        $('#payment-amount').value = bills[0].nominal - bills[0].dibayar;
        $('#payment-form').hidden = false;
        updateSummary();
    });
});
$('#payment-amount')?.addEventListener('input', updateSummary);
$('#payment-method')?.addEventListener('change', e => { const qris = e.target.value === 'qris'; $('#proof-field').hidden = !qris; $('#proof-field input').required = qris; if (!qris) $('#proof-field input').value = ''; });
$('#payment-form')?.addEventListener('submit', e => {
    e.preventDefault(); const form = e.currentTarget;
    submit(form, async () => {
        const bill = form.querySelector('input[name="tagihan"]:checked');
        const data = new FormData(form);
        if (data.get('metode') === 'manual') data.delete('bukti');
        const payment = await api(`/keuangan/tagihan/${bill.value}/pembayaran`, { method: 'POST', body: data });
        form.hidden = true;
        $('#student-result').textContent = 'Pembayaran tersimpan. Cari siswa kembali untuk memuat tagihan terbaru.';
        ['total','paid','amount','left'].forEach(key => $(`#summary-${key}`).textContent = money(0));
        form.reset();
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
$('#verify-form')?.addEventListener('submit', e => {
    e.preventDefault(); const form = e.currentTarget;
    const accept = e.submitter?.value === 'terima';
    if (!accept && !form.elements.alasan.value.trim()) { form.elements.alasan.setCustomValidity('Isi alasan penolakan.'); form.elements.alasan.reportValidity(); return; }
    form.elements.alasan.setCustomValidity('');
    if (!window.confirm(accept ? 'Terima bukti ini dan masukkan ke penerimaan sekolah?' : 'Tolak bukti pembayaran ini?')) return;
    submit(form, async () => {
        await api(`/keuangan/pembayaran/${form.dataset.id}/verifikasi`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ terima: accept, alasan: form.elements.alasan.value }) });
        window.location.assign('/keuangan/halaman/verifikasi');
    });
});
$('#verify-form textarea')?.addEventListener('input', e => e.target.setCustomValidity(''));
document.querySelectorAll('.cancel-payment').forEach(button => button.addEventListener('click', () => {
    const reason = window.prompt('Alasan pembatalan pembayaran (wajib, maksimal 500 karakter):');
    if (reason === null) return;
    if (!reason.trim() || reason.length > 500) { feedback('Isi alasan pembatalan sepanjang 1–500 karakter.', true); return; }
    if (!window.confirm('Batalkan pembayaran? Penerimaan dan saldo tagihan akan dihitung ulang.')) return;
    submit(button.closest('tr'), async () => { await api(`/keuangan/pembayaran/${button.dataset.id}/batal`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({alasan:reason})}); window.location.reload(); });
}));
