"""Editable FigCraft concept, built through FigCraft's documented command API."""
import json
import socket
import math
import pathlib
import urllib.request
import xml.etree.ElementTree as ET

ROOT = pathlib.Path(__file__).resolve().parent
C = dict(bg='#F4F7F6', white='#FFFFFF', ink='#18343B', muted='#61767A', line='#E3EBE8',
         side='#122D34', side2='#1D3C43', green='#166B5B', mint='#E7F3ED', lime='#D7F57A',
         blue='#306BC5', pale='#EDF3FD', amber='#875518', sand='#FFF3DB', red='#AF443D', rose='#FCEDE9')
ICONS = ['layout-sidebar','layout-dashboard','receipt','users','chart-bar','settings','logout',
         'search','bell','plus','arrow-up-right','arrow-right','chevron-down','chevron-left',
         'check','x','clock','download','calendar','credit-card','qrcode','file-text','alert-circle','eye','menu-2']

def download_icons():
    from concurrent.futures import ThreadPoolExecutor
    def get(name):
        p = ROOT / 'assets' / (name + '.svg')
        if not p.exists():
            url = 'https://raw.githubusercontent.com/tabler/tabler-icons/main/icons/outline/' + name + '.svg'
            p.write_bytes(urllib.request.urlopen(url, timeout=30).read())
    with ThreadPoolExecutor(max_workers=6) as pool:
        list(pool.map(get, ICONS))

def rpc(method, params):
    with socket.create_connection(('127.0.0.1',7979),timeout=20) as s:
        s.settimeout(180)
        s.sendall((json.dumps(dict(id=1,method=method,params=params,timeoutMs=180000))+'\n').encode())
        f = s.makefile('r',encoding='utf-8')
        r = json.loads(f.readline())
        if not r.get('ok'): raise RuntimeError(r)
        return r['result']

def run(command, **params):
    return rpc('engine.execute',dict(command=command,params=params,agent='Codex'))

class Board:
    def __init__(self): self.steps=[]; self.count=0; self.links=[]; self.frames={}; self.hits={}
    def add(self,command,**params):
        self.count+=1; name='n'+str(self.count)
        self.steps.append(dict(command=command,params=params,**{'as':name}))
        return {'$ref':name}
    def node(self,p,t,x,y,w,h,name='',**props):
        return self.add('node.create',type=t,parent=p,x=x,y=y,width=w,height=h,name=name or t,props=props,select=False)
    def box(self,p,x,y,w,h,fill=None,r=18,stroke=None,name='Panel'):
        pr=dict(fill=fill or C['white'],cornerRadius=r)
        if stroke: pr.update(stroke=stroke,strokeWeight=1)
        return self.node(p,'frame',x,y,w,h,name,**pr)
    def text(self,p,x,y,txt,size=14,color=None,bold=False,w=400,h=None,align='left',name=None):
        return self.node(p,'text',x,y,w,h or math.ceil(size*1.55),name or txt[:45],characters=txt,
                         fontFamily='Inter',fontStyle='Semi Bold' if bold else 'Regular',fontSize=size,
                         lineHeight='145%',fill=color or C['ink'],textAutoResize='height',textAlign=align,
                         features={'tnum':True})
    def line(self,p,x,y,w,color=None): self.box(p,x,y,w,1,color or C['line'],0)
    def icon(self,p,x,y,name,color=None,size=24):
        f=self.node(p,'frame',x,y,size,size,'Tabler / '+name,fill='none')
        root=ET.parse(ROOT/'assets'/(name+'.svg')).getroot()
        for el in root:
            tag=el.tag.split('}')[-1]; a=el.attrib; d=''
            if a.get('stroke')=='none': continue
            if tag=='path': d=a.get('d','')
            elif tag=='line': d=f"M{a['x1']} {a['y1']} L{a['x2']} {a['y2']}"
            elif tag in ('polyline','polygon'): d='M'+a['points']+(' Z' if tag=='polygon' else '')
            elif tag=='rect':
                xx=float(a['x']); yy=float(a['y']); ww=float(a['width']); hh=float(a['height'])
                d=f'M{xx} {yy} h{ww} v{hh} h{-ww} Z'
            elif tag=='circle':
                xx=float(a['cx']); yy=float(a['cy']); rr=float(a['r'])
                d=f'M{xx-rr} {yy} a{rr} {rr} 0 1 0 {rr*2} 0 a{rr} {rr} 0 1 0 {-rr*2} 0'
            if d:
                # SVG coordinates remain intact; the parent scales from the 24px source.
                import re
                if size!=24:
                    d=re.sub(r'[-+]?(?:\d*\.\d+|\d+)',lambda m:str(round(float(m.group())*size/24,4)),d)
                    # Icons are normally 24px to retain SVG arc flags. Use a 24px parent below instead.
                self.add('node.create',type='vector',parent=f,path=d,name='Tabler stroke',select=False,
                         props=dict(fill='none',stroke=color or C['muted'],strokeWeight=1.7,strokeCap='round',strokeJoin='round'))
        return f
    def button(self,p,x,y,w,label,icon=None,style='primary',dest=None):
        bg,fg={'primary':(C['green'],C['white']),'lime':(C['lime'],C['ink']),
               'secondary':(C['white'],C['ink']),'soft':(C['mint'],C['green']),
               'danger':(C['rose'],C['red'])}[style]
        b=self.box(p,x,y,w,44,bg,12,C['line'] if style=='secondary' else None,label)
        if icon: self.icon(b,14,10,icon,fg,24)
        self.text(b,44 if icon else 8,11,label,14,fg,True,w-52 if icon else w-16,align='left' if icon else 'center')
        if dest: self.links.append((b,dest))
        return b
    def badge(self,p,x,y,label,tone='green',w=112):
        bg,fg={'green':('mint','green'),'amber':('sand','amber'),'red':('rose','red'),'blue':('pale','blue'),'gray':('bg','muted')}[tone]
        b=self.box(p,x,y,w,28,C[bg],14)
        self.text(b,8,4,label,12,C[fg],True,w-16,align='center')
        return b
    def avatar(self,p,x,y,initials,fill=None):
        b=self.box(p,x,y,36,36,fill or C['mint'],12)
        self.text(b,0,8,initials,12,C['green'],True,36,align='center')
    def field(self,p,x,y,w,label,value,helper=None):
        self.text(p,x,y,label,13,bold=True,w=w)
        b=self.box(p,x,y+28,w,48,C['white'],10,C['line'])
        self.text(b,14,13,value,14,C['muted'],w=w-28)
        if helper: self.text(p,x,y+83,helper,12,C['muted'],w=w)
        return b
    def frame(self,key,name,x,y,w=1440,h=1080):
        f=self.node(None,'frame',x,y,w,h,name,fill=C['bg'],clip=True,cornerRadius=0)
        self.frames[key]=f
        return f
    def shell(self,key,title,subtitle,x,y,active='Dashboard',role='Petugas Keuangan',collapsed=False,h=1080):
        f=self.frame(key,title+' / '+('sidebar ringkas' if collapsed else 'sidebar terbuka'),x,y,h=h)
        sw=80 if collapsed else 240
        sb=self.box(f,0,0,sw,h,C['side'],0,name='Sidebar')
        toggle=self.box(sb,18 if collapsed else 180,24,44,44,C['side2'],12,name='Buka / ciutkan sidebar')
        self.icon(toggle,10,10,'layout-sidebar','#D3E4DF',24)
        if not collapsed:
            self.text(sb,28,27,'SIAPA',24,C['white'],True,138)
            self.box(sb,28,65,29,4,C['lime'],2)
            self.text(sb,26,106,'PEMBAYARAN SEKOLAH',10,'#A9C2BF',True,190)
        menus={'Petugas Keuangan':[('Dashboard','layout-dashboard','dashboard'),('Pembayaran','credit-card','input'),('Tagihan','receipt','tagihan'),('Verifikasi QRIS','qrcode','verifikasi'),('Tunggakan','alert-circle','tunggakan'),('Data siswa','users','siswa'),('Laporan','chart-bar','laporan')],
               'Admin':[('Dashboard','layout-dashboard','admin'),('Data siswa','users','admin'),('Pengguna','users','pengguna')],
               'Yayasan':[('Dashboard','layout-dashboard','yayasan'),('Laporan','chart-bar','laporan'),('Pembatalan','alert-circle','pembatalan')]}
        for i,(label,ic,dest) in enumerate(menus[role]):
            yy=145+i*56
            on=label==active
            b=self.box(sb,14,yy,52 if collapsed else 212,46,C['lime'] if on else C['side'],12,name='Navigasi / '+label)
            self.icon(b,14,11,ic,C['ink'] if on else '#ADC5C2',24)
            if not collapsed:
                self.text(b,48,13,label,14,C['ink'] if on else '#D5E5E1',on,154)
            self.links.append((b,'laporan-keuangan' if dest=='laporan' and role=='Petugas Keuangan' else dest))
        if not collapsed:
            self.line(sb,24,h-184,192,C['side2'])
            self.text(sb,26,h-165,'SDIT Abu Bakar',13,'#E6F0ED',True,190)
            self.text(sb,26,h-143,'Tahun ajaran 2026/2027',12,'#A9C2BF',w=190)
        out=self.box(sb,14,h-70,52 if collapsed else 212,46,C['side2'],12,name='Keluar')
        self.icon(out,14,11,'logout','#C8DBD6',24)
        if not collapsed: self.text(out,48,13,'Keluar',14,'#E6F0ED',w=145)
        self.links.append((out,'login'))
        hd=self.box(f,sw,0,1440-sw,80,C['white'],0,name='Header')
        self.icon(hd,32,29,'search',size=24)
        self.text(hd,68,30,'Cari nama siswa atau NIS...',14,C['muted'],w=300)
        self.badge(hd,1440-sw-410,26,'TA 2026/2027','gray',140)
        nt=self.box(hd,1440-sw-248,18,44,44,C['bg'],14)
        self.icon(nt,10,10,'bell',size=24)
        self.avatar(hd,1440-sw-180,22,'YR' if role=='Petugas Keuangan' else ('AS' if role=='Admin' else 'IN'))
        self.text(hd,1440-sw-132,20,'Yosi Rahma' if role=='Petugas Keuangan' else ('Admin Sekolah' if role=='Admin' else 'Ibu Nurhayati'),13,bold=True,w=128)
        self.text(hd,1440-sw-132,41,role,11,C['muted'],w=128)
        cx=sw+36; cw=1440-sw-72
        self.text(f,cx,108,title,28,bold=True,w=600)
        self.text(f,cx,151,subtitle,14,C['muted'],w=700)
        self.hits[key]={'toggle':toggle,'x':cx,'w':cw}
        return f,cx,cw

B=Board()

def chart(p,x,y,w,h):
    B.text(p,x,y,'Penerimaan bulanan',18,bold=True,w=w)
    B.text(p,x,y+29,'Juni - November 2026, dalam juta rupiah',12,C['muted'],w=w)
    base=y+h-35
    for n in range(5):
        yy=base-n*(h-100)/4
        B.line(p,x+36,yy,w-50)
        B.text(p,x,yy-8,str(n*50),11,C['muted'],w=30)
    for i,(m,v) in enumerate(zip(['Jun','Jul','Agu','Sep','Okt','Nov'],[142,168,151,176,189,163.75])):
        bx=x+65+i*(w-90)/6; bh=v/200*(h-100)
        B.box(p,bx,base-bh,42,bh,C['green'] if i==5 else '#C9DFD5',8)
        B.text(p,bx-10,base+9,m,12,C['ink'] if i==5 else C['muted'],i==5,62,align='center')
        B.text(p,bx-15,base-bh-25,str(v).replace('.',','),11,C['green'] if i==5 else C['muted'],i==5,72,align='center')

def dashboard(key,x,y,collapsed=False,role='Petugas Keuangan'):
    f,cx,cw=B.shell(key,'Ringkasan keuangan','Selasa, 3 November 2026. Semua penerimaan sekolah dalam satu tempat.',x,y,collapsed=collapsed,role=role,h=1160)
    B.button(f,1440-220,114,184,'Input pembayaran','plus',dest='input') if role=='Petugas Keuangan' else B.button(f,1440-220,114,184,'Lihat laporan','chart-bar',dest='laporan')
    left=round(cw*.60); right=cw-left-24
    hero=B.box(f,cx,202,left,224,C['side'],24,name='Penerimaan bulan ini')
    B.text(hero,28,25,'Penerimaan bulan ini',14,'#C4DBD5',w=360)
    B.text(hero,28,58,'Rp163.750.000',38,C['white'],True,w=left-48)
    B.badge(hero,28,124,'77,1% terkumpul','green',142)
    B.text(hero,28,175,'Dari total tagihan Rp212.500.000',13,'#C4DBD5',w=left-50)
    B.box(hero,left-110,22,78,32,C['lime'],16)
    B.text(hero,left-105,29,'NOV 2026',10,C['ink'],True,68,align='center')
    xx=cx+left+24
    for yy,label,amount,detail,bg,fg,ic in [(202,'Sisa tagihan','Rp48.750.000','37 siswa belum lunas',C['white'],C['ink'],'receipt'),(326,'Menunggu verifikasi','12 pembayaran','Bukti QRIS perlu diperiksa',C['mint'],C['green'],'qrcode')]:
        c=B.box(f,xx,yy,right,100,bg,20)
        B.text(c,22,15,label,13,C['muted'],w=right-80)
        B.text(c,22,39,amount,24,fg,True,w=right-40)
        B.text(c,22,74,detail,11,C['muted'],w=right-40)
        B.icon(c,right-46,17,ic,C['green'],24)
        if ic=='qrcode': B.links.append((c,'verifikasi' if role=='Petugas Keuangan' else 'laporan'))
    alert=B.box(f,cx,450,cw,60,C['sand'],14)
    B.icon(alert,18,18,'calendar',C['amber'],24)
    B.text(alert,56,12,'Jatuh tempo berikutnya: 10 November',14,C['amber'],True,w=cw-240)
    B.text(alert,56,34,'Periksa tagihan yang belum lunas sebelum jatuh tempo.',12,C['amber'],w=cw-240)
    B.button(alert,cw-170,8,154,'Lihat tagihan' if role=='Petugas Keuangan' else 'Lihat laporan',style='secondary',dest='tagihan' if role=='Petugas Keuangan' else 'laporan')
    ch=B.box(f,cx,534,left,290)
    chart(ch,24,20,left-48,245)
    mix=B.box(f,xx,534,right,290)
    B.text(mix,24,21,'Komposisi tagihan',18,bold=True,w=right-48)
    B.text(mix,24,51,'Berdasarkan nominal November',12,C['muted'],w=right-48)
    widths=[.74,.18,.08]; colors=[C['green'],'#A5C65D',C['blue']]
    off=24
    for n,col in zip(widths,colors):
        seg=(right-48)*n
        B.box(mix,off,92,seg-3,24,col,5); off+=seg
    for i,(label,amt,pct,col) in enumerate(zip(['SPP','Daftar ulang','Jemputan'],['Rp157.250.000','Rp38.250.000','Rp17.000.000'],['74%','18%','8%'],colors)):
        yy=143+i*43
        B.box(mix,24,yy+5,8,8,col,3)
        B.text(mix,44,yy,label,13,w=125)
        B.text(mix,right-188,yy,amt,13,bold=True,w=135,align='right')
        B.text(mix,right-49,yy,pct,11,C['muted'],w=36,align='right')
    table=B.box(f,cx,848,cw,268)
    B.text(table,24,20,'Pembayaran terbaru',18,bold=True)
    B.button(table,cw-166,12,142,'Lihat semua',style='secondary',dest='laporan-keuangan' if role=='Petugas Keuangan' else 'laporan')
    cols=[24,350,520,690,cw-160]
    for xx2,lab in zip(cols,['SISWA','TAGIHAN','METODE','JUMLAH','STATUS']): B.text(table,xx2,68,lab,10,C['muted'],True,w=145)
    rows=[('Aisyah Rahma','2410123 / Kelas 1','SPP November','Tunai','Rp590.000','Diterima','green'),('Bagas Pratama','2310148 / Kelas 2','Daftar ulang','QRIS','Rp3.500.000','Menunggu','blue'),('Citra Lestari','2210106 / Kelas 3','Jemputan November','Tunai','Rp275.000','Diterima','green')]
    for i,(name,sub,bill,method,amt,status,tone) in enumerate(rows):
        yy=98+i*53; B.line(table,24,yy-9,cw-48)
        B.avatar(table,24,yy,''.join(n[0] for n in name.split())[:2])
        B.text(table,72,yy,name,13,bold=True,w=250); B.text(table,72,yy+21,sub,11,C['muted'],w=245)
        B.text(table,350,yy+8,bill,12,w=170); B.text(table,520,yy+8,method,12,w=100)
        B.text(table,620,yy+8,amt,13,bold=True,w=175,align='right')
        B.badge(table,cw-160,yy+3,status,tone,130)
    return f

def bills(key,x,y,arrears=False):
    f,cx,cw=B.shell(key,'Tunggakan' if arrears else 'Tagihan siswa','Pantau sisa pembayaran dan temukan siswa dengan cepat.',x,y,active='Tunggakan' if arrears else 'Tagihan')
    B.button(f,1440-225,112,189,'Buat tagihan','plus',dest='buat-tagihan')
    stats=[('Tagihan menunggak','87','red'),('Total sisa','Rp48.750.000','red'),('Siswa terdampak','37','amber')] if arrears else [('Total tagihan','361','gray'),('Sudah lunas','274','green'),('Belum lunas','87','amber')]
    for i,(label,num,tone) in enumerate(stats):
        c=B.box(f,cx+i*(cw+20)/3,202,(cw-40)/3,110)
        B.text(c,22,18,label,13,C['muted']); B.text(c,22,46,num,30,bold=True)
    panel=B.box(f,cx,336,cw,660)
    for i,(label,w) in enumerate([('Semua tagihan',154),('Belum lunas',142),('Lunas',100)]):
        B.button(panel,24+i*165,20,w,label,style='soft' if i==(1 if arrears else 0) else 'secondary')
    B.field(panel,24,88,390,'Cari siswa','Nama atau NIS')
    B.field(panel,438,88,190,'Kelas','Semua kelas')
    B.field(panel,652,88,190,'Jenis tagihan','Semua jenis')
    B.field(panel,866,88,210,'Periode','Semua periode' if arrears else 'November 2026')
    yy=212
    for xx,lab in [(24,'SISWA'),(350,'JENIS / PERIODE'),(550,'NOMINAL'),(730,'SISA'),(900,'STATUS')]: B.text(panel,xx,yy,lab,11,C['muted'],True,w=175)
    names=['Aisyah Rahma','Bagas Pratama','Citra Lestari','Dimas Saputra','Eka Wulandari','Fajar Nugroho']
    for i,name in enumerate(names):
        yy=254+i*56; B.line(panel,24,yy-10,cw-48)
        B.avatar(panel,24,yy,''.join(n[0] for n in name.split()))
        B.text(panel,73,yy,name,13,bold=True,w=250); B.text(panel,73,yy+22,f'{2410123+i} / Kelas {i+1}',11,C['muted'],w=250)
        B.text(panel,350,yy+2,'SPP',13,w=180); B.text(panel,350,yy+23,'Oktober 2026' if arrears else 'November 2026',11,C['muted'],w=180)
        amt='Rp590.000' if i==0 else ('Rp550.000' if i==1 else 'Rp540.000')
        B.text(panel,550,yy+10,amt,13,bold=True,w=130)
        B.text(panel,730,yy+10,'Rp240.000' if i==3 else amt,13,bold=True,w=150)
        B.badge(panel,900,yy+4,'Menunggak' if arrears else ('Verifikasi' if i==1 else 'Belum lunas'),'red' if arrears else ('blue' if i==1 else 'amber'),135)
        hot=B.box(panel,cw-52,yy,32,36,C['white'],8); B.icon(hot,4,6,'arrow-right',size=24); B.links.append((hot,'input'))
    B.text(panel,24,611,'Menampilkan 1-6 dari 87 tagihan belum lunas',12,C['muted'],w=550)

def payment(x,y):
    f,cx,cw=B.shell('input','Input pembayaran','Cari siswa, pilih tagihan, lalu catat pembayaran.',x,y,active='Pembayaran')
    left=round(cw*.62); right=cw-left-24
    form=B.box(f,cx,202,left,786)
    B.text(form,26,24,'Pembayaran baru',20,bold=True,w=left-52)
    B.field(form,26,79,left-52,'Cari siswa dengan NIS','2410123', 'Nama siswa akan tampil setelah NIS ditemukan.')
    s=B.box(form,26,201,left-52,80,C['mint'],14); B.avatar(s,16,20,'AR')
    B.text(s,66,15,'Aisyah Rahma',15,bold=True,w=left-150)
    B.text(s,66,42,'NIS 2410123 / Kelas 1 / Aktif',12,C['muted'],w=left-150)
    B.text(form,26,308,'Pilih tagihan',13,bold=True)
    bill=B.box(form,26,338,left-52,88,C['white'],12,C['green']); B.icon(bill,16,29,'check',C['green'],24)
    B.text(bill,56,15,'SPP November 2026',15,bold=True,w=left-130)
    B.text(bill,56,43,'Jatuh tempo 10 November 2026',12,C['muted'],w=left-130)
    B.text(bill,left-230,17,'Rp590.000',18,bold=True,w=150,align='right')
    B.text(form,26,454,'Metode pembayaran',13,bold=True)
    B.button(form,26,484,170,'Tunai','credit-card','soft')
    B.button(form,208,484,170,'QRIS','qrcode','secondary',dest='qris-input')
    B.field(form,26,554,left-52,'Jumlah pembayaran','Rp590.000','Pembayaran sebagian diperbolehkan, maksimal sesuai sisa tagihan.')
    B.line(form,26,692,left-52)
    B.button(form,26,716,118,'Batal',style='secondary',dest='dashboard')
    B.button(form,left-230,716,204,'Simpan pembayaran',style='primary',dest='sukses')
    summary=B.box(f,cx+left+24,202,right,390,C['side'],22)
    B.text(summary,26,26,'Ringkasan pembayaran',18,C['white'],True,w=right-52)
    for yy,label,amt in [(88,'Total tagihan','Rp590.000'),(143,'Sudah dibayar','Rp0'),(198,'Pembayaran ini','Rp590.000')]:
        B.text(summary,26,yy,label,13,'#BCD4CE',w=right-52)
        B.text(summary,26,yy+23,amt,20,C['white'],True,w=right-52)
    B.line(summary,26,264,right-52,C['side2'])
    B.text(summary,26,286,'Sisa setelah pembayaran',13,'#BCD4CE',w=right-52)
    B.text(summary,26,315,'Rp0',32,C['lime'],True,w=right-52)
    B.text(f,cx+left+32,622,'Tunai langsung tercatat sebagai diterima.\nQRIS harus diverifikasi terlebih dahulu.',13,C['muted'],w=right-16,h=72)

def qris_form(x,y):
    f,cx,cw=B.shell('qris-input','Pembayaran QRIS','Bukti diunggah petugas dan diperiksa sebelum pembayaran diterima.',x,y,active='Pembayaran')
    c=B.box(f,cx,202,700,760)
    B.text(c,28,25,'Aisyah Rahma',22,bold=True); B.text(c,28,62,'2410123 / Kelas 1',13,C['muted'])
    B.field(c,28,115,644,'Tagihan','SPP November 2026 / Rp590.000')
    B.field(c,28,224,644,'Jumlah pembayaran','Rp590.000')
    B.text(c,28,334,'Bukti pembayaran QRIS',13,bold=True)
    up=B.box(c,28,365,644,180,C['bg'],14,C['line'])
    B.icon(up,309,29,'download',C['green'],24)
    B.text(up,20,77,'Pilih file bukti pembayaran',15,bold=True,w=604,align='center')
    B.text(up,20,109,'JPG, PNG atau PDF. Maksimal 2 MB.',12,C['muted'],w=604,align='center')
    B.badge(c,28,572,'Menunggu verifikasi','blue',178)
    B.text(c,28,615,'Tagihan tetap belum lunas sampai bukti diterima.',13,C['muted'],w=640)
    B.button(c,28,686,120,'Kembali',style='secondary',dest='input')
    B.button(c,458,686,214,'Simpan pengajuan',dest='verifikasi')

def verify(x,y):
    f,cx,cw=B.shell('verifikasi','Verifikasi QRIS','12 pembayaran menunggu pemeriksaan bukti.',x,y,active='Verifikasi QRIS')
    listp=B.box(f,cx,202,390,766)
    B.text(listp,24,24,'Antrean pemeriksaan',18,bold=True,w=342)
    for i,(nm,amt) in enumerate([('Bagas Pratama','Rp3.500.000'),('Aisyah Rahma','Rp590.000'),('Dimas Saputra','Rp300.000'),('Eka Wulandari','Rp540.000')]):
        c=B.box(listp,14,84+i*114,362,98,C['mint'] if i==0 else C['white'],14)
        B.avatar(c,14,16,''.join(n[0] for n in nm.split())); B.text(c,64,12,nm,14,bold=True,w=260)
        B.text(c,64,38,amt,16,bold=True,w=260); B.text(c,64,67,'3 November 2026',11,C['muted'],w=260)
    d=B.box(f,cx+414,202,cw-414,766)
    B.text(d,26,24,'Detail pembayaran',20,bold=True,w=550)
    B.text(d,26,64,'Bagas Pratama / 2310148 / Kelas 2',14,C['muted'],w=550)
    for yy,lab,val in [(112,'Tagihan','Daftar ulang 2026'),(155,'Nominal pengajuan','Rp3.500.000')]:
        B.text(d,26,yy,lab,13,C['muted'],w=240); B.text(d,270,yy,val,14,bold=True,w=350)
    proof=B.box(d,26,215,cw-466,278,C['bg'],12,C['line'])
    B.icon(proof,26,26,'file-text',C['green'],24)
    B.text(proof,26,71,'bukti-qris-bagas.jpg',16,bold=True,w=500)
    B.text(proof,26,108,'Area pratinjau bukti pembayaran',14,C['muted'],w=500)
    B.text(proof,26,143,'Contoh desain. Bukti asli ditampilkan di sini\nagar petugas dapat memeriksa nominal dan penerima.',13,C['muted'],w=500,h=70)
    B.button(proof,26,218,174,'Perbesar bukti','eye',style='secondary')
    B.text(d,26,522,'Pastikan nominal dan penerima pembayaran sesuai.',13,C['muted'],w=cw-470)
    B.field(d,26,563,cw-466,'Alasan penolakan','Isi jika bukti tidak sesuai')
    B.button(d,26,693,150,'Tolak bukti',style='danger',dest='ditolak')
    B.button(d,cw-644,693,204,'Terima pembayaran',dest='sukses')

def report(x,y,role='Yayasan'):
    key='laporan' if role=='Yayasan' else 'laporan-keuangan'
    f,cx,cw=B.shell(key,'Laporan pembayaran','Rekap penerimaan, tagihan, dan sisa pembayaran per periode.',x,y,active='Laporan',role=role)
    B.button(f,1440-224,114,188,'Ekspor CSV','download',style='primary')
    filt=B.box(f,cx,202,cw,126)
    for xx,ww,lab,val in [(24,235,'Periode','November 2026'),(283,220,'Jenis','Semua jenis'),(527,230,'Status','Semua status'),(781,260,'Cari NIS','Semua siswa')]: B.field(filt,xx,18,ww,lab,val)
    for i,(label,amt,bg,fg) in enumerate([('Total tagihan','Rp212.500.000',C['white'],C['ink']),('Pembayaran diterima','Rp163.750.000',C['side'],C['white']),('Sisa pembayaran','Rp48.750.000',C['white'],C['ink'])]):
        c=B.box(f,cx+i*(cw+24)/3,352,(cw-48)/3,134,bg)
        B.text(c,24,24,label,13,'#C4DBD5' if i==1 else C['muted'],w=290)
        B.text(c,24,63,amt,27,fg,True,w=320)
    p=B.box(f,cx,510,cw,348)
    B.text(p,24,24,'Rekap per jenis tagihan',20,bold=True,w=650)
    for xx,lab in [(24,'JENIS'),(320,'TOTAL TAGIHAN'),(590,'DITERIMA'),(860,'SISA')]: B.text(p,xx,84,lab,11,C['muted'],True,w=220)
    for i,row in enumerate([('SPP','Rp157.250.000','Rp125.250.000','Rp32.000.000'),('Daftar ulang','Rp38.250.000','Rp25.000.000','Rp13.250.000'),('Jemputan','Rp17.000.000','Rp13.500.000','Rp3.500.000')]):
        yy=128+i*62; B.line(p,24,yy-14,cw-48)
        for xx,v in zip([24,320,590,860],row): B.text(p,xx,yy,v,14,bold=i==0,w=220)
    B.text(f,cx+4,886,'Rekap hanya menghitung pembayaran berstatus diterima. Pembatalan tetap tersimpan di riwayat.',13,C['muted'],w=cw)

def students(key,x,y,admin=False):
    f,cx,cw=B.shell(key,'Data siswa','Identitas siswa, kelas, dan akses akun orang tua.',x,y,active='Data siswa',role='Admin' if admin else 'Petugas Keuangan')
    if admin: B.button(f,1440-216,114,180,'Tambah siswa','plus',dest='tambah-siswa')
    p=B.box(f,cx,202,cw,730)
    B.field(p,24,24,460,'Cari siswa','Nama atau NIS'); B.field(p,510,24,230,'Kelas','Semua kelas'); B.field(p,766,24,310,'Status siswa','Aktif')
    for xx,lab in [(24,'NIS'),(235,'NAMA SISWA'),(570,'KELAS'),(720,'AKUN'),(920,'STATUS')]: B.text(p,xx,155,lab,11,C['muted'],True,w=200)
    for i,nm in enumerate(['Aisyah Rahma','Bagas Pratama','Citra Lestari','Dimas Saputra','Eka Wulandari','Fajar Nugroho']):
        yy=208+i*67; B.line(p,24,yy-16,cw-48)
        B.text(p,24,yy,str(2410123+i),14,w=180); B.text(p,235,yy,nm,14,bold=True,w=300)
        B.text(p,570,yy,str(i+1),14,w=110)
        B.text(p,720,yy,'Belum dibuat' if i==4 else 'Aktif',13,C['muted'],w=190)
        B.badge(p,920,yy-2,'Aktif','green',96)
    B.text(p,24,658,'312 siswa aktif / Kelas 1-6',13,C['muted'],w=500)

def users(x,y):
    f,cx,cw=B.shell('pengguna','Pengguna','Kelola akses staf sesuai tanggung jawabnya.',x,y,active='Pengguna',role='Admin')
    p=B.box(f,cx,202,cw,490); B.text(p,24,25,'Akun staf',20,bold=True)
    for xx,lab in [(24,'NAMA'),(350,'USERNAME'),(570,'ROLE'),(850,'STATUS')]: B.text(p,xx,100,lab,11,C['muted'],True,w=200)
    for i,(nm,username,role) in enumerate([('Admin Sekolah','admin','Admin'),('Yosi Rahma','keuangan','Petugas Keuangan'),('Ibu Nurhayati','yayasan','Yayasan')]):
        yy=154+i*76; B.line(p,24,yy-18,cw-48)
        for xx,v in zip([24,350,570],[nm,username,role]): B.text(p,xx,yy,v,14,bold=xx==24,w=260)
        B.badge(p,850,yy-3,'Aktif','green',100)
    B.text(p,24,416,'Akun siswa dibuat dari menu Data siswa, menggunakan NIS sebagai username.',13,C['muted'],w=cw-48)

def cancelled(x,y):
    f,cx,cw=B.shell('pembatalan','Pembatalan pembayaran','Riwayat koreksi transaksi tetap tersimpan untuk pemeriksaan.',x,y,active='Pembatalan',role='Yayasan')
    p=B.box(f,cx,202,cw,460); B.text(p,26,26,'Pembatalan terbaru',20,bold=True)
    B.badge(p,26,82,'Dibatalkan','gray',120)
    B.text(p,26,137,'Dimas Saputra / SPP Oktober 2026',20,bold=True,w=750)
    B.text(p,26,183,'Nominal: Rp300.000',16,bold=True,w=650)
    B.text(p,26,231,'Alasan pembatalan',13,C['muted'],w=650)
    B.text(p,26,263,'Salah memilih siswa saat mencatat pembayaran tunai.',15,w=850)
    B.line(p,26,321,cw-52)
    B.text(p,26,347,'Dicatat oleh Yosi Rahma pada 2 November 2026, 10.24 WIB.',13,C['muted'],w=850)
    B.text(p,26,382,'Pembayaran lama dipertahankan. Saldo tagihan dihitung ulang.',13,C['muted'],w=850)

def login(x,y):
    f=B.frame('login','Masuk SIAPA',x,y,1440,960)
    l=B.box(f,24,24,650,912,C['side'],28)
    B.badge(l,44,46,'SDIT ABU BAKAR','green',166)
    B.text(l,44,232,'Administrasi rapi.\nPembayaran lebih pasti.',44,C['white'],True,560,h=150)
    B.text(l,44,411,'Satu tempat untuk tagihan, pembayaran,\ndan laporan sekolah.',18,'#C1D9D1',w=540,h=80)
    a=B.box(l,44,573,562,185,C['side2'],20)
    B.text(a,26,22,'Untuk sekolah dan orang tua',16,C['white'],True,w=500)
    for i,lab in enumerate(['Tagihan mudah dipantau','Riwayat pembayaran jelas','Data sesuai hak akses']):
        B.icon(a,26,65+i*34,'check',C['lime'],24); B.text(a,64,67+i*34,lab,14,'#D4E6DF',w=450)
    B.text(l,44,841,'SDIT Abu Bakar Ash Shiddiq Rajapolah',13,'#ABC7BE',w=540)
    logo=ROOT.parent/'logo'/'siapa-konsep-01.png'
    B.add('image.place',path=str(logo),parent=f,x=806,y=126,width=370,height=128,mode='fit',name='Logo SIAPA')
    B.text(f,806,290,'Selamat datang kembali',30,bold=True,w=540)
    B.text(f,806,344,'Masuk menggunakan akun yang diberikan sekolah.',15,C['muted'],w=525)
    B.field(f,806,411,490,'Username','Masukkan username atau NIS')
    B.field(f,806,521,490,'Password','Masukkan password')
    B.icon(f,1257,559,'eye',size=24)
    B.button(f,806,657,490,'Masuk',style='primary',dest='dashboard')
    B.text(f,806,736,'Orang tua menggunakan NIS siswa sebagai username.\nHubungi admin sekolah jika mengalami kendala masuk.',13,C['muted'],w=490,h=70)

def mobile(key,x,y,history=False):
    f=B.frame(key,'Portal orang tua / '+('Riwayat' if history else 'Tagihan'),x,y,390,960)
    B.text(f,24,34,'SIAPA',23,bold=True,w=160); B.avatar(f,330,30,'AR')
    B.text(f,24,105,'Halo, orang tua Aisyah',23,bold=True,w=342)
    B.text(f,24,146,'NIS 2410123 / Kelas 1',14,C['muted'],w=342)
    h=B.box(f,20,195,350,178,C['side'],22)
    B.text(h,24,22,'Sisa tagihan',14,'#BBD7CE',w=300)
    B.text(h,24,55,'Rp865.000',34,C['white'],True,w=300)
    B.text(h,24,113,'SPP dan jemputan November',13,'#C8DFD7',w=300)
    B.text(h,24,142,'Jatuh tempo 10 November 2026',12,C['lime'],w=300)
    B.button(f,20,397,166,'Tagihan',style='secondary' if history else 'soft',dest='mobile-tagihan')
    B.button(f,202,397,168,'Riwayat',style='soft' if history else 'secondary',dest='mobile-riwayat')
    B.text(f,24,471,'Riwayat pembayaran' if history else 'Tagihan aktif',18,bold=True,w=342)
    rows=[('SPP Oktober 2026','Rp590.000','Diterima'),('Jemputan Oktober','Rp275.000','Diterima')] if history else [('SPP November 2026','Rp590.000','Belum lunas'),('Jemputan November','Rp275.000','Belum lunas')]
    for i,(lab,amt,status) in enumerate(rows):
        p=B.box(f,20,513+i*154,350,134,C['white'],18)
        B.text(p,20,17,lab,15,bold=True,w=310); B.text(p,20,49,amt,25,bold=True,w=310)
        B.badge(p,20,93,status,'green' if history else 'amber',120)
        B.text(p,157,97,'3 Okt 2026' if history else '10 Nov 2026',12,C['muted'],w=170,align='right')
    B.text(f,24,848,'Untuk pembayaran atau konfirmasi QRIS,\nhubungi petugas keuangan sekolah.',13,C['muted'],w=342,h=60)

def notice(key,title,body,x,y,tone='green'):
    f=B.frame(key,title,x,y,520,360)
    p=B.box(f,20,20,480,320)
    B.box(p,28,28,48,48,C['mint'] if tone=='green' else C['rose'],16)
    B.icon(p,40,40,'check' if tone=='green' else 'x',C[tone],24)
    B.text(p,28,99,title,25,bold=True,w=424)
    B.text(p,28,148,body,14,C['muted'],w=424,h=72)
    B.button(p,28,246,424,'Kembali ke ringkasan',dest='dashboard')

def create_bill(x,y):
    f,cx,cw=B.shell('buat-tagihan','Buat tagihan','Tarif mengikuti kelas. Jemputan diisi manual per siswa.',x,y,active='Tagihan')
    p=B.box(f,cx,202,760,644)
    B.text(p,28,26,'Generate tagihan SPP',22,bold=True,w=690)
    B.text(p,28,68,'Tagihan dibuat untuk seluruh siswa aktif pada periode yang dipilih.',14,C['muted'],w=690)
    B.field(p,28,125,704,'Periode','November 2026')
    B.text(p,28,241,'Tarif SPP per kelas',15,bold=True,w=690)
    for i,(label,amt) in enumerate([('Kelas 1','Rp590.000'),('Kelas 2','Rp550.000'),('Kelas 3-6','Rp540.000')]):
        B.text(p,28,286+i*49,label,14,w=330); B.text(p,480,286+i*49,amt,16,bold=True,w=250,align='right')
    B.text(p,28,463,'Tagihan periode yang sudah ada tidak akan dibuat ulang.',13,C['muted'],w=690)
    B.button(p,28,554,130,'Kembali',style='secondary',dest='tagihan')
    B.button(p,520,554,212,'Generate tagihan',dest='tagihan')

def add_student(x,y):
    f,cx,cw=B.shell('tambah-siswa','Tambah siswa','Daftarkan identitas siswa sebelum membuat akun orang tua.',x,y,active='Data siswa',role='Admin')
    p=B.box(f,cx,202,760,670)
    B.text(p,28,26,'Identitas siswa',22,bold=True,w=690)
    B.field(p,28,95,704,'NIS','Masukkan NIS unik','NIS menjadi identitas tetap dan tidak dapat diubah setelah disimpan.')
    B.field(p,28,225,704,'Nama lengkap siswa','Nama sesuai data sekolah')
    B.field(p,28,342,704,'Kelas','Pilih kelas 1-6')
    B.text(p,28,467,'Setelah siswa tersimpan, admin dapat membuat akun dengan username NIS.\nPassword awal ditampilkan sekali dan wajib diganti saat login pertama.',13,C['muted'],w=704,h=64)
    B.button(p,28,584,128,'Kembali',style='secondary',dest='admin')
    B.button(p,530,584,202,'Simpan siswa',dest='admin')

def guide(x,y):
    f=B.frame('guide','Panduan konsep dan interaksi v4',x,y,1440,980)
    B.text(f,48,44,'SIAPA / Konsep v4',36,bold=True,w=900)
    B.text(f,48,105,'Area kerja terang, navigasi ringkas, dan hierarki uang yang mudah dibaca.',18,C['muted'],w=1300)
    for i,(key,label) in enumerate([('side','Navigasi'),('green','Aksi utama'),('lime','Menu aktif'),('bg','Latar kerja'),('amber','Perlu perhatian')]):
        B.box(f,48+i*270,177,232,86,C[key],16)
        B.text(f,48+i*270,279,label+' / '+C[key],13,w=250)
    B.text(f,48,350,'Navigasi yang bisa diperkecil',24,bold=True,w=650)
    B.text(f,48,402,'Terbuka: 240 px, ikon dan label.\nRingkas: 80 px, ikon tetap di posisi yang sama.\nTombol panel menukar kedua keadaan dengan transisi 220 ms.',17,C['muted'],w=730,h=130)
    B.text(f,840,350,'Prioritas informasi',24,bold=True,w=540)
    B.text(f,840,402,'Penerimaan, sisa tagihan, dan QRIS.\nPeringatan jatuh tempo.\nGrafik, komposisi, dan pembayaran terbaru.',17,C['muted'],w=540,h=130)
    B.text(f,48,582,'Cakupan prototype',24,bold=True,w=1250)
    B.text(f,48,637,'Keuangan: dashboard, tagihan, input tunai/QRIS, verifikasi, tunggakan.\nAdmin: siswa dan pengguna. Yayasan: ringkasan, laporan, pembatalan.\nOrang tua: tagihan dan riwayat di HP. Login dan hasil transaksi.',17,C['muted'],w=1300,h=140)
    B.text(f,48,827,'Data contoh untuk desain. Tidak tersambung ke transaksi asli.',14,C['green'],True,w=1300)
    B.text(f,48,867,'Arah visual berasal dari referensi dan HTML pengguna; tidak memakai docs/DESIGN.md.',14,C['muted'],w=1300)

def build():
    download_icons()
    run('file.new',name='SIAPA v4 - Fresh Finance')
    run('page.rename',name='v4 / Layar & prototype')
    dashboard('dashboard',0,0)
    dashboard('dashboard-ringkas',1536,0,True)
    payment(3072,0); qris_form(4608,0); verify(6144,0)
    bills('tagihan',0,1320); bills('tunggakan',1536,1320,True)
    report(3072,1320); students('admin',4608,1320,True); students('siswa',6144,1320)
    login(0,2560); dashboard('yayasan',1536,2560,role='Yayasan'); users(3072,2560); cancelled(4608,2560)
    create_bill(6144,2560)
    mobile('mobile-tagihan',0,3880); mobile('mobile-riwayat',470,3880,True)
    notice('sukses','Pembayaran tersimpan','Pembayaran telah diterima. Status tagihan dan riwayat pembayaran sudah diperbarui.',1040,3880)
    notice('ditolak','Bukti ditolak','Alasan penolakan tersimpan. Pembayaran ini tidak dihitung sebagai penerimaan.',1040,4320,'red')
    guide(1700,3880)
    report(3240,3880,role='Petugas Keuangan'); add_student(4776,3880)
    print('Creating',len(B.steps),'editable commands',flush=True)
    results=run('command.batch',label='SIAPA v4 / Desain baru',commands=B.steps)
    (ROOT/'build-result.json').write_text(json.dumps(results,ensure_ascii=False),encoding='utf-8')
    def actual(ref):
        index=int(ref['$ref'][1:])-1
        step=results[index]
        if not step.get('ok',True): raise RuntimeError(step)
        return step.get('result',step)['id']
    frameids={key:actual(val) for key,val in B.frames.items()}
    links=[]
    # Prototype menu links, both sidebar states, and payment outcome modals.
    for ref,dest in B.links:
        if dest not in frameids: continue
        links.append(dict(command='proto.interaction.add',params=dict(id=actual(ref),dest=frameids[dest],transition=dict(type='dissolve'),durationMs=180)))
    for source,dest in [('dashboard','dashboard-ringkas'),('dashboard-ringkas','dashboard')]:
        links.append(dict(command='proto.interaction.add',params=dict(id=actual(B.hits[source]['toggle']),dest=frameids[dest],transition=dict(type='smartAnimate'),durationMs=220,easing='easeInOut')))
    run('command.batch',label='Hubungkan prototype v4',commands=links)
    run('proto.flow.add',start=frameids['dashboard'],name='Keuangan / mulai di sini')
    run('proto.flow.add',start=frameids['login'],name='Masuk SIAPA')
    run('proto.flow.add',start=frameids['mobile-tagihan'],name='Portal orang tua')
    run('file.saveAs',path=str(ROOT/'siapa-v4.figcraft'))
    (ROOT/'frames.json').write_text(json.dumps(frameids,indent=2),encoding='utf-8')
    for key in frameids:
        run('file.export',ids=[frameids[key]],format='png',scale=1,path=str(ROOT/(key+'.png')))
        print('Exported',key,flush=True)
    run('view.zoomAt',x=720,y=580,width=1480,height=1200)
    print(json.dumps(dict(frames=len(frameids),links=len(links),file=str(ROOT/'siapa-v4.figcraft'))),flush=True)

if __name__=='__main__': build()
