@if($halaman === 'siswa')
<form class="panel filters filters-panel" method="get"><label>Cari siswa<input name="q" value="{{ request('q') }}" placeholder="Nama atau NIS siswa"></label><button class="button primary">Cari</button><a href="/admin/siswa" class="reset-link">Reset</a></form>
@endif
<section class="panel"><div class="section-heading"><h2>{{ $halaman === 'pengguna' ? 'Daftar akun' : 'Daftar siswa' }}</h2><span class="caption">{{ $data->total() }} {{ $halaman === 'pengguna' ? 'akun' : 'siswa' }}</span></div>
<div class="table-wrap"><table><thead><tr><th>Nama</th><th>{{ $halaman === 'pengguna' ? 'Username' : 'NIS' }}</th><th>{{ $halaman === 'pengguna' ? 'Role' : 'Kelas' }}</th><th>{{ $halaman === 'siswa' ? 'Status siswa / akses login' : 'Status akun' }}</th><th>Aksi</th></tr></thead><tbody>
@forelse($data as $row)
@php $editData = $row->only($halaman === "pengguna" ? ["id","nama","username","role","aktif"] : ["nis","nama","kelas","aktif"]); if ($halaman === 'siswa' && $row->user_id) $editData['login_aktif'] = (bool) ($row->aktif && $row->user?->aktif); @endphp
<tr><td><strong>{{ $row->nama }}</strong></td><td>{{ $halaman === 'pengguna' ? $row->username : $row->nis }}</td><td>{{ $halaman === 'pengguna' ? ucfirst($row->role->value) : 'Kelas '.$row->kelas }}</td><td><span class="badge {{ $row->aktif ? 'accepted' : 'neutral' }}">{{ $row->aktif ? 'Aktif' : 'Nonaktif' }}</span>@if($halaman === 'siswa')<small>{{ !$row->user_id ? 'Belum punya akun' : ($row->aktif && $row->user?->aktif ? 'Login aktif' : 'Login nonaktif') }}</small>@endif</td><td><div class="row-actions">
@if($halaman === 'siswa' || $row->role->value !== 'siswa')<button type="button" class="button" data-edit='@json($editData)'>Edit</button>@else<a class="text-link" href="/admin/siswa">Kelola di data siswa</a>@endif
@if($halaman === 'siswa') @if(!$row->user_id)<button type="button" class="button" data-account="{{ $row->nis }}" data-name="{{ $row->nama }}">Buat akun</button>@else<span class="caption">Akun tersedia</span>@endif @endif
</div></td></tr>
@empty<tr><td colspan="5" class="empty">{{ request('q') ? 'Siswa tidak ditemukan. Coba nama atau NIS lain.' : 'Belum ada data. Gunakan tombol tambah untuk memulai.' }}</td></tr>@endforelse
</tbody></table></div></section>
<dialog id="portal-editor" class="portal-dialog" aria-labelledby="editor-title">
<form id="portal-editor-form" data-kind="{{ $halaman }}"><div class="section-heading"><h2 id="editor-title">Tambah data</h2><button class="icon-button" type="button" data-close aria-label="Tutup">×</button></div>
<p id="editor-error" class="portal-form-error" role="alert" hidden></p>
<label>Nama<input name="nama" required maxlength="100" autocomplete="off"></label>
@if($halaman === 'pengguna')
<label>Username<input name="username" required maxlength="50" pattern="[A-Za-z0-9_-]+" autocomplete="off"></label>
<label>Role<select name="role"><option value="admin">Administrator</option><option value="keuangan">Petugas Keuangan</option><option value="yayasan">Yayasan</option></select></label>
<label><span id="password-label">Kata sandi awal</span><input name="password" type="password" minlength="8" autocomplete="new-password"><small>Minimal 8 karakter, memuat huruf dan angka. Wajib diganti saat login berikutnya.</small></label>
@else
<label>NIS<input name="nis" required inputmode="numeric" pattern="[0-9]{4,20}" minlength="4" maxlength="20"></label>
<label>Kelas<select name="kelas">@foreach(range(1,6) as $kelas)<option value="{{ $kelas }}">Kelas {{ $kelas }}</option>@endforeach</select></label>
@endif
<label id="active-field" hidden>{{ $halaman === 'siswa' ? 'Status siswa' : 'Status akun' }}<select name="aktif"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></label>
@if($halaman === 'siswa')<label id="login-active-field" hidden>Akses login<select name="login_aktif"><option value="1">Aktif</option><option value="0">Nonaktif</option></select><small>Siswa nonaktif otomatis tidak bisa login. Saat siswa diaktifkan kembali, pilih akses login Aktif untuk membukanya.</small></label>@endif
<div class="form-footer"><button class="button" type="button" data-close>Batal</button><button class="button primary" type="submit">Simpan</button></div>
</form></dialog>
<dialog id="account-dialog" class="portal-dialog" aria-labelledby="account-title"><h2 id="account-title">Akun siswa</h2><p id="account-description" class="caption"></p><div id="account-result" class="notice"></div><div class="form-footer"><button class="button" type="button" data-close>Tutup</button><button class="button primary" type="button" id="account-create">Buat akun</button></div></dialog>
