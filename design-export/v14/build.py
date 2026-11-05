from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run
C.update(bg='#F6F5EF',ink='#142D4A',green='#2054A3')
nav=[('ikhtisar','Ikhtisar'),('penerimaan','Penerimaan'),('rekap','Rekap'),('tunggakan','Tunggakan'),('transaksi','Transaksi'),('siswa','Per siswa'),('audit','Pembatalan')]
blue='#194888'; aqua='#9CE0D4'; gold='#F5DBA4'
def serif(p,x,y,t,size,w,color=None):
    B.node(p,'text',x,y,w,int(size*1.5),t,characters=t,fontFamily='Georgia',fontStyle='Regular',fontSize=size,fill=color or C['ink'],textAutoResize='height')
def t(p,x,y,s,z=15,w=500,b=False,c=None):B.text(p,x,y,s,z,c,b,w)
def page(key,title,sub):
    i=[k for k,_ in nav].index(key)
    f=B.frame(key,title,i*1488,0,1400,988)
    top=B.box(f,0,0,1400,76,'#FFFFFF',0)
    t(top,42,21,'SIAPA',23,99,True,blue); B.box(top,135,24,1,28,'#BDD1DE',0)
    t(top,150,28,'YAYASAN',12,100,True,blue)
    for j,(k,label) in enumerate(nav):
        b=B.box(top,268+j*129,16,124,44,'#E6EFFB' if k==key else '#FFFFFF',15)
        B.text(b,4,11,label,14,blue if k==key else C['muted'],k==key,116,align='center'); B.links.append((b,k))
    B.avatar(top,1314,18,'IN')
    t(f,48,108,'SDIT ABU BAKAR     /     BULETIN KEUANGAN     /     NOVEMBER 2026',11,1000,True,blue)
    serif(f,46,142,title,45,1280); t(f,49,214,sub,16,1270)
    B.line(f,48,263,1304,'#CAD3D7')
    t(f,48,930,'SIAPA     /     Tinjauan yayasan     •     Data contoh per 3 November 2026',12,980,c=C['muted'])
    t(f,1260,930,f'{i+1:02}   /   07',12,92,True,blue)
    return f
def panel(f,x,y,w,h,fill='#FFFFFF'):return B.box(f,x,y,w,h,fill,19)
def section(p,title,sub):
    t(p,25,22,title,20,940,True);t(p,26,62,sub,14,910,c=C['muted'])
def table(p,columns,rows):
    widths=[0,300,550,790]
    for x,v in zip(widths,columns):t(p,26+x,120,v,11,245,True,blue)
    for j,row in enumerate(rows):
        yy=161+j*65; B.line(p,24,yy-9,1012)
        for i,(x,v) in enumerate(zip(widths,row)):t(p,26+x,yy+6,v,15,270 if i==0 else 235,i==0)
run('file.new',name='SIAPA • Tinjauan Yayasan v14');run('page.rename',name='Yayasan • edisi November')
f=page('ikhtisar','Tinjauan yayasan','Posisi penerimaan sekolah dan hal yang perlu ditinjau bulan ini.')
h=panel(f,48,289,798,320,blue)
t(h,28,24,'01     /     CAPAIAN PENERIMAAN',12,560,True,'#C5E2FF')
serif(h,25,63,'77,1%',80,480,'#FFFFFF')
t(h,30,177,'Rp163,75 juta diterima dari Rp212,50 juta tagihan.',18,725,False,'#FFFFFF')
for i in range(48): B.box(h,30+i*15,234,10,34,aqua if i<37 else '#4873A8',3)
t(h,30,278,'DITERIMA',10,140,True,aqua);t(h,630,278,'SISA 22,9%',10,140,True,'#ECF4FF');B.links.append((h,'rekap'))
p=panel(f,868,289,484,320,'#F8E9CD')
t(p,25,24,'02     /     FOKUS PENGAWASAN',12,430,True,'#75501D')
serif(p,24,67,'Rp32 juta',42,430)
t(p,25,141,'Sisa SPP menyumbang sekitar dua pertiga dari seluruh tagihan yang belum terbayar.',17,424)
B.button(p,24,242,229,'Tinjau rekap tagihan',style='secondary',dest='rekap')
p=panel(f,48,632,798,250)
section(p,'Sisa tagihan per jenis','Panjang batang menunjukkan nilai relatif terhadap sisa SPP.')
for j,(lab,amt,n) in enumerate([('SPP','Rp32 juta',360),('Daftar ulang','Rp13,25 juta',149),('Jemputan','Rp3,5 juta',39)]):
    y=113+j*42;t(p,26,y,lab,14,145);B.box(p,165,y+6,n,12,[blue,'#6B9FBC','#409A8C'][j],5);t(p,582,y,amt,15,185,True)
p=panel(f,868,632,484,250,'#DDF2EC')
section(p,'Jejak pembatalan','Koreksi terakhir dalam riwayat sekolah')
t(p,26,112,'SPP Oktober  •  Rp300.000',19,432,True)
B.button(p,24,174,224,'Periksa alasan',style='secondary',dest='audit')
data=[
('penerimaan','Pola penerimaan','Bandingkan penerimaan tunai dan QRIS yang sudah terkonfirmasi.','Rp163,75 juta','Diterima selama 1–3 November','Rekap harian','Dalam juta rupiah',['TANGGAL','TUNAI','QRIS','DITERIMA'],[['1 November','28,00','18,00','46,00'],['2 November','32,00','22,00','54,00'],['3 November','38,25','25,50','63,75']],'Telusuri transaksi','transaksi'),
('rekap','Komposisi tagihan','Lihat kontribusi tiap jenis pembayaran terhadap sisa tagihan sekolah.','Rp48,75 juta','Total sisa pembayaran','Rekap menurut jenis','Dalam juta rupiah',['JENIS','TAGIHAN','DITERIMA','SISA'],[['SPP','157,25','125,25','32,00'],['Daftar ulang','38,25','25,00','13,25'],['Jemputan','17,00','13,50','3,50']],'Lihat tunggakan','tunggakan'),
('tunggakan','Tunggakan siswa','Pisahkan tagihan lewat tempo dari tagihan yang masih dalam masa pembayaran.','Rp1.455.000','Subtotal 3 tagihan contoh','Daftar lewat tempo','Urutan berdasarkan tanggal jatuh tempo',['SISWA','TAGIHAN','BATAS BAYAR','SISA'],[['Bagas Pratama','SPP Oktober','10 Oktober','Rp590.000'],['Eka Wulandari','SPP Oktober','10 Oktober','Rp590.000'],['Citra Lestari','Jemputan Oktober','31 Oktober','Rp275.000']],'Tinjau Citra Lestari','siswa'),
('transaksi','Riwayat pembayaran','Status verifikasi menentukan transaksi yang masuk ke rekap penerimaan.','2 diterima','Dari 3 contoh transaksi','Cuplikan transaksi','Pembayaran yang menunggu verifikasi belum dihitung sebagai penerimaan.',['SISWA','METODE','STATUS','NOMINAL'],[['Aisyah Rahma','Tunai','Diterima','Rp590.000'],['Bagas Pratama','QRIS','Menunggu','Rp3.500.000'],['Citra Lestari','QRIS','Diterima','Rp275.000']],'Buka profil Citra','siswa'),
('siswa','Laporan Citra Lestari','NIS 2210106    /    Kelas 3    /    Posisi per 3 November','Rp865.000','Sisa 2 tagihan siswa','Rincian kewajiban','Hanya tagihan Oktober yang telah melewati tanggal jatuh tempo.',['TAGIHAN','PERIODE','BATAS BAYAR','SISA'],[['Jemputan','Oktober','31 Oktober','Rp275.000'],['SPP','November','10 November','Rp590.000']],'Kembali ke tunggakan','tunggakan')]
for key,title,sub,value,cap,heading,note,cols,rows,action,dest in data:
    f=page(key,title,sub); p=panel(f,48,293,1304,142,'#E0EEF5')
    serif(p,26,25,value,42,595);t(p,29,98,cap,14,620)
    B.button(p,976,50,292,action,style='secondary',dest=dest)
    p=panel(f,48,464,1070,415);section(p,heading,note);table(p,cols,rows)
    rail=panel(f,1140,464,212,415,'#DDECE7');t(rail,18,23,'INDEKS EDISI',11,180,True)
    for j,(k,label) in enumerate([('ikhtisar','Ikhtisar'),('rekap','Rekap'),('audit','Pembatalan')]):B.button(rail,15,85+j*83,180,label,style='secondary',dest=k)
f=page('audit','Jejak pembatalan','Riwayat koreksi pembayaran untuk peninjauan yayasan.')
p=panel(f,48,290,510,525,'#F9E9CD');t(p,25,26,'KOREKSI   /   PEMBAYARAN TUNAI',12,440,True)
serif(p,23,88,'Rp300.000',48,458);t(p,25,177,'Dimas Saputra',23,450,True);t(p,25,226,'SPP Oktober 2026',16,440)
B.badge(p,26,306,'Dibatalkan','amber',145)
p=panel(f,583,290,769,525);section(p,'Catatan pemeriksaan','2 November 2026   •   10.24 WIB')
for j,(lab,v) in enumerate([('Dicatat oleh','Yosi Rahma • Petugas Keuangan'),('Alasan koreksi','Petugas memilih siswa yang keliru saat mencatat pembayaran tunai.'),('Dampak ke laporan','Transaksi lama tetap terlihat di riwayat. Saldo tagihan dihitung ulang.')]):
    y=132+j*108;B.box(p,27,y,8,8,blue,4);t(p,53,y-7,lab,15,630,True);t(p,53,y+25,v,15,668)
B.button(p,25,460,260,'Kembali ke ikhtisar',style='secondary',dest='ikhtisar')
res=run('command.batch',label='Edisi yayasan',commands=B.steps)
def node(r):return res[int(r['$ref'][1:])-1]['id']
ids={k:node(r) for k,r in B.frames.items()}
run('command.batch',label='Navigasi laporan',commands=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))) for r,d in B.links])
run('proto.flow.add',start=ids['ikhtisar'],name='Mulai tinjauan')
run('file.saveAs',path=str(out/'siapa-yayasan.figcraft'))
run('file.export',ids=[ids['ikhtisar']],format='png',scale=1,path=str(out/'ikhtisar.png'))
run('view.zoomAt',x=700,y=494,width=1490,height=1050)
