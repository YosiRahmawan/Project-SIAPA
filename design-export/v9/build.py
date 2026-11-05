from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run,blue_panel
titles=['Pembayaran tunai','Pembayaran QRIS BSI','Pilih penerima','Pengingat satuan','Pengingat massal']
run('file.new',name='SIAPA v9 • Pembayaran dan pengingat')
run('page.rename',name='A Manual  /  B Terintegrasi')
for api in (False,True):
    lane='B' if api else 'A'
    mode='Dengan API' if api else 'Tanpa API'
    for i,title in enumerate(titles):
        key=lane+str(i)
        f=B.frame(key,f'{lane}{i+1:02} • {mode} / {title}',i*1180,int(api)*910,1120,820)
        head=blue_panel(f,24,24,1072,176,24,mode)
        B.text(head,30,20,'SIAPA    /    KEUANGAN SEKOLAH',12,'#DBE9FF',True,570)
        B.text(head,30,52,title,34,'#FFFFFF',True,720)
        B.text(head,30,122,mode+'   •   Simulasi petugas keuangan',15,'#DBE9FF',w=640)
        B.text(head,870,50,f'{lane}  /  0{i+1}',30,'#D8F9FF',True,152,align='right')
        for j,label in enumerate(['Tunai','QRIS BSI','Penerima','Satuan','Massal']):
            B.button(f,24+j*216,219,204,label,style='primary' if i==j else 'secondary',dest=lane+str(j))
        if i==0:
            summary=('Rp590.000','Pembayaran di loket','SPP November • Aisyah Rahma')
            rows=[('Siswa','Aisyah Rahma   /   2410123   /   Kelas 1'),('Tagihan','SPP November 2026'),('Uang diterima','Rp590.000 tunai'),('Pencatatan','Petugas menghitung uang dan menyimpan transaksi.'),('Hasil contoh','Lunas • kuitansi TUN-1103-01')]
        elif i==1:
            summary=('Rp590.000','QRIS merchant sekolah','Contoh transaksi terpisah dari tunai')
            rows=[('Tagihan','SPP November • Bagas Pratama'),('Pembayaran','QR per tagihan dari mitra' if api else 'Pindai QRIS statis BSI milik sekolah'),('Referensi','INV-BGS-1126 • Rp590.000' if api else 'Petugas membuka transaksi di aplikasi merchant.'),('Konfirmasi','Cocokkan referensi, nominal dan status sukses dari mitra.' if api else 'Cocokkan bukti, nominal, waktu, dan referensi merchant.'),('Status akhir','Lunas otomatis jika cocok • selisih masuk pemeriksaan.' if api else 'Lunas setelah petugas mengonfirmasi penerimaan.')]
        elif i==2:
            summary=('20 wali','Penerima yang memenuhi syarat','12 mendekati tempo • 8 terlambat')
            rows=[('Tanggal proses','7 November 2026 • contoh data'),('H−3 / 12 wali','Tagihan terbuka jatuh tempo 10 November.'),('Terlambat / 8 wali','Tagihan terbuka melewati 31 Oktober.'),('Dikecualikan','Tagihan lunas dan nomor tanpa izin pengingat.'),('Pilihan','Satu wali atau semua wali dalam daftar terpilih.')]
        else:
            many=i==4
            summary=('20 wali' if many else '1 wali','Pengiriman WhatsApp','Pratinjau sebelum pengiriman')
            rows=[('Penerima','12 mendekati tempo + 8 terlambat' if many else 'Wali Citra Lestari • Kelas 3'),('Jenis pesan','Pesan pribadi sesuai status tagihan masing-masing.' if many else 'Terlambat • Jemputan Oktober • Rp275.000'),('Contoh pesan','Bapak/Ibu wali Citra, mohon bayar jemputan Rp275.000 (jatuh tempo 31 Oktober). Buka tagihan di SIAPA untuk membayar melalui QRIS BSI.'),('Pengiriman',('Antrekan 20 pesan individual melalui WhatsApp API.' if many else 'Antrekan satu pesan melalui WhatsApp API.') if api else ('Siapkan 20 draf. Petugas membuka dan mengirim tiap chat.' if many else 'Buka draf WhatsApp lalu tekan Kirim di aplikasi.')),('Pelacakan','Status per wali dari penyedia: antre, terkirim, gagal.' if api else 'Petugas menandai sudah diproses setelah mengirim.')]
        side=B.box(f,24,291,316,435,'#E0F4EF' if api else '#E5EEFF',22)
        B.text(side,22,26,summary[1],17,bold=True,w=278)
        B.text(side,22,88,summary[0],37,bold=True,w=278)
        B.text(side,22,162,summary[2],15,w=266)
        B.button(side,20,353,276,'Bandingkan versi lain',style='secondary',dest=('A' if api else 'B')+str(i))
        card=B.box(f,358,291,738,435,C['white'],22)
        for n,(label,val) in enumerate(rows):
            y=24+80*n
            B.text(card,22,y,label,13,C['muted'],True,140)
            B.text(card,181,y,val,15,w=530)
            if n<4:B.line(card,22,y+56,690)
        if i in (3,4):
            B.button(f,445,740,340,'Buka tautan tagihan (contoh)',style='secondary',dest=lane+'tagihan')
        B.text(f,30,755,'DATA CONTOH   /   Prototipe pembayaran dan komunikasi wali siswa',12,C['muted'],w=400)
        B.button(f,831,741,264,'Pilih satu wali' if i==2 else ('Pilih semua 20 wali' if i==3 else ('Kembali ke penerima' if i==4 else 'Lanjutkan')),dest=lane+str(2 if i==4 else i+1))
    for j,stage in enumerate(['tagihan','bayar','status']):
        f=B.frame(lane+stage,lane+' • Portal wali / '+stage,(5+j)*1180,int(api)*910,440,810)
        B.text(f,24,26,'SIAPA   /   PORTAL WALI',13,C['green'],True,380)
        B.text(f,24,70,['Tagihan Citra','Bayar dengan QRIS','Status pembayaran'][j],28,bold=True,w=396)
        B.text(f,24,119,'Akun wali Citra Lestari  •  Kelas 3',14,C['muted'],w=388)
        hero=blue_panel(f,20,171,400,133,20,'Tagihan jemputan')
        B.text(hero,22,17,'JEMPUTAN   /   OKTOBER 2026',11,'#E2F4FF',True,355)
        B.text(hero,22,43,'Rp275.000',33,'#FFFFFF',True,355)
        B.text(hero,22,98,'Tagihan JMP-1026-106',13,'#E2F4FF',w=355)
        p=B.box(f,20,324,400,366,C['white'],20)
        if j==0:
            B.badge(p,20,20,'Terlambat','amber',130)
            for n,(label,value) in enumerate([('Jatuh tempo','31 Oktober 2026'),('Penerima','SDIT Abu Bakar'),('Metode online','QRIS BSI')]):
                B.text(p,20,93+n*68,label,14,C['muted'],w=145)
                B.text(p,165,93+n*68,value,15,bold=True,w=216)
            B.text(p,20,306,'Periksa nama siswa dan nominal sebelum membayar.',13,C['muted'],w=356)
        elif j==1:
            B.text(p,20,15,'QRIS   /   BSI',16,bold=True,w=350,align='center')
            qr=B.box(p,111,54,178,178,'#EFF5FE',15)
            # Deliberately non-payable preview symbol.
            B.icon(qr,77,55,'qrcode',C['green'],24)
            B.text(qr,8,100,'CONTOH QRIS',13,C['green'],True,162,align='center')
            B.text(p,20,249,'Pindai QRIS sekolah dari aplikasi pembayaran.',15,bold=True,w=360,align='center')
            B.text(p,20,298,'Periksa penerima dan nominal Rp275.000 sebelum konfirmasi. QRIS asli ditampilkan saat layanan aktif.',13,C['muted'],w=360)
        else:
            B.badge(p,20,22,'Lunas' if api else 'Perlu verifikasi','green' if api else 'amber',165)
            B.text(p,20,90,'Pembayaran diterima' if api else 'Menunggu pemeriksaan',23,bold=True,w=360)
            B.text(p,20,148,'Contoh hasil setelah mitra mengonfirmasi pembayaran dan referensi tagihan cocok.' if api else 'Petugas mencocokkan transaksi di aplikasi merchant BSI. Status menjadi lunas setelah dana dikonfirmasi.',15,C['muted'],w=356)
        B.button(f,20,713,400,['Lihat QRIS untuk membayar','Lihat contoh status pembayaran','Kembali ke detail tagihan'][j],dest=lane+['bayar','status','tagihan'][j])
res=run('command.batch',label='Dua alur sejajar',commands=B.steps)
def node(ref):return res[int(ref['$ref'][1:])-1]['id']
ids={k:node(v) for k,v in B.frames.items()}
run('command.batch',label='Navigasi simulasi',commands=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))) for r,d in B.links])
for k in ['A0','B0']:run('proto.flow.add',start=ids[k],name='Manual' if k=='A0' else 'Terintegrasi')
run('file.saveAs',path=str(out/'siapa-v9.figcraft'))
for k in ['A4','B4','Atagihan','Abayar','Bstatus']:run('file.export',ids=[ids[k]],format='png',scale=1,path=str(out/(k+'.png')))
run('view.zoomAt',x=4340,y=865,width=8800,height=1800)
