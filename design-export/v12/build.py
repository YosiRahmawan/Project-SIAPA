from pathlib import Path
import sys
out=Path(__file__).resolve().parent
sys.path.insert(0,str(out.parent/'v7'))
from build_v7 import B,C,run,blue_panel,students,add_student,users
run('file.new',name='SIAPA v12 • Administrasi sekolah')
run('page.rename',name='Admin • alur dari kiri ke kanan')
def screen(key,title,desc,menu):
    return B.shell(key,title,desc,len(B.frames)*1512,0,active=menu,role='Admin')
f,x,w=screen('ringkasan','Ruang administrasi','Tahun ajaran 2026/2027   •   Data contoh sekolah','Dashboard')
B.button(f,1164,110,238,'Tambah siswa','plus',dest='tambah-siswa')
p=blue_panel(f,x,203,689,228,24,'Sebaran siswa per kelas')
B.text(p,26,22,'DIREKTORI   /   SEMESTER GANJIL',11,'#CAE3FF',True,360)
B.text(p,26,58,'312',51,'#FFFFFF',True,210)
B.text(p,28,137,'siswa aktif',19,'#FFFFFF',w=220)
B.text(p,28,183,'Tahun ajaran 2026/2027',13,'#C8E3FF',w=250)
for i,n in enumerate([56,52,48,54,50,52]):
    bx=315+i*57; bh=n*1.85
    B.text(p,bx,160-bh-24,str(n),13,'#FFFFFF',True,35,align='center')
    B.box(p,bx,160-bh,35,bh,'#9BF4E5' if i==0 else '#A6CCFA',7)
    B.text(p,bx-7,174,'Kls '+str(i+1),11,'#E7F4FF',w=50,align='center')
side=B.box(f,x+707,203,w-707,228,'#DFF6EF',24)
B.text(side,22,21,'AKSES KELUARGA',11,'#255F57',True,270)
B.text(side,22,51,'300 dari 312',34,bold=True,w=335)
B.text(side,23,107,'siswa sudah memiliki akun portal',15,w=353)
B.box(side,23,153,352,9,'#B4DCD1',5); B.box(side,23,153,338,9,'#187B71',5)
B.text(side,23,181,'96,2% aktif   •   12 akun belum dibuat',13,'#245A52',True,365)
p=B.box(f,x,454,689,438)
B.text(p,24,22,'Perlu akun portal',23,bold=True,w=410)
B.badge(p,510,24,'12 siswa','amber',145)
B.text(p,24,64,'Tiga dari 12 siswa yang belum memiliki akses.',14,C['muted'],w=620)
for i,(nm,nis,kelas) in enumerate([('Eka Wulandari','2410127','5'),('Nadia Putri','2410311','2'),('Rafi Maulana','2410347','1')]):
    yy=118+76*i
    B.avatar(p,24,yy,''.join(v[0] for v in nm.split()),['#E2EEFF','#EAEBFF','#FFF0D8'][i])
    B.text(p,80,yy,nm,16,bold=True,w=270); B.text(p,80,yy+26,'NIS '+nis,12,C['muted'],w=220)
    B.text(p,375,yy+11,'Kelas '+kelas,14,w=90)
    B.badge(p,504,yy+3,'Belum dibuat','amber',150)
    B.line(p,24,yy+57,641)
B.button(p,24,356,252,'Tinjau akun Eka','arrow-right',dest='detail')
q=B.box(f,x+707,454,w-707,438)
B.text(q,24,22,'Pengelolaan sekolah',21,bold=True,w=365)
for i,(ic,title,caption,dest,col) in enumerate([('users','Direktori siswa','Identitas dan kelas','admin','#DBEAFE'),('plus','Daftarkan siswa','Tambahkan NIS baru','tambah-siswa','#DBF6EB'),('settings','Pengguna staf','Peran dan status akses','pengguna','#F1E9FF')]):
    yy=88+i*106
    row=B.box(q,16,yy,w-739,88,'#F5F8FC',14)
    tile=B.box(row,13,20,45,45,col,12); B.icon(tile,10,10,ic)
    B.text(row,72,14,title,16,bold=True,w=240); B.text(row,72,42,caption,13,C['muted'],w=250)
    B.icon(row,335,28,'arrow-right'); B.links.append((row,dest))
students('admin',1512,0,True)
B.button(B.frames['admin'],1040,987,360,'Tinjau Eka Wulandari',dest='detail')
add_student(3024,0)
def form(key,title,desc,menu,heading,fields,buttons):
    f,x,w=screen(key,title,desc,menu)
    p=B.box(f,x,200,w,710)
    B.text(p,28,26,heading,22,bold=True,w=w-56)
    accent='#DEF4EC' if menu=='Data siswa' else '#E8EBFF'
    context=B.box(p,760,99,336,376,accent,20)
    B.text(context,22,22,'KARTU IDENTITAS' if menu=='Data siswa' else 'AKSES SEKOLAH',11,C['muted'],True,292)
    B.avatar(context,22,74,'EW' if menu=='Data siswa' else 'ST')
    B.text(context,22,137,'Eka Wulandari' if menu=='Data siswa' else 'Pengguna internal',24,bold=True,w=295)
    B.text(context,22,186,'2410127   /   Kelas 5' if menu=='Data siswa' else 'Admin  /  Keuangan  /  Yayasan',14,w=292)
    B.line(context,22,236,292)
    B.text(context,22,262,'NIS menghubungkan identitas siswa dengan tagihan dan akun portal.' if menu=='Data siswa' else 'Peran menentukan menu dan tindakan yang dapat diakses staf.',15,w=288)
    for i,(label,value) in enumerate(fields): B.field(p,28,99+106*i,690,label,value)
    for i,(label,dest) in enumerate(buttons): B.button(p,28+324*i,610,300,label,style='primary' if i==0 else 'secondary',dest=dest)
form('detail','Profil siswa','Eka Wulandari   /   NIS 2410127','Data siswa','Identitas dan akses portal',[('Nama lengkap','Eka Wulandari'),('Kelas','5 • Aktif'),('Akun portal','Belum dibuat')],[('Buat akun portal','akun'),('Ubah data siswa','edit-siswa'),('Kembali ke daftar','admin')])
form('edit-siswa','Ubah data siswa','NIS 2410127 merupakan identitas tetap siswa.','Data siswa','Identitas siswa',[('Nama lengkap','Eka Wulandari'),('Kelas','5'),('Status siswa','Aktif')],[('Simpan perubahan','detail'),('Batal','detail')])
form('akun','Buat akun portal','Akun ini memberi wali akses ke tagihan dan riwayat anaknya.','Data siswa','Konfirmasi akun Eka Wulandari',[('Username dari NIS','2410127'),('Sandi awal','Dibuat sistem dan ditampilkan sekali setelah konfirmasi.')],[('Konfirmasi pembuatan','hasil'),('Kembali','detail')])
form('hasil','Akun portal berhasil dibuat','Contoh hasil pembuatan akun untuk Eka Wulandari.','Data siswa','Serahkan akses kepada wali',[('Username','2410127'),('Sandi awal • contoh simulasi','Contoh7Wali'),('Login pertama','Wali wajib mengganti sandi awal.')],[('Kembali ke ringkasan','ringkasan')])
users(len(B.frames)*1512,0)
p=B.frames['pengguna']
B.button(p,1120,111,280,'Tambah pengguna',dest='tambah-staf')
B.button(p,1020,720,380,'Kelola akun Yosi Rahma',dest='edit-staf')
form('tambah-staf','Tambah pengguna staf','Buat akses sesuai tugas pengguna di sekolah.','Pengguna','Akun baru',[('Nama lengkap','Nama staf'),('Username unik','Huruf, angka, tanda hubung atau garis bawah'),('Peran','Pilih admin, petugas keuangan, atau yayasan'),('Sandi awal','Minimal 8 karakter, mengandung huruf dan angka')],[('Buat pengguna','pengguna'),('Batal','pengguna')])
form('edit-staf','Ubah pengguna staf','Username keuangan   /   Akun Yosi Rahma','Pengguna','Peran dan status akses',[('Nama lengkap','Yosi Rahma'),('Peran','Petugas Keuangan'),('Status akun','Aktif'),('Ganti sandi • opsional','Isi jika perlu mengatur ulang sandi')],[('Simpan perubahan','pengguna'),('Batal','pengguna')])
res=run('command.batch',label='Alur administrasi',commands=B.steps)
def node(ref):return res[int(ref['$ref'][1:])-1]['id']
ids={k:node(v) for k,v in B.frames.items()}
links=[]
for r,d in B.links:
    if B.steps[int(r['$ref'][1:])-1]['params'].get('name')=='Navigasi / Dashboard': d='ringkasan'
    if d in ids:links.append(dict(command='proto.interaction.add',params=dict(id=node(r),dest=ids[d],transition=dict(type='dissolve'))))
run('command.batch',label='Navigasi admin',commands=links)
run('proto.flow.add',start=ids['ringkasan'],name='Mulai • Admin sekolah')
run('file.saveAs',path=str(out/'siapa-admin.figcraft'))
run('file.export',ids=[ids['ringkasan']],format='png',scale=1,path=str(out/'ringkasan.png'))
run('view.zoomAt',x=720,y=540,width=1540,height=1160)
print(len(ids),'layar admin')
