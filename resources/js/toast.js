// Notifikasi sementara yang sama di semua peran. Sukses hilang sendiri, galat bertahan sampai ditutup.
const region = () => {
    let box = document.querySelector('.toasts');
    if (!box) {
        box = document.createElement('div');
        box.className = 'toasts';
        box.setAttribute('role', 'region');
        box.setAttribute('aria-label', 'Notifikasi');
        document.body.append(box);
    }
    return box;
};

function dismiss(item) {
    clearTimeout(item.timer);
    item.remove();
}

export function toast(message, { type = 'success', link = null, duration = 6000 } = {}) {
    if (!message) return null;
    const box = region();
    [...box.children].filter(el => el.dataset.message === message).forEach(dismiss);
    while (box.children.length >= 3) dismiss(box.firstElementChild);

    const item = document.createElement('div');
    item.className = `toast ${type}`;
    item.dataset.message = message;
    item.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = type === 'error' ? '!' : '✓';
    const body = document.createElement('div');
    body.className = 'toast-body';
    const text = document.createElement('span');
    text.textContent = message;
    body.append(text);
    if (link) {
        const anchor = document.createElement('a');
        anchor.href = link.href;
        anchor.target = '_blank';
        anchor.rel = 'noopener';
        anchor.textContent = link.text;
        body.append(anchor);
    }
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-close';
    close.setAttribute('aria-label', 'Tutup notifikasi');
    close.textContent = '×';
    close.addEventListener('click', () => dismiss(item));
    item.append(icon, body, close);
    box.append(item);

    if (type !== 'error') {
        const start = () => { item.timer = setTimeout(() => dismiss(item), duration); };
        item.addEventListener('mouseenter', () => clearTimeout(item.timer));
        item.addEventListener('mouseleave', start);
        item.addEventListener('focusin', () => clearTimeout(item.timer));
        item.addEventListener('focusout', start);
        start();
    }
    return item;
}

toast.clear = () => document.querySelectorAll('.toasts .toast').forEach(dismiss);
window.toast = toast;

// Pesan dari server (session flash) ditampilkan sebagai toast.
const flash = () => document.querySelectorAll('[data-flash]').forEach(el => toast(el.textContent.trim(), { type: el.dataset.flash }));
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', flash); else flash();
