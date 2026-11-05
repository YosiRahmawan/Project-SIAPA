from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run,blue_panel
titles=['Pembayaran tunai','Pembayaran QRIS BSI','Pilih penerima','Pengingat satuan','Pengingat massal']
run('file.new',name='SIAPA v8 • Pembayaran dan pengingat')
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
            rows=[('Penerima','12 mendekati tempo + 8 terlambat' if many else 'Wali Citra Lestari • Kelas 3'),('Jenis pesan','Pesan pribadi sesuai status tagihan masing-masing.' if many else 'Terlambat • Jemputan Oktober • Rp275.000'),('Contoh pesan','Bapak/Ibu wali Citra, tagihan jemputan Rp275.000 telah melewati batas 31 Oktober.'),('Pengiriman',('Antrekan 20 pesan individual melalui WhatsApp API.' if many else 'Antrekan satu pesan melalui WhatsApp API.') if api else ('Siapkan 20 draf. Petugas membuka dan mengirim tiap chat.' if many else 'Buka draf WhatsApp lalu tekan Kirim di aplikasi.')),('Pelacakan','Status per wali dari penyedia: antre, terkirim, gagal.' if api else 'Petugas menandai sudah diproses setelah mengirim.')]
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
        B.text(f,30,755,'DATA CONTOH   /   Prototipe pembayaran dan komunikasi wali siswa',12,C['muted'],w=710)
        B.button(f,831,741,264,'Pilih satu wali' if i==2 else ('Pilih semua 20 wali' if i==3 else ('Kembali ke penerima' if i==4 else 'Lanjutkan')),dest=lane+str(2 if i==4 else i+1))
res=run('command.batch',label='Dua alur sejajar',commands=B.steps)
def node(ref):return res[int(ref['$ref'][1:])-1]['id']
ids={k:node(v) for k,v in B.frames.items()}
run('command.batch',label='Navigasi simulasi',commands=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))) for r,d in B.links])
for k in ['A0','B0']:run('proto.flow.add',start=ids[k],name='Manual' if k=='A0' else 'Terintegrasi')
run('file.saveAs',path=str(out/'siapa-v8.figcraft'))
for k in ['A4','B4']:run('file.export',ids=[ids[k]],format='png',scale=1,path=str(out/(k+'.png')))
run('view.zoomAt',x=2920,y=865,width=5900,height=1800)
