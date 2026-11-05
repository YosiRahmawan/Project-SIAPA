from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run
C.update(bg='#F2F6FC',ink='#142D4A',green='#2054A3')
nav=[('ikhtisar','Ikhtisar'),('penerimaan','Penerimaan'),('rekap','Rekap'),('tunggakan','Tunggakan'),('transaksi','Transaksi'),('siswa','Per siswa'),('audit','Pembatalan')]
blue='#194EC7'; aqua='#9CE0D4'; gold='#F5DBA4'
def serif(p,x,y,t,size,w,color=None):
    B.text(p,x,y,t,size,color or C['ink'],True,w)
def t(p,x,y,s,z=15,w=500,b=False,c=None):B.text(p,x,y,s,z,c,b,w)
def page(key,title,sub):
    i=[k for k,_ in nav].index(key)
    f=B.frame(key,title,i*1488,0,1400,988)
    top=B.box(f,0,0,1400,68,'#10274D',0)
    t(top,44,17,'SIAPA',24,125,True,'#FFFFFF')
    t(top,151,25,'ANALITIK YAYASAN',12,270,True,'#B8D6FF')
    t(top,1000,22,'SDIT Abu Bakar    /    Yayasan',14,282,False,'#FFFFFF');B.avatar(top,1318,15,'IN')
    bar=B.box(f,0,68,1400,62,'#FFFFFF',0)
    for j,(k,label) in enumerate(nav):
        btn=B.box(bar,44+j*162,8,154,46,'#E5EEFF' if k==key else '#FFFFFF',10)
        B.text(btn,4,12,label,14,blue if k==key else C['muted'],k==key,146,align='center');B.links.append((btn,k))
    t(f,46,162,title,32,800,True);t(f,48,217,sub,15,1030,c=C['muted'])
    badge=panel(f,1123,165,229,48,'#FFFFFF');B.icon(badge,13,12,'calendar',blue);t(badge,49,12,'1–3 November 2026',14,172,True)
    t(f,48,935,'SDIT Abu Bakar    /    Analitik yayasan    •    Data simulasi',13,1000,c=C['muted'])
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
run('file.new',name='SIAPA • Tinjauan Yayasan v15');run('page.rename',name='Yayasan • edisi November')
f=page('ikhtisar','Ringkasan keuangan','Pantau realisasi tagihan, penerimaan harian, dan transaksi yang memerlukan peninjauan.')
for i,(lab,val,sub,ic,dest) in enumerate([('Total tagihan','Rp212.500.000','SPP, daftar ulang, dan jemputan','receipt','rekap'),('Penerimaan terkonfirmasi','Rp163.750.000','77,1% dari total tagihan','check','penerimaan'),('Sisa pembayaran','Rp48.750.000','22,9% dari total tagihan','clock','rekap')]):
    p=panel(f,48+441*i,270,423,149,blue if i==1 else '#FFFFFF')
    fg='#FFFFFF' if i==1 else C['ink']
    t(p,21,17,lab,15,319,True,fg);B.icon(p,377,16,ic,fg)
    t(p,19,45,val,31,385,True,fg);t(p,22,107,sub,13,375,False,fg)
    B.links.append((p,dest))
p=panel(f,48,439,808,323);section(p,'Penerimaan harian','Total tunai dan QRIS terkonfirmasi   •   juta rupiah')
for j,n in enumerate([70,50,30,10]):
    yy=114+j*44; t(p,24,yy-8,str(n),12,36,c=C['muted']);B.line(p,64,yy,710)
pts=[(109,167),(416,149),(720,128)]
# Baseline 10 at y246; scale 2.2 px per million.
pts=[(109,167),(416,149),(720,128)]
path='M109 167 L416 149 L720 128'
B.add('node.create',type='vector',parent=p,path=path+' L720 246 L109 246 Z',select=False,props=dict(fill='#E4EFFF'))
B.add('node.create',type='vector',parent=p,path=path,select=False,props=dict(fill='none',stroke=blue,strokeWeight=4))
for i,((xx,yy),value) in enumerate(zip(pts,['46,00','54,00','63,75'])):
    B.box(p,xx-5,yy-5,10,10,blue,5);t(p,xx-20,yy-31,value,13,70,True,blue);t(p,xx-29,263,str(i+1)+' Nov',13,75)
B.links.append((p,'penerimaan'))
q=panel(f,877,439,475,323);section(q,'Sisa per jenis tagihan','Porsi dari Rp48,75 juta yang belum diterima')
for j,(lab,val,n,col) in enumerate([('SPP','Rp32 juta',277,blue),('Daftar ulang','Rp13,25 juta',115,'#418BB4'),('Jemputan','Rp3,5 juta',30,'#248B82')]):
    y=107+j*65;t(q,25,y,lab,14,195,True);B.text(q,233,y,val,14,C['ink'],True,214,align='right')
    B.box(q,25,y+32,422,7,'#EDF2F9',4);B.box(q,25,y+32,n,7,col,4)
B.links.append((q,'rekap'))
for i,(lab,sub,action,dest,col) in enumerate([('Tunggakan siswa','3 contoh tagihan lewat tempo  •  Rp1.455.000','Lihat tunggakan','tunggakan','#FFF2D9'),('Pembatalan terakhir','SPP Oktober  •  Rp300.000  •  2 November','Tinjau koreksi','audit','#E0F3EF')]):
    p=panel(f,48+i*663,782,641,116,col);t(p,22,19,lab,18,355,True);t(p,22,64,sub,13,400)
    B.button(p,443,35,176,action,style='secondary',dest=dest)
data=[
('penerimaan','Pola penerimaan','Bandingkan penerimaan tunai dan QRIS yang sudah terkonfirmasi.','Rp163,75 juta','Diterima selama 1–3 November','Rekap harian','Dalam juta rupiah',['TANGGAL','TUNAI','QRIS','DITERIMA'],[['1 November','28,00','18,00','46,00'],['2 November','32,00','22,00','54,00'],['3 November','38,25','25,50','63,75']],'Telusuri transaksi','transaksi'),
('rekap','Komposisi tagihan','Lihat kontribusi tiap jenis pembayaran terhadap sisa tagihan sekolah.','Rp48,75 juta','Total sisa pembayaran','Rekap menurut jenis','Dalam juta rupiah',['JENIS','TAGIHAN','DITERIMA','SISA'],[['SPP','157,25','125,25','32,00'],['Daftar ulang','38,25','25,00','13,25'],['Jemputan','17,00','13,50','3,50']],'Lihat tunggakan','tunggakan'),
('tunggakan','Tunggakan siswa','Pisahkan tagihan lewat tempo dari tagihan yang masih dalam masa pembayaran.','Rp1.455.000','Subtotal 3 tagihan contoh','Daftar lewat tempo','Urutan berdasarkan tanggal jatuh tempo',['SISWA','TAGIHAN','BATAS BAYAR','SISA'],[['Bagas Pratama','SPP Oktober','10 Oktober','Rp590.000'],['Eka Wulandari','SPP Oktober','10 Oktober','Rp590.000'],['Citra Lestari','Jemputan Oktober','31 Oktober','Rp275.000']],'Tinjau Citra Lestari','siswa'),
('transaksi','Riwayat pembayaran','Status verifikasi menentukan transaksi yang masuk ke rekap penerimaan.','2 diterima','Dari 3 contoh transaksi','Cuplikan transaksi','Pembayaran yang menunggu verifikasi belum dihitung sebagai penerimaan.',['SISWA','METODE','STATUS','NOMINAL'],[['Aisyah Rahma','Tunai','Diterima','Rp590.000'],['Bagas Pratama','QRIS','Menunggu','Rp3.500.000'],['Citra Lestari','QRIS','Diterima','Rp275.000']],'Buka profil Citra','siswa'),
('siswa','Laporan Citra Lestari','NIS 2210106    /    Kelas 3    /    Posisi per 3 November','Rp865.000','Sisa 2 tagihan siswa','Rincian kewajiban','Hanya tagihan Oktober yang telah melewati tanggal jatuh tempo.',['TAGIHAN','PERIODE','BATAS BAYAR','SISA'],[['Jemputan','Oktober','31 Oktober','Rp275.000'],['SPP','November','10 November','Rp590.000']],'Kembali ke tunggakan','tunggakan')]
for key,title,sub,value,cap,heading,note,cols,rows,action,dest in data:
    f=page(key,title,sub); p=panel(f,48,293,1304,142,'#DCEAFF')
    serif(p,26,25,value,42,595);t(p,29,98,cap,14,620)
    B.button(p,976,50,292,action,style='secondary',dest=dest)
    p=panel(f,48,464,1070,415);section(p,heading,note);table(p,cols,rows)
    rail=panel(f,1140,464,212,415,'#E3EDFA');t(rail,18,23,'LAPORAN TERKAIT',11,180,True)
    for j,(k,label) in enumerate([('ikhtisar','Ikhtisar'),('rekap','Rekap'),('audit','Pembatalan')]):B.button(rail,15,85+j*83,180,label,style='secondary',dest=k)
f=page('audit','Jejak pembatalan','Riwayat koreksi pembayaran untuk peninjauan yayasan.')
p=panel(f,48,290,510,525,'#F9E9CD');t(p,25,26,'KOREKSI   /   PEMBAYARAN TUNAI',12,440,True)
serif(p,23,88,'Rp300.000',48,458);t(p,25,177,'Dimas Saputra',23,450,True);t(p,25,226,'SPP Oktober 2026',16,440)
B.badge(p,26,306,'Dibatalkan','amber',145)
p=panel(f,583,290,769,525);section(p,'Catatan pemeriksaan','2 November 2026   •   10.24 WIB')
for j,(lab,v) in enumerate([('Dicatat oleh','Yosi Rahma • Petugas Keuangan'),('Alasan koreksi','Petugas memilih siswa yang keliru saat mencatat pembayaran tunai.'),('Dampak ke laporan','Transaksi lama tetap terlihat di riwayat. Saldo tagihan dihitung ulang.')]):
    y=132+j*108;B.box(p,27,y,8,8,blue,4);t(p,53,y-7,lab,15,630,True);t(p,53,y+25,v,15,668)
B.button(p,25,460,260,'Kembali ke ikhtisar',style='secondary',dest='ikhtisar')
res=run('command.batch',label='Analitik yayasan',commands=B.steps)
def node(r):return res[int(r['$ref'][1:])-1]['id']
ids={k:node(r) for k,r in B.frames.items()}
run('command.batch',label='Navigasi laporan',commands=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))) for r,d in B.links])
run('proto.flow.add',start=ids['ikhtisar'],name='Mulai tinjauan')
run('file.saveAs',path=str(out/'siapa-yayasan.figcraft'))
run('file.export',ids=[ids['ikhtisar']],format='png',scale=1,path=str(out/'ikhtisar.png'))
run('view.zoomAt',x=700,y=494,width=1490,height=1050)
