import random, json, subprocess, datetime
random.seed(7)
PW = subprocess.check_output(['php','-r','require "vendor/autoload.php"; echo App\\Services\\PasswordHasher::hash("Demo12345");'], cwd='/home/user/school-platform').decode()
class Raw(str): pass
def q(v):
    if v is None: return 'NULL'
    if isinstance(v, Raw): return str(v)
    if isinstance(v, bool): return '1' if v else '0'
    if isinstance(v, (int, float)): return str(v)
    return "'" + str(v).replace('\\','\\\\').replace("'", "''") + "'"
out = []
def w(s=''): out.append(s)
bases = {}
def base(t, col='id'):
    bases[t] = True; w(f"SET @b_{t} = (SELECT COALESCE(MAX({col}),0) FROM {t});")
def I(t, n): return Raw(f"@b_{t}+{n}")
def ins(t, cols, rows, ignore=False):
    if not rows: return
    w(f"INSERT {'IGNORE ' if ignore else ''}INTO {t} ({', '.join(cols)}) VALUES")
    w(',\n'.join('  (' + ', '.join(q(x) for x in r) + ')' for r in rows) + ';')
D = lambda n: Raw(f"(CURDATE() - INTERVAL {n} DAY)") if n >= 0 else Raw(f"(CURDATE() + INTERVAL {-n} DAY)")
DT = lambda n, t='08:00:00': Raw(f"TIMESTAMP(CURDATE() - INTERVAL {n} DAY, '{t}')") if n >= 0 else Raw(f"TIMESTAMP(CURDATE() + INTERVAL {-n} DAY, '{t}')")
SID = Raw('@sid'); ADMIN = Raw('@admin')
role = lambda c: Raw(f"(SELECT id FROM roles WHERE school_id = @sid AND code = '{c}')")

w("-- ============================================================================")
w("-- بيانات تجريبية لمنصة «مدرستي» (للتجربة والتدريب فقط — لا تُشغَّل على قاعدة إنتاج)")
w("-- المتطلبات: قاعدة بعد تثبيت المنصة (/install) وبعد إنشاء مدرسة واحدة على الأقل من لوحة المشغّل.")
w("-- تُضاف البيانات إلى أول مدرسة، وتُنشأ سنة دراسية جديدة باسم «تجريبية»؛ شغّله مرة واحدة فقط.")
w("-- كلمة مرور كل الحسابات التجريبية: Demo12345")
w("-- ============================================================================")
w("SET NAMES utf8mb4; SET FOREIGN_KEY_CHECKS = 0; SET @OLD_SQL_MODE = @@SQL_MODE; SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';")
w("SET @sid = (SELECT id FROM schools WHERE deleted_at IS NULL ORDER BY id LIMIT 1);")
w("SET @admin = (SELECT MIN(user_id) FROM user_roles WHERE role_id = (SELECT id FROM roles WHERE school_id = @sid AND code = 'school_admin'));")
w("SET @scheme = (SELECT id FROM grading_schemes WHERE school_id = @sid AND is_default = 1 ORDER BY id LIMIT 1);")
w("SET @t_work = (SELECT id FROM assessment_types WHERE scheme_id = @scheme ORDER BY sort_order LIMIT 1);")
w("SET @t_exam = (SELECT id FROM assessment_types WHERE scheme_id = @scheme ORDER BY sort_order DESC LIMIT 1);")
w("SELECT IF(@sid IS NULL, 'خطأ: أنشئ مدرسة أولًا من لوحة المشغّل', CONCAT('سيتم الإضافة إلى المدرسة رقم ', @sid)) AS info;")
for t in ['users','staff','students','student_identities','guardians','academic_years','terms','stages','grade_levels','rooms','sections','subjects','section_subjects','student_enrollments','student_guardians','timetable_slots','attendance_sessions','attendance_records','assessments','grades','report_cards','lessons','materials','assignments','submissions','question_bank','quizzes','quiz_attempts','live_sessions','messages','announcements','notifications','notification_queue','exchange_rates','payment_methods','fee_structures','fee_items','discounts','student_fees','installments','invoices','invoice_lines','payments','receipts','contracts','payroll_runs','payroll_items','admission_applications','risk_indicators','risk_flags','counseling_cases','counseling_notes','facilities','facility_bookings','credentials','discipline_records','student_status_history','student_transfers','consents','staff_qualifications','staff_attendance','teacher_assignments','import_jobs','import_rows','audit_logs','settings']:
    base(t)
w("SET @cur_year = YEAR(CURDATE());")

# ---------- الهيكل الأكاديمي ----------
w("\n-- الهيكل الأكاديمي")
ins('academic_years',['id','school_id','name','starts_on','ends_on','status'],[[I('academic_years',1),SID,'السنة التجريبية',D(100),D(-200),'active']])
Y = I('academic_years',1)
ins('terms',['id','school_id','academic_year_id','name','term_no','starts_on','ends_on','is_final','grades_locked'],[
 [I('terms',1),SID,Y,'الفصل الأول',1,D(100),D(-40),0,0],[I('terms',2),SID,Y,'الفصل الثاني',2,D(-41),D(-200),1,0]])
ins('stages',['id','school_id','name','sort_order'],[[I('stages',1),SID,'المرحلة الأساسية (تجريبي)',1]])
ins('grade_levels',['id','school_id','stage_id','name','sort_order','next_grade_level_id'],[
 [I('grade_levels',1),SID,I('stages',1),'الصف الأول (تجريبي)',1,I('grade_levels',2)],[I('grade_levels',2),SID,I('stages',1),'الصف الثاني (تجريبي)',2,None]])
ins('rooms',['id','school_id','name','capacity','kind'],[[I('rooms',1),SID,'قاعة 1 (تجريبي)',30,'classroom'],[I('rooms',2),SID,'قاعة 2 (تجريبي)',30,'classroom'],[I('rooms',3),SID,'مختبر العلوم (تجريبي)',25,'lab']])
SUBJ = ['الرياضيات (تجريبي)','اللغة العربية (تجريبي)','العلوم (تجريبي)','اللغة الإنجليزية (تجريبي)']
ins('subjects',['id','school_id','name','code'],[[I('subjects',i+1),SID,n,c] for i,(n,c) in enumerate(zip(SUBJ,['MATH','ARAB','SCI','ENG']))])
SECS = [('أ',1,1),('ب',1,2),('أ',2,1)]   # name, grade, room
# موظفون (تُنشأ قبل الشعب لأن homeroom يشير إليهم)
STAFF = [('خالد أحمد المقطري','male','teacher','معلم رياضيات','الرياضيات','khaled.demo@school.test','0771000001'),
 ('سلمى علي الحداد','female','teacher','معلمة لغة عربية','اللغة العربية','salma.demo@school.test','0771000002'),
 ('محمد ناصر العولقي','male','teacher','معلم علوم','العلوم','mohammed.demo@school.test','0771000003'),
 ('هدى صالح بن سالم','female','teacher','معلمة لغة إنجليزية','اللغة الإنجليزية','huda.demo@school.test','0771000004'),
 ('عبدالله يحيى الشرجبي','male','accountant','محاسب','المحاسبة','acc.demo@school.test','0771000005'),
 ('ريم عبدالله القدسي','female','counselor','مرشدة طلابية','الإرشاد النفسي','counselor.demo@school.test','0771000006')]
w("\n-- المستخدمون والموظفون")
urows=[]; srows=[]; urole=[]
for i,(n,g,r,job,spec,em,ph) in enumerate(STAFF,1):
    urows.append([I('users',i),SID,'staff',n,em,ph,None,PW,0])
    urole.append([I('users',i),role(r)])
    srows.append([I('staff',i),SID,I('users',i),f'EMP-{i:03d}',n,g,Raw(f"'1985-0{i}-15'"),ph,em,job,spec,1 if r=='teacher' else 0,D(700+i*30),'active'])
ins('users',['id','school_id','user_type','full_name','email','phone','username','password_hash','must_change_password'],urows)
ins('user_roles',['user_id','role_id'],urole,ignore=True)
ins('staff',['id','school_id','user_id','employee_no','full_name','gender','birth_date','phone','email','job_title','specialization','is_teacher','hire_date','status'],srows)
ins('staff_qualifications',['school_id','staff_id','degree','major','institution','year'],[[SID,I('staff',1),'بكالوريوس','رياضيات','جامعة صنعاء',2008],[SID,I('staff',2),'بكالوريوس','لغة عربية','جامعة عدن',2010],[SID,I('staff',6),'ماجستير','علم النفس','جامعة صنعاء',2015]])
ins('staff_attendance',['school_id','staff_id','att_date','status','check_in','check_out','recorded_by'],[[SID,I('staff',s),D(d),st,'07:30:00','13:30:00',ADMIN] for d in (1,2,3) for s,st in zip(range(1,7),['present','present','late','present','present','present'])])
ins('sections',['id','school_id','academic_year_id','grade_level_id','name','gender','capacity','homeroom_staff_id','room_id'],[[I('sections',i+1),SID,Y,I('grade_levels',g),n,'mixed',30,I('staff',i+1),I('rooms',rm)] for i,(n,g,rm) in enumerate(SECS)])
ss=[]; ssid={}; k=0
for si in range(3):
    for sj in range(4):
        k+=1; ssid[(si,sj)]=k; ss.append([I('section_subjects',k),SID,I('sections',si+1),I('subjects',sj+1),5 if sj in (0,1) else 3,100])
ins('section_subjects',['id','school_id','section_id','subject_id','weekly_periods','max_score'],ss)
ins('teacher_assignments',['school_id','section_subject_id','staff_id','is_primary'],[[SID,I('section_subjects',k),I('staff',sj+1),1] for (si,sj),k in ssid.items()])

# ---------- الطلاب وأولياء الأمور ----------
w("\n-- الطلاب وأولياء الأمور")
NAMES = ['يوسف أحمد العمراني','مريم أحمد العمراني','عمر سالم باوزير','نور محمد الحكيمي','أسامة علي الوصابي','لينا علي الوصابي','طارق حسين الأهدل','ديما فهد المنصوب','زيد عبدالرحمن الكبسي','رنا ياسر الصلوي','بسام جمال القاضي','هالة منير الشامي']
GEND = ['male','female','male','female','male','female','male','female','male','female','male','female']
SEC_OF = [0]*4+[1]*4+[2]*4
GUARD = [(1,'أحمد صالح العمراني','father','0773001001'),(1,None,None,None),(2,'سالم عمر باوزير','father','0773001002'),(3,'محمد قاسم الحكيمي','father','0773001003'),(4,'علي حمود الوصابي','father','0773001004'),(4,None,None,None),(5,'حسين طاهر الأهدل','father','0773001005'),(6,'فهد ناصر المنصوب','father','0773001006'),(7,'عبدالرحمن خالد الكبسي','father','0773001007'),(8,'ياسر محمد الصلوي','father','0773001008'),(9,'جمال أمين القاضي','father','0773001009'),(10,'منير سعيد الشامي','father','0773001010')]
gmap = {}; grows=[]; gu=[]; gurole=[]
gi=0
for i,(gid,gname,rel,ph) in enumerate(GUARD):
    if gname:
        gi+=1; gmap[gid]=gi
        uid = 6+gi
        gu.append([I('users',uid),SID,'guardian',gname,None,ph,None,PW,0]); gurole.append([I('users',uid),role('guardian')])
        grows.append([I('guardians',gi),I('users',uid),gname,ph,ph,'موظف','صنعاء - حي التحرير'])
ins('users',['id','school_id','user_type','full_name','email','phone','username','password_hash','must_change_password'],gu)
ins('user_roles',['user_id','role_id'],gurole,ignore=True)
ins('guardians',['id','user_id','full_name','phone','whatsapp','job','address'],grows)
STU_U0 = 6+gi
stu_users=[]; sturole=[]; ident=[]; stud=[]; enr=[]; sg=[]
for i,n in enumerate(NAMES,1):
    no=f'D{i:04d}'   # بادئة D لتمييز الطلاب التجريبيين
    uid=STU_U0+i
    stu_users.append([I('users',uid),SID,'student',n,None,None,no.lower(),PW,0]); sturole.append([I('users',uid),role('student')])
    ident.append([I('student_identities',i),f'DEMO{i:012d}',n,Raw(f"'201{2+(i%3)}-0{(i%9)+1}-1{i%9}'"),GEND[i-1]])
    stud.append([I('students',i),SID,I('student_identities',i),I('users',uid),no,n,GEND[i-1],Raw(f"'201{2+(i%3)}-0{(i%9)+1}-1{i%9}'"),'صنعاء','يمني','صنعاء',D(90),'active'])
    enr.append([I('student_enrollments',i),SID,I('students',i),Y,I('sections',SEC_OF[i-1]+1),(i-1)%4+1,'active',D(90)])
    g = GUARD[i-1][0]
    sg.append([SID,I('students',i),I('guardians',gmap[g]),GUARD[i-1][2] or 'father',1,1,1,1])
# الأخت تشترك في ولي الأمر مع أخيها
sg=[[SID,I('students',i),I('guardians',gmap[GUARD[i-1][0]]),'father',1,1,1,1] for i in range(1,13)]
ins('users',['id','school_id','user_type','full_name','email','phone','username','password_hash','must_change_password'],stu_users)
ins('user_roles',['user_id','role_id'],sturole,ignore=True)
ins('student_identities',['id','platform_uid','full_name','birth_date','gender'],ident)
ins('students',['id','school_id','identity_id','user_id','student_no','full_name','gender','birth_date','birth_place','nationality','address','admission_date','status'],stud)
ins('student_enrollments',['id','school_id','student_id','academic_year_id','section_id','roll_no','status','enrolled_on'],enr)
ins('student_guardians',['school_id','student_id','guardian_id','relation','is_primary','can_view','can_pickup','financially_responsible'],sg)
ins('student_status_history',['school_id','student_id','academic_year_id','from_status','to_status','reason','changed_by'],[[SID,I('students',i),Y,None,'active','قيد جديد',ADMIN] for i in (1,2,3)])
ins('discipline_records',['school_id','student_id','incident_date','kind','description','action_taken','recorded_by'],[
 [SID,I('students',7),D(5),'violation','تأخر متكرر عن الطابور الصباحي','تنبيه شفهي وإبلاغ ولي الأمر',ADMIN],[SID,I('students',7),D(3),'violation','عدم إحضار الكتب','تنبيه',ADMIN],[SID,I('students',1),D(4),'commendation','مشاركة متميزة في الإذاعة المدرسية',None,ADMIN]])
ins('student_transfers',['identity_id','from_school_id','to_school_id','status','guardian_consent','summary_json'],[[I('student_identities',12),SID,None,'requested',1,json.dumps({'note':'طلب نقل تجريبي'},ensure_ascii=False)]])
ins('consents',['school_id','student_id','guardian_id','kind','granted'],[[SID,I('students',1),I('guardians',1),'photo',1],[SID,I('students',1),I('guardians',1),'trips',1],[SID,I('students',3),I('guardians',2),'photo',0]])
w("INSERT INTO counters (school_id, name, year, value) VALUES (@sid,'student_no',@cur_year,0) ON DUPLICATE KEY UPDATE value = value;")

# ---------- الجدول ----------
w("\n-- الجدول الدراسي (الأحد–الخميس، 4 حصص)")
tt=[]; n=0
slot_times=[('07:30:00','08:15:00'),('08:20:00','09:05:00'),('09:25:00','10:10:00'),('10:15:00','11:00:00')]
for si in range(3):
    for day in range(5):
        for p in range(4):
            sj = (day+p+si)%4   # توزيع دوري بلا تعارض: لكل شعبة مادة مختلفة، ولا يكرر معلم نفس الحصة
            n+=1
            tt.append([I('timetable_slots',n),SID,Y,I('section_subjects',ssid[(si,sj)]),I('sections',si+1),I('staff',sj+1),I('rooms',[1,2,None][si]) if si<2 else None,day,p+1,slot_times[p][0],slot_times[p][1]])
# تعارض المعلم: نفس sj في نفس (day,p) لشعبتين؟ (day+p+si)%4 يختلف باختلاف si ✓
ins('timetable_slots',['id','school_id','academic_year_id','section_subject_id','section_id','staff_id','room_id','day_of_week','period_no','starts_at','ends_at'],tt)

# ---------- الحضور ----------
w("\n-- الحضور اليومي (آخر 10 أيام دراسية)")
today=datetime.date.today(); days=[]; k=0
while len(days)<10:
    k+=1; d=today-datetime.timedelta(days=k)
    if d.weekday() not in (4,5): days.append(k)   # تجاوز الجمعة والسبت
sess=[]; rec=[]; sn=0; rn=0
for si in range(3):
    for dk in days:
        sn+=1; sess.append([I('attendance_sessions',sn),SID,I('sections',si+1),D(dk),0,I('users',1+si),'daily'])
        for e in range(1,13):
            if SEC_OF[e-1]!=si: continue
            if e==7: st='absent' if dk in days[:6] else 'present'      # طالب كثير الغياب → يظهر في الإنذار المبكر
            else: st=random.choices(['present','late','absent','excused'],[88,5,5,2])[0]
            rn+=1; rec.append([SID,I('attendance_sessions',sn),I('student_enrollments',e),st,I('users',1+si)])
ins('attendance_sessions',['id','school_id','section_id','session_date','period_no','taken_by','kind'],sess)
ins('attendance_records',['school_id','session_id','enrollment_id','status','updated_by'],rec)

# ---------- الدرجات ----------
w("\n-- التقييمات والدرجات للفصل الأول (معتمدة)")
asm=[]; gr=[]; an=0; scores={}
for si in range(3):
    for sj in range(4):
        for ty,(tid,mx,title) in enumerate([('@t_work',30,'أعمال الفصل'),('@t_exam',70,'اختبار نهاية الفصل')]):
            an+=1; asm.append([I('assessments',an),SID,I('terms',1),I('section_subjects',ssid[(si,sj)]),Raw(tid),f'{title}',mx,D(20-ty*10),'approved',ADMIN,I('users',1+sj)])
            for e in range(1,13):
                if SEC_OF[e-1]!=si: continue
                base_q = 0.35 if e==7 else random.uniform(0.55,0.97)
                sc = round(mx*min(1,max(0,base_q+random.uniform(-.08,.08))),1)
                scores.setdefault((e,si,sj),0); scores[(e,si,sj)]+=sc
                gr.append([SID,I('assessments',an),I('student_enrollments',e),sc,0,I('users',1+sj)])
ins('assessments',['id','school_id','term_id','section_subject_id','type_id','title','max_score','held_on','status','approved_by','created_by'],asm)
ins('grades',['school_id','assessment_id','enrollment_id','score','is_absent','updated_by'],gr)
def letter(p): return 'ممتاز' if p>=90 else 'جيد جدًا' if p>=80 else 'جيد' if p>=65 else 'مقبول' if p>=50 else 'ضعيف'
cards=[]; byenr={}
for (e,si,sj),tot in scores.items(): byenr.setdefault(e,[]).append((sj,tot))
secavg={}
for e,l in byenr.items(): secavg[e]=round(sum(t for _,t in l)/len(l),2)
for si in range(3):
    es=sorted([e for e in secavg if SEC_OF[e-1]==si],key=lambda e:-secavg[e])
    for rk,e in enumerate(es,1):
        subs=[{'name':SUBJ[sj],'total':round(t,2),'percent':round(t,2),'letter':letter(t),'passed':t>=50} for sj,t in sorted(byenr[e])]
        avg=secavg[e]; res='ناجح' if all(s['passed'] for s in subs) else 'مكمل'
        snap={'school':{'name':'مدرسة (تجريبي)','city':'','phone':''},'year':'السنة التجريبية','term':'الفصل الأول','is_final':False,'section':f'{["الصف الأول (تجريبي)","الصف الأول (تجريبي)","الصف الثاني (تجريبي)"][si]} / {SECS[si][0]}','student':{'name':NAMES[e-1],'no':f'D{e:04d}'},'scheme':{'name':'النظام الافتراضي','max_score':100,'pass_score':50},'subjects':subs,'average':avg,'letter':letter(avg),'rank':rk,'class_size':len(es),'result':res,'attendance':{'absent':0,'late':0,'excused':0},'incomplete':False,'issued_at':str(today)}
        cards.append([SID,I('student_enrollments',e),I('terms',1),'term',json.dumps(snap,ensure_ascii=False),avg,rk,res,ADMIN])
ins('report_cards',['school_id','enrollment_id','term_id','kind','snapshot','average','rank_in_section','result','issued_by'],cards)

# ---------- التعليم الإلكتروني ----------
w("\n-- المحتوى التعليمي")
ins('lessons',['id','school_id','section_subject_id','term_id','title','body','sort_order','is_published','created_by'],[
 [I('lessons',1),SID,I('section_subjects',ssid[(0,0)]),I('terms',1),'المعادلات من الدرجة الأولى','شرح مبسّط لحل المعادلات مع أمثلة.',1,1,I('users',1)],
 [I('lessons',2),SID,I('section_subjects',ssid[(0,0)]),I('terms',1),'النسبة والتناسب','تعريف النسبة وخصائص التناسب.',2,1,I('users',1)],
 [I('lessons',3),SID,I('section_subjects',ssid[(0,1)]),I('terms',1),'الجملة الاسمية والفعلية','قواعد وأمثلة.',1,1,I('users',2)],
 [I('lessons',4),SID,I('section_subjects',ssid[(0,2)]),I('terms',1),'دورة الماء في الطبيعة','درس علوم مسودة.',1,0,I('users',3)]])
ins('materials',['school_id','lesson_id','section_subject_id','kind','title','url'],[
 [SID,I('lessons',1),I('section_subjects',ssid[(0,0)]),'video','شرح فيديو: حل المعادلات','https://www.youtube.com/watch?v=demo1'],[SID,I('lessons',2),I('section_subjects',ssid[(0,0)]),'link','تمارين تفاعلية','https://example.com/ratio-practice'],[SID,I('lessons',3),I('section_subjects',ssid[(0,1)]),'link','ملخص القواعد','https://example.com/arabic-grammar']])
ins('assignments',['id','school_id','section_subject_id','title','instructions','due_at','max_score','allow_late','created_by'],[
 [I('assignments',1),SID,I('section_subjects',ssid[(0,0)]),'واجب المعادلات','حل التمارين 1-10 من الكتاب',DT(-3,'23:59:00'),10,1,I('users',1)],
 [I('assignments',2),SID,I('section_subjects',ssid[(0,1)]),'تعبير كتابي','اكتب فقرة عن وطنك',DT(-5,'23:59:00'),10,0,I('users',2)]])
ins('submissions',['school_id','assignment_id','enrollment_id','body','submitted_at','score','feedback','graded_by','graded_at','is_late'],[
 [SID,I('assignments',1),I('student_enrollments',1),'حللت التمارين كلها.',DT(1,'18:00:00'),9,'عمل ممتاز',I('users',1),DT(0,'09:00:00'),0],
 [SID,I('assignments',1),I('student_enrollments',2),'أجبت عن 8 تمارين.',DT(1,'20:00:00'),None,None,None,None,0],
 [SID,I('assignments',2),I('student_enrollments',3),'وطني اليمن، بلد الحضارة والتاريخ…',DT(0,'10:00:00'),None,None,None,None,0]])
ins('question_bank',['id','school_id','subject_id','kind','body','options','answer','points','created_by'],[
 [I('question_bank',1),SID,I('subjects',1),'mcq','ما ناتج 3 × 4؟',json.dumps(['10','12','14'],ensure_ascii=False),json.dumps(1),1,I('users',1)],
 [I('question_bank',2),SID,I('subjects',1),'true_false','العدد 7 عدد أولي',None,json.dumps(True),1,I('users',1)],
 [I('question_bank',3),SID,I('subjects',1),'short','ما ناتج 15 ÷ 3؟',None,json.dumps(['5','خمسة']),1,I('users',1)],
 [I('question_bank',4),SID,I('subjects',1),'essay','اشرح طريقة حل معادلة من الدرجة الأولى.',None,None,3,I('users',1)]])
ins('quizzes',['id','school_id','section_subject_id','title','opens_at','closes_at','duration_min','shuffle_questions','max_attempts','is_published','created_by','description','show_answers'],[
 [I('quizzes',1),SID,I('section_subjects',ssid[(0,0)]),'اختبار قصير: الأعداد',DT(2),DT(-5),15,1,1,1,I('users',1),'اختبار تجريبي من 4 أسئلة','after_submit']])
ins('quiz_questions',['quiz_id','question_id','sort_order','points'],[[I('quizzes',1),I('question_bank',i),i,None] for i in range(1,5)])
ins('quiz_attempts',['id','school_id','quiz_id','enrollment_id','started_at','submitted_at','question_order','score','status','deadline_at','graded_at'],[
 [I('quiz_attempts',1),SID,I('quizzes',1),I('student_enrollments',1),DT(1,'10:00:00'),DT(1,'10:10:00'),json.dumps([2,1,4,3]),3,'submitted',DT(1,'10:15:00'),None],
 [I('quiz_attempts',2),SID,I('quizzes',1),I('student_enrollments',2),DT(1,'11:00:00'),DT(1,'11:12:00'),json.dumps([1,3,2,4]),4,'graded',DT(1,'11:15:00'),DT(0,'08:00:00')]])
ins('quiz_answers',['attempt_id','question_id','answer','score'],[
 [I('quiz_attempts',1),I('question_bank',1),json.dumps(1),1],[I('quiz_attempts',1),I('question_bank',2),json.dumps(True),1],[I('quiz_attempts',1),I('question_bank',3),json.dumps('5'),1],[I('quiz_attempts',1),I('question_bank',4),json.dumps('أنقل الحدود وأقسم.'),None],
 [I('quiz_attempts',2),I('question_bank',1),json.dumps(1),1],[I('quiz_attempts',2),I('question_bank',2),json.dumps(True),1]])
ins('live_sessions',['id','school_id','section_subject_id','title','provider','join_url','starts_at','ends_at','created_by','description'],[
 [I('live_sessions',1),SID,I('section_subjects',ssid[(0,0)]),'مراجعة الفصل','jitsi','https://meet.jit.si/madrasati-demo-review-2026',DT(-1,'17:00:00'),DT(-1,'18:00:00'),I('users',1),'حصة مراجعة مباشرة'],
 [I('live_sessions',2),SID,I('section_subjects',ssid[(0,1)]),'درس النحو','meet','https://meet.google.com/demo-nahw-xyz',DT(2,'16:00:00'),DT(2,'17:00:00'),I('users',2),None]])
ins('live_attendance',['live_session_id','enrollment_id','joined_at'],[[I('live_sessions',2),I('student_enrollments',1),DT(2,'16:02:00')],[I('live_sessions',2),I('student_enrollments',2),DT(2,'16:05:00')]])

# ---------- التواصل ----------
w("\n-- التواصل")
ins('announcements',['id','school_id','title','body','audience','section_id','published_at','created_by'],[
 [I('announcements',1),SID,'بداية الفصل الدراسي','نرحب بجميع الطلاب وأولياء الأمور ونتمنى عامًا دراسيًا موفقًا.','all',None,DT(30),ADMIN],
 [I('announcements',2),SID,'اجتماع أولياء الأمور','يُعقد اجتماع أولياء الأمور يوم الخميس القادم الساعة 10 صباحًا.','guardians',None,DT(2),ADMIN],
 [I('announcements',3),SID,'رحلة علمية','رحلة علمية لطلاب الصف الأول/أ إلى المتحف الوطني.','section',I('sections',1),DT(1),I('users',1)]])
ins('messages',['id','school_id','sender_user_id','parent_id','subject','body','thread_id'],[
 [I('messages',1),SID,I('users',1),None,'نتيجة الاختبار','أود إبلاغكم بتحسن مستوى يوسف في الرياضيات.',I('messages',1)],
 [I('messages',2),SID,I('users',7),I('messages',1),'رد: نتيجة الاختبار','شكرًا أستاذ خالد على اهتمامكم.',I('messages',1)],
 [I('messages',3),SID,ADMIN,None,'تنبيه إداري','يرجى تسليم كشوف الدرجات قبل نهاية الأسبوع.',I('messages',3)]])
ins('message_recipients',['message_id','user_id','read_at'],[[I('messages',1),I('users',7),DT(0)],[I('messages',2),I('users',1),None],[I('messages',3),I('users',1),None],[I('messages',3),I('users',2),None]])
ins('notifications',['school_id','user_id','title','body','url','read_at'],[
 [SID,I('users',STU_U0+1),'واجب جديد','واجب المعادلات: موعد التسليم بعد 3 أيام','/learn',None],[SID,I('users',7),'رسالة جديدة','لديك رسالة من أ. خالد','/messages',None],[SID,I('users',1),'إعلان جديد','اجتماع أولياء الأمور','/announcements',DT(0)]])
ins('notification_channels',['user_id','channel','enabled'],[[I('users',1),'inapp',1],[I('users',1),'sms',0],[I('users',7),'inapp',1],[I('users',7),'sms',1]])
ins('notification_queue',['school_id','channel','recipient','body','status','attempts','last_error'],[
 [SID,'sms','+9677730010000','تذكير: اجتماع أولياء الأمور يوم الخميس','pending',0,None],[SID,'sms','+9677730010020','رسالة تجريبية','failed',5,'لا يوجد مزود مضبوط']])

# ---------- المالية ----------
w("\n-- الرسوم والمدفوعات")
ins('exchange_rates',['school_id','currency','rate_to_base','created_by'],[[SID,'USD',250,ADMIN],[SID,'SAR',66.5,ADMIN]])
w("UPDATE tax_settings SET tax_enabled = 0, tax_label = 'ضريبة (غير مفعّلة)' WHERE school_id = @sid;")
ins('payment_methods',['id','school_id','kind','name','account_info'],[[I('payment_methods',1),SID,'bank','تحويل بنكي (تجريبي)','حساب رقم 123456'],[I('payment_methods',2),SID,'wallet','محفظة إلكترونية (تجريبي)','0770000000']])
ins('discounts',['id','school_id','name','kind','value','reason'],[[I('discounts',1),SID,'خصم الأخوة 10%','percent',10,'sibling'],[I('discounts',2),SID,'منحة تفوق','fixed',50000,'scholarship']])
ins('fee_structures',['id','school_id','academic_year_id','grade_level_id','name','currency','installments_count','first_due_date','interval_months'],[
 [I('fee_structures',1),SID,Y,I('grade_levels',1),'رسوم الصف الأول',"YER",3,D(60),1],[I('fee_structures',2),SID,Y,I('grade_levels',2),'رسوم الصف الثاني','YER',3,D(60),1]])
ins('fee_items',['school_id','structure_id','name','amount','installments_count'],[[SID,I('fee_structures',1),'الرسوم الدراسية',250000,3],[SID,I('fee_structures',1),'الكتب والأدوات',50000,3],[SID,I('fee_structures',2),'الرسوم الدراسية',300000,3],[SID,I('fee_structures',2),'الكتب والأدوات',60000,3]])
sf=[]; inst=[]; inn=0
GROSS={0:300000,1:300000,2:360000}
paid_plan={1:[100000],2:[90000,40000],3:[100000],5:[100000,100000],9:[30000]}   # enrollment → مبالغ بالريال المطبّقة على الأقساط بالترتيب
instrows={}
for e in range(1,13):
    si=SEC_OF[e-1]; gross=GROSS[si]; disc=30000 if e==2 else 0; net=gross-disc
    sf.append([I('student_fees',e),SID,I('student_enrollments',e),I('fee_structures',1 if si<2 else 2),'YER',gross,disc,net,I('discounts',1) if e==2 else None,0,0,net,'active','خصم الأخوة' if e==2 else None])
    parts=[net//3,net//3,net-2*(net//3)]
    plan=paid_plan.get(e,[]); left=list(plan)
    for sq,amt in enumerate(parts,1):
        inn+=1; pd=0
        if left:
            pay=min(left[0],amt) if False else None
        instrows[(e,sq)]=(inn,amt)
# توزيع الدفعات على الأقساط (FIFO)
alloc=[]; payrows=[]; rcp=[]; pn=0
paid_amt={}
for e,plan in paid_plan.items():
    cursor=1
    remaining_in_inst={sq:instrows[(e,sq)][1] for sq in (1,2,3)}
    for amount in plan:
        pn+=1; left=amount; plist=[]
        for sq in (1,2,3):
            if left<=0: break
            take=min(left,remaining_in_inst[sq]-paid_amt.get((e,sq),0))
            if take>0: paid_amt[(e,sq)]=paid_amt.get((e,sq),0)+take; plist.append((sq,take)); left-=take
        payrows.append((pn,e,amount,plist))
for e in range(1,13):
    for sq in (1,2,3):
        i,amt=instrows[(e,sq)]; pa=paid_amt.get((e,sq),0)
        st='paid' if pa==amt else 'partial' if pa>0 else 'pending'
        inst.append([I('installments',i),SID,I('student_fees',e),sq,D(60-30*(sq-1)),amt,pa,st])
ins('student_fees',['id','school_id','enrollment_id','structure_id','currency','gross','discount_total','net','discount_id','tax_percent','tax_amount','payable','status','discount_reason'],[[r[0],r[1],r[2],r[3],r[4],r[5],r[6],r[7],r[8],None,0,r[11],r[12],r[13]] for r in sf])
ins('installments',['id','school_id','student_fee_id','seq','due_date','amount','paid_amount','status'],inst)
prow=[]; arow=[]; rrow=[]
for (pn,e,amount,plist) in payrows:
    usd = (e==9)
    cur = 'USD' if usd else 'YER'; amt_in = 120 if usd else amount; rate = 250 if usd else 1
    prow.append([I('payments',pn),SID,None,I('installments',instrows[(e,plist[0][0])][0]),I('student_fees',e),I('payment_methods',1) if e==3 else None,cur,amt_in,rate,'TRF-%04d'%pn if e==3 else None,D(10-pn),ADMIN,'YER',1,amount])
    for sq,take in plist: arow.append([I('payments',pn),I('installments',instrows[(e,sq)][0]),take])
    rrow.append([SID,I('payments',pn),f'RC-%d-%06d'%(today.year,pn)])
ins('payments',['id','school_id','invoice_id','installment_id','student_fee_id','method_id','currency','amount','rate_to_base','reference_no','paid_on','received_by','fee_currency','fee_rate_to_base','applied_amount'],prow)
ins('payment_allocations',['payment_id','installment_id','amount'],arow)
ins('receipts',['school_id','payment_id','number'],rrow)
w(f"INSERT INTO counters (school_id, name, year, value) VALUES (@sid,'receipt',@cur_year,{pn}) ON DUPLICATE KEY UPDATE value = {pn};")
ins('invoices',['id','school_id','number','guardian_id','student_fee_id','currency','rate_to_base','subtotal','tax_percent','tax_amount','total','status','issued_on','created_by'],[
 [I('invoices',1),SID,'INV-%d-000001'%today.year,I('guardians',1),I('student_fees',1),'YER',1,300000,None,0,300000,'partial',D(40),ADMIN],
 [I('invoices',2),SID,'INV-%d-000002'%today.year,I('guardians',1),I('student_fees',2),'YER',1,270000,None,0,270000,'partial',D(40),ADMIN],
 [I('invoices',3),SID,'INV-%d-000003'%today.year,I('guardians',2),I('student_fees',3),'YER',1,300000,None,0,300000,'partial',D(40),ADMIN]])
ins('invoice_lines',['invoice_id','description','amount'],[[I('invoices',1),'الرسوم الدراسية',250000],[I('invoices',1),'الكتب والأدوات',50000],[I('invoices',2),'الرسوم الدراسية بعد الخصم',270000],[I('invoices',3),'الرسوم الدراسية والكتب',300000]])
w(f"INSERT INTO counters (school_id, name, year, value) VALUES (@sid,'invoice',@cur_year,3) ON DUPLICATE KEY UPDATE value = 3;")

# ---------- الموسعة ----------
w("\n-- العقود والرواتب")
ctr=[[I('contracts',i),SID,I('staff',i),'دائم',D(700+i*30),None,[150000,140000,140000,130000,180000,170000][i-1],'YER','active'] for i in range(1,7)]
ins('contracts',['id','school_id','staff_id','kind','starts_on','ends_on','base_salary','currency','status'],ctr)
pm=(today.replace(day=1)-datetime.timedelta(days=1)).strftime('%Y-%m')
ins('payroll_runs',['id','school_id','period','status','created_by','approved_by','approved_at','paid_on'],[[I('payroll_runs',1),SID,pm,'paid',ADMIN,ADMIN,DT(14),Raw("(CURDATE() - INTERVAL 10 DAY)")]])
pdim=int((today.replace(day=1)-datetime.timedelta(days=1)).strftime('%d'))
ins('payroll_items',['run_id','staff_id','base','allowances','deductions','net','currency','contract_id','days_worked','days_in_month'],[[I('payroll_runs',1),I('staff',i),b,5000 if i<5 else 0,0,b+(5000 if i<5 else 0),'YER',I('contracts',i),pdim,pdim] for i,b in zip(range(1,7),[150000,140000,140000,130000,180000,170000])])
w("\n-- القبول والإنذار المبكر والإرشاد")
ins('admission_applications',['id','school_id','applicant_name','birth_date','gender','grade_level_id','guardian_name','guardian_phone','status','notes','previous_school','address','guardian_relation','academic_year_id','decision_note','decided_by','decided_at'],[
 [I('admission_applications',1),SID,'إبراهيم ماجد الزبيري',Raw("'2018-05-10'"),'male',I('grade_levels',1),'ماجد الزبيري','+967771111001','new','طلب جديد',None,'صنعاء','father',Y,None,None,None],
 [I('admission_applications',2),SID,'سارة وليد الأكوع',Raw("'2017-09-02'"),'female',I('grade_levels',2),'وليد الأكوع','+967771111002','review','وثائقها قيد المراجعة','مدرسة النور','صنعاء','father',Y,None,None,None],
 [I('admission_applications',3),SID,'كريم صادق النهمي',Raw("'2018-01-20'"),'male',I('grade_levels',1),'صادق النهمي','+967771111003','accepted',None,None,'عدن','father',Y,'مستوفٍ للشروط',ADMIN,DT(1)],
 [I('admission_applications',4),SID,'وفاء نبيل الجبري',Raw("'2017-03-14'"),'female',I('grade_levels',2),'نبيل الجبري','+967771111004','rejected',None,None,'تعز','father',Y,'لا توجد مقاعد شاغرة',ADMIN,DT(2)]])
ins('risk_indicators',['school_id','code','label','threshold','is_active'],[[SID,c,l,t,1] for c,l,t in [('absence_rate','نسبة الغياب',20),('consecutive_absence','أيام الغياب المتتالية',3),('low_average','انخفاض المعدل',50),('grade_drop','هبوط المعدل بين الفصول',15),('discipline','عدد المخالفات',3),('fee_arrears','تأخر الرسوم (أيام)',60)]],ignore=True)
ins('risk_flags',['school_id','student_id','indicator_code','explanation','status','reviewed_by','note','reviewed_at','metric'],[
 [SID,I('students',7),'absence_rate','غاب 6 من 10 أيام مسجّلة (60%) خلال آخر 30 يومًا — الحد 20%','open',None,None,None,60],
 [SID,I('students',7),'low_average','معدل «الفصل الأول» أقل من الحد 50%','reviewed',I('users',6),'تم التواصل مع ولي الأمر',DT(1),38.5]])
ins('counseling_cases',['id','school_id','student_id','counselor_user_id','title','notes_enc','status','kind','risk_flag_id','updated_at'],[
 [I('counseling_cases',1),SID,I('students',7),I('users',6),'متابعة الغياب (تجريبي)',None,'open','social',None,DT(0)],
 [I('counseling_cases',2),SID,I('students',4),I('users',6),'توجيه دراسي (تجريبي)',None,'closed','academic',None,DT(5)]])
w("-- ملاحظة: نصوص الإرشاد تُشفَّر بمفتاح التطبيق (APP_ENC_KEY) فلا يمكن توليدها في SQL؛ أضف ملاحظات حقيقية من الواجهة.")
ins('counseling_notes',['school_id','case_id','user_id','note_enc'],[[SID,I('counseling_cases',1),I('users',6),'']])
w("\n-- المرافق والشهادات")
ins('facilities',['id','school_id','name','kind','capacity','status'],[[I('facilities',1),SID,'مختبر الحاسوب (تجريبي)','lab',25,'available'],[I('facilities',2),SID,'المكتبة (تجريبي)','library',40,'available'],[I('facilities',3),SID,'الملعب (تجريبي)','sports',100,'maintenance']])
ins('facility_bookings',['school_id','facility_id','booked_by','starts_at','ends_at','purpose'],[[SID,I('facilities',1),I('users',1),DT(-1,'09:00:00'),DT(-1,'10:00:00'),'حصة حاسوب'],[SID,I('facilities',2),I('users',2),DT(-2,'11:00:00'),DT(-2,'12:00:00'),'نشاط قراءة'],[SID,I('facilities',1),I('users',3),DT(-3,'09:00:00'),DT(-3,'11:00:00'),'اختبار عملي']])
ins('credentials',['school_id','student_id','title','verify_code','issued_on','issued_by','kind','notes'],[[SID,I('students',1),'شهادة تفوق دراسي','DEMO-AAAA-2222',D(3),ADMIN,'excellence',None],[SID,I('students',2),'شهادة مشاركة','DEMO-BBBB-3333',D(8),ADMIN,'participation',None]])

# ---------- عام ----------
w("\n-- الاستيراد والتدقيق والإعدادات")
ins('import_jobs',['id','school_id','user_id','kind','original_name','stored_name','status','total_rows','ok_rows','error_rows'],[[I('import_jobs',1),SID,ADMIN,'students','طلاب-تجريبي.xlsx','demo-import-none','committed',3,2,1]])
ins('import_rows',['job_id','row_no','data','status','errors'],[[I('import_jobs',1),2,json.dumps({'full_name':'طالب مستورد'},ensure_ascii=False),'imported',None],[I('import_jobs',1),3,json.dumps({'full_name':''},ensure_ascii=False),'error',json.dumps(['الاسم مطلوب'],ensure_ascii=False)]])
ins('audit_logs',['school_id','user_id','action','entity','entity_id','new_values','ip'],[[SID,ADMIN,'demo.seed','school',SID,json.dumps({'note':'تحميل بيانات تجريبية'},ensure_ascii=False),'127.0.0.1']])
ins('settings',['school_id','skey','svalue'],[[SID,'fees.invoice_prefix','INV'],[SID,'fees.receipt_prefix','RC']],ignore=True)
w("\n-- ربط المواد بالصفوف (المطلوب منذ الإصدار 0.11)
INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id)
  SELECT DISTINCT ss.subject_id, sc.grade_level_id, ss.school_id FROM section_subjects ss JOIN sections sc ON sc.id = ss.section_id WHERE ss.school_id = @sid;
INSERT IGNORE INTO subject_grade_levels (subject_id, grade_level_id, school_id)
  SELECT s.id, g.id, s.school_id FROM subjects s JOIN grade_levels g ON g.school_id = s.school_id
  WHERE s.school_id = @sid AND NOT EXISTS (SELECT 1 FROM subject_grade_levels x WHERE x.subject_id = s.id);

-- توحيد صيغة الهواتف إلى الدولية (+967...) كما يفعل النظام عند الإدخال؛ ضروري لدخول ولي الأمر برمز SMS
UPDATE users SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE school_id = @sid AND phone REGEXP '^0[0-9]{9}$';
UPDATE staff SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE school_id = @sid AND phone REGEXP '^0[0-9]{9}$';
UPDATE students SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE school_id = @sid AND phone REGEXP '^0[0-9]{9}$';
UPDATE guardians SET phone = CONCAT('+967', SUBSTRING(phone, 2)) WHERE phone REGEXP '^0[0-9]{9}$' AND id IN (SELECT guardian_id FROM student_guardians WHERE school_id = @sid);
UPDATE guardians SET whatsapp = CONCAT('+967', SUBSTRING(whatsapp, 2)) WHERE whatsapp REGEXP '^0[0-9]{9}$' AND id IN (SELECT guardian_id FROM student_guardians WHERE school_id = @sid);

SET FOREIGN_KEY_CHECKS = 1; SET SQL_MODE = @OLD_SQL_MODE;")
w("SELECT 'تم تحميل البيانات التجريبية. كلمة مرور الحسابات: Demo12345' AS result;")
open('/home/user/school-platform/database/demo/demo-data.sql','w',encoding='utf8').write('\n'.join(out)+'\n')
print(len(out),'lines')
