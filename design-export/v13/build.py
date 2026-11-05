from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run,blue_panel,dashboard,receipts,report,cancelled
role=sys.argv[1]
run('file.new',name='SIAPA v13 • '+role.capitalize())
run('page.rename',name=role.capitalize()+' • alur dari kiri')
if role=='siswa':
    pages=[
    ('beranda','Portal keluarga','Rp865.000','Total dua tagihan terbuka','Tagihan anak',[('SPP November','Rp590.000  •  Jatuh tempo 10 November'),('Jemputan Oktober','Rp275.000  •  Lewat jatuh tempo 31 Oktober')],'Lihat pengingat','peringatan'),
    ('peringatan','Pengingat pembayaran','1 terlambat','Per 7 November 2026','Pesan untuk wali Citra',[('Jemputan Oktober','Mohon lunasi Rp275.000 melalui halaman pembayaran SIAPA.'),('Mendekati jatuh tempo','SPP November jatuh tempo dalam 3 hari.')],'Buka tagihan jemputan','detail'),
    ('detail','Detail tagihan','Rp275.000','Jemputan Oktober 2026','Rincian untuk Citra',[('Status • Terlambat','Batas pembayaran 31 Oktober 2026'),('Referensi tagihan','JMP-1026-106  •  SDIT Abu Bakar')],'Bayar dengan QRIS BSI','qris'),
    ('qris','Pembayaran QRIS','Rp275.000','Penerima • SDIT Abu Bakar','QRIS BSI merchant sekolah',[],'Lihat status pembayaran','status'),
    ('status','Status pembayaran','Rp275.000','JMP-1026-106','Menunggu konfirmasi',[('QRIS sekolah • manual','Petugas mencocokkan transaksi merchant sebelum mengubah status menjadi lunas.'),('Jika terhubung ke mitra','Status lunas setelah referensi dan nominal terkonfirmasi.')],'Buka riwayat','riwayat'),
    ('riwayat','Riwayat pembayaran','Rp865.000','Total dua pembayaran sebelumnya','Pembayaran diterima',[('SPP Oktober • Tunai','Rp590.000  •  3 Oktober 2026'),('Jemputan September • QRIS','Rp275.000  •  30 September 2026')],'Kembali ke beranda','beranda')]
    for i,(key,title,amount,desc,heading,rows,cta,dest) in enumerate(pages):
        f=B.frame(key,title,500*i,0,440,880)
        B.text(f,22,22,'SIAPA    /    PORTAL SISWA',12,C['green'],True,310); B.avatar(f,369,16,'CL')
        B.text(f,22,76,title,27,bold=True,w=395)
        B.text(f,22,121,'Citra Lestari   /   Kelas 3   /   NIS 2210106',14,C['muted'],w=398)
        h=blue_panel(f,20,175,400,160,23,'Ringkasan')
        B.text(h,21,19,desc,13,'#E2F5FF',w=360); B.text(h,21,56,amount,38,'#FFFFFF',True,357)
        B.text(h,22,123,'TAHUN AJARAN   2026 / 2027',10,'#D7EBFF',True,356)
        p=B.box(f,20,356,400,332,C['white'],20)
        B.text(p,19,20,heading,20,bold=True,w=362)
        for j,(name,sub) in enumerate(rows):
            y=76+116*j
            row=B.box(p,14,y,372,102,['#E9F5FF','#FFF2DF'][j],13)
            B.text(row,13,12,name,15,bold=True,w=346); B.text(row,13,45,sub,13,w=343)
        if key=='qris':
            tile=B.box(p,95,65,210,165,'#EEF5FF',12); B.icon(tile,94,50,'qrcode')
            B.text(tile,12,98,'SIMULASI QRIS',13,C['green'],True,186,align='center')
            B.text(p,20,253,'Pindai QRIS sekolah di aplikasi pembayaran. Pastikan penerima dan nominal sesuai.',14,w=361)
        B.button(f,20,707,400,cta,dest=dest)
        for n,(label,k) in enumerate([('Tagihan','beranda'),('Pengingat','peringatan'),('Riwayat','riwayat')]):B.button(f,20+n*136,790,128,label,style='secondary',dest=k)
    start='beranda'
else:
    dashboard('yayasan',0,0,role='Yayasan')
    receipts('rincian-yayasan',1512,0,'Yayasan')
    report(3024,0)
    tables=[
    ('tunggakan','Tunggakan siswa','Tagihan yang melewati tenggat dan masih bersisa.','Rp1.455.000','Subtotal tiga tagihan contoh',[
    ('Citra Lestari','Jemputan Oktober','31 Okt','Rp275.000'),('Bagas Pratama','SPP Oktober','10 Okt','Rp590.000'),('Eka Wulandari','SPP Oktober','10 Okt','Rp590.000')],['SISWA','TAGIHAN','BATAS BAYAR','SISA']),
    ('transaksi','Daftar pembayaran','Penerimaan dan transaksi yang menunggu konfirmasi.','3 transaksi','Cuplikan aktivitas pembayaran',[
    ('Aisyah Rahma','SPP November','Tunai • diterima','Rp590.000'),('Bagas Pratama','Daftar ulang','QRIS • menunggu','Rp3.500.000'),('Citra Lestari','Jemputan September','QRIS • diterima','Rp275.000')],['SISWA','TAGIHAN','METODE / STATUS','NOMINAL']),
    ('siswa','Laporan per siswa','Citra Lestari   /   NIS 2210106   /   Kelas 3','Rp865.000','Sisa dua tagihan per 7 November',[
    ('SPP November','10 November','Belum lunas','Rp590.000'),('Jemputan Oktober','31 Oktober','Terlambat','Rp275.000')],['TAGIHAN','BATAS BAYAR','STATUS','SISA'])]
    for i,(key,title,sub,val,lab,rows,cols) in enumerate(tables):
        f,x,w=B.shell(key,title,sub,(i+3)*1512,0,active='Laporan',role='Yayasan')
        h=blue_panel(f,x,202,w,154,20,'Ikhtisar'); B.text(h,25,20,lab,16,'#DFEDFF',w=620); B.text(h,25,58,val,37,'#FFFFFF',True,620)
        p=B.box(f,x,380,w,402); B.text(p,24,21,'Rincian laporan',21,bold=True,w=640)
        for n,c in enumerate(cols):B.text(p,24+270*n,85,c,11,C['muted'],True,250)
        for j,row in enumerate(rows):
            yy=137+j*76; B.line(p,24,yy-15,w-48)
            for n,v in enumerate(row):B.text(p,24+270*n,yy,v,15,bold=n in (0,3),w=248)
        B.button(f,x,810,290,'Lihat laporan Citra' if key!='siswa' else 'Kembali ke rekap',dest='siswa' if key!='siswa' else 'laporan')
    for i,(label,key) in enumerate([('Tunggakan','tunggakan'),('Daftar pembayaran','transaksi'),('Laporan per siswa','siswa')]):B.button(B.frames['laporan'],280+i*324,953,300,label,dest=key)
    cancelled(9072,0)
    start='yayasan'
res=run('command.batch',label='Layar '+role,commands=B.steps)
def node(r):return res[int(r['$ref'][1:])-1]['id']
ids={k:node(r) for k,r in B.frames.items()}
links=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))) for r,d in B.links if d in ids]
run('command.batch',label='Navigasi '+role,commands=links)
run('proto.flow.add',start=ids[start],name='Mulai • '+role)
run('file.saveAs',path=str(out/('siapa-'+role+'.figcraft')))
run('file.export',ids=[ids[start]],format='png',scale=1,path=str(out/(role+'.png')))
print(role,len(ids),len(links))
