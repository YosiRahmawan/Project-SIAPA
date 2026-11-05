@if($halaman !== 'riwayat')
@php $nominal = $data->sum('nominal'); $dibayar = $data->sum('dibayar'); @endphp
<div class="summary-cards"><section class="panel dark-stat"><p>Sisa tagihan</p><h2>{{ $uang(max(0,$nominal-$dibayar)) }}</h2><small>{{ $halaman === 'peringatan' ? 'Untuk tagihan dalam pengingat' : 'Dikurangi pembayaran diterima' }}</small></section><section class="panel"><p>Sudah dibayar</p><h2>{{ $uang($dibayar) }}</h2><small>Pembayaran yang sudah diterima sekolah</small></section><section class="panel"><p>Jumlah tagihan</p><h2>{{ $data->count() }}</h2><small>{{ $halaman === 'peringatan' ? 'Tagihan perlu diperhatikan' : 'Seluruh tagihan milik Anda' }}</small></section></div>
@if($halaman === 'tagihan')<p class="notice portal-notice">Untuk pembayaran dan konfirmasi bukti QRIS, hubungi petugas keuangan sekolah. Status diperbarui setelah pembayaran diverifikasi.</p>@endif
@endif
<section class="panel"><h2>{{ $halaman === 'riwayat' ? 'Transaksi pembayaran' : 'Rincian tagihan' }}</h2><div class="table-wrap"><table><thead><tr>@if($halaman === 'riwayat')<th>Tanggal</th><th>Tagihan</th><th>Metode</th><th class="money">Jumlah</th><th>Status / keterangan</th>@else<th>Tagihan</th><th>Jatuh tempo</th><th class="money">Nominal</th><th class="money">Dibayar</th><th class="money">Sisa</th><th>Status</th>@endif</tr></thead><tbody>
@forelse($data as $row)
@if($halaman === 'riwayat')
<tr><td>{{ $row->tanggal->format('d/m/Y') }}</td><td><strong>{{ $jenis[$row->tagihan->jenis->value] }}</strong><small>{{ $row->tagihan->periode }}</small></td><td>{{ $row->metode->value === 'manual' ? 'Tunai' : 'QRIS' }}</td><td class="money">{{ $uang($row->jumlah) }}</td><td><span class="badge {{ $row->status->value === 'diterima' ? 'accepted' : 'neutral' }}">{{ $status[$row->status->value] }}</span>@if($row->alasan)<small>{{ $row->alasan }}</small>@endif</td></tr>
@else
<tr><td><strong>{{ $jenis[$row['jenis']] }}</strong><small>{{ $row['periode'] }}</small></td><td>{{ \Carbon\CarbonImmutable::parse($row['jatuh_tempo'])->format('d/m/Y') }}</td><td class="money">{{ $uang($row['nominal']) }}</td><td class="money">{{ $uang($row['dibayar']) }}</td><td class="money"><strong>{{ $uang(max(0,$row['nominal']-$row['dibayar'])) }}</strong></td><td><span class="badge {{ $row['status_tampil'] === 'menunggak' ? 'overdue' : ($row['status_tampil'] === 'lunas' ? 'accepted' : 'pending') }}">{{ $status[$row['status_tampil']] }}</span></td></tr>
@endif
@empty<tr><td colspan="6" class="empty">{{ ['tagihan'=>'Belum ada tagihan untuk akun Anda.','riwayat'=>'Belum ada pembayaran tercatat.','peringatan'=>'Tidak ada tagihan yang mendekati atau melewati jatuh tempo.'][$halaman] }}</td></tr>@endforelse
</tbody></table></div></section>
