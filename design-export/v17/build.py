from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run
run('file.new',name='SIAPA • Login bersama');run('page.rename',name='Masuk & alur akun')
f=B.frame('login','Masuk • Semua pengguna',0,0,1440,900)
p=B.box(f,44,44,1352,812,'#FFFFFF',28,'#BED2EB')
B.add('image.place',parent=p,path=str(out.parent/'logo'/'siapa-konsep-01.png'),x=57,y=37,width=199,height=69,mode='fit',name='Logo SIAPA')
B.text(p,66,167,'PORTAL PEMBAYARAN SEKOLAH',12,C['green'],True,415)
B.text(p,64,202,'Selamat datang',40,bold=True,w=455)
B.text(p,66,275,'Masuk dengan akun sekolah untuk melanjutkan.',16,C['muted'],w=450)
B.field(p,66,350,428,'Username atau NIS','Masukkan username atau NIS')
B.field(p,66,456,428,'Kata sandi','Masukkan kata sandi')
B.button(p,66,569,428,'Masuk',dest='alur')
B.text(p,66,645,'Siswa dan wali menggunakan NIS siswa. Jika kesulitan masuk, hubungi admin sekolah.',14,C['muted'],w=417)
B.text(p,66,759,'SDIT Abu Bakar Ash Shiddiq Rajapolah',12,C['muted'],w=480)
art=B.box(p,550,20,782,772,'#E6EEFE',22)
B.text(art,36,47,'SIAPA   /   LAYANAN SEKOLAH',12,C['green'],True,690)
B.text(art,34,94,'Keuangan sekolah dalam satu portal.',32,bold=True,w=713)
B.add('image.place',parent=art,path=str(out/'ilustrasi-login.png'),x=0,y=185,width=782,height=587,mode='fit',name='Ilustrasi sekolah dan keluarga')
g=B.frame('alur','Logika masuk • Penjelasan prototipe',1520,0,1440,900)
B.text(g,54,51,'Alur setelah masuk',35,bold=True,w=1000)
B.text(g,56,122,'Sistem memeriksa username, kata sandi, dan status aktif. Akun yang gagal diverifikasi kembali ke form dengan pesan kesalahan.',17,C['muted'],w=1240)
p=B.box(g,54,218,1332,101,C['mint'],18)
B.text(p,24,16,'Akun dengan kata sandi awal',19,bold=True,w=1000)
B.text(p,24,55,'Selesaikan penggantian kata sandi sebelum melanjutkan ke halaman sesuai role.',15,w=1180)
for i,(role,dest) in enumerate([('Admin','Pengguna'),('Petugas keuangan','Laporan tunggakan'),('Siswa / wali','Tagihan siswa'),('Yayasan','Rekap laporan')]):
    y=365+i*80;p=B.box(g,54,y,1332,66,'#FFFFFF',12);B.text(p,24,18,role,17,bold=True,w=390);B.text(p,464,18,dest,17,w=660)
B.button(g,54,737,290,'Kembali ke login',style='secondary',dest='login')
res=run('command.batch',label='Login SIAPA',commands=B.steps)
def node(ref):return res[int(ref['$ref'][1:])-1]['id']
ids={k:node(v) for k,v in B.frames.items()}
run('command.batch',label='Alur autentikasi',commands=[dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[k],transition=dict(type='dissolve'))) for r,k in B.links])
run('proto.flow.add',start=ids['login'],name='Masuk ke SIAPA')
run('file.saveAs',path=str(out/'siapa-login.figcraft'))
run('file.export',ids=[ids['login']],format='png',scale=1,path=str(out/'login.png'))
