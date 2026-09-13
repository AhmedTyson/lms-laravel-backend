# -*- coding: utf-8 -*-
"""Bilingual LMS report builder: Egyptian Arabic narrative + English schema + rules."""
import arabic_reshaper
from bidi.algorithm import get_display
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (SimpleDocTemplate, Paragraph, Spacer, Table,
                                TableStyle, PageBreak, KeepTogether)

pdfmetrics.registerFont(TTFont('Arabic', r'C:\Windows\Fonts\arabtype.ttf'))
NAVY = colors.HexColor('#1F3864')
ACCENT = colors.HexColor('#C9A227')
LIGHT = colors.HexColor('#EDF1F7')
GREY = colors.HexColor('#5A5A5A')

S = {
    'cover_title': ParagraphStyle('ct', fontName='Helvetica-Bold', fontSize=26, textColor=NAVY, spaceAfter=6),
    'cover_ar': ParagraphStyle('ca', fontName='Arabic', fontSize=20, textColor=NAVY, alignment=2, spaceAfter=6),
    'h1': ParagraphStyle('h1', fontName='Helvetica-Bold', fontSize=18, textColor=NAVY, spaceBefore=18, spaceAfter=8,
                         borderPadding=(0, 0, 4, 0)),
    'h2': ParagraphStyle('h2', fontName='Helvetica-Bold', fontSize=13, textColor=colors.HexColor('#2E5AAC'), spaceBefore=12, spaceAfter=5),
    'h1ar': ParagraphStyle('h1ar', fontName='Arabic', fontSize=17, textColor=NAVY, alignment=2, spaceBefore=16, spaceAfter=6),
    'body': ParagraphStyle('b', fontName='Helvetica', fontSize=10.5, leading=15, spaceAfter=4),
    'body_ar': ParagraphStyle('ba', fontName='Arabic', fontSize=12.5, leading=20, alignment=2, spaceAfter=4),
    'bullet': ParagraphStyle('bl', fontName='Helvetica', fontSize=10.5, leading=15, leftIndent=18, spaceAfter=3,
                             bulletIndent=8),
    'cell': ParagraphStyle('c', fontName='Helvetica', fontSize=8.5, leading=11),
    'cell_h': ParagraphStyle('ch', fontName='Helvetica-Bold', fontSize=8.5, leading=11, textColor=colors.white),
    'mono': ParagraphStyle('m', fontName='Courier', fontSize=8.5, leading=11),
    'rule_ar': ParagraphStyle('ra', fontName='Arabic', fontSize=11.5, leading=18, alignment=2, textColor=colors.HexColor('#333333'), spaceAfter=6),
    'foot': ParagraphStyle('f', fontName='Helvetica', fontSize=8, textColor=GREY, alignment=1),
}


def ar(t):
    return get_display(arabic_reshaper.reshape(t))


def P(text, style='body'):
    return Paragraph(text, S[style])


def PAR(text):
    return Paragraph(ar(text), S['body_ar'])


story = []
A = story.append


def hrule_box(title, rows):
    """rows: list of [col1, col2] already Paragraphs; styled table."""
    t = Table(rows, colWidths=[4.2 * cm, 12.3 * cm])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), NAVY),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTSIZE', (0, 0), (-1, -1), 8.5),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor('#B0B8C8')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, LIGHT]),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
    ]))
    A(P(f'<b>{title}</b>', 'h2'))
    A(t)
    A(Spacer(1, 6))


# ---------------- COVER ----------------
A(Spacer(1, 4 * cm))
A(P('LMS Backend — Project Report', 'cover_title'))
A(Paragraph(ar('تقرير مشروع نظام إدارة التعلم — اللي اتعمل لحد دلوقتي'), S['cover_ar']))
A(Spacer(1, 8))
A(P('Laravel 13 · MySQL · Modular monolith (nWidart) · JWT + Spatie Teams<br/>'
    'Phases 1–3 + pre-Phase 7 refinement (ADR-011–015)<br/>'
    'Repository: github.com/AhmedTyson/lms-laravel-backend · September 2026'))
A(Spacer(1, 12))
A(P('Part 1 — Egyptian Arabic: what was built and why &nbsp;|&nbsp; Part 2 — English: full database schema &nbsp;|&nbsp; '
    'Part 3 — every rule: what it requires and what we implemented exactly.'))

# ---------------- PART 1 (ARABIC) ----------------
A(PageBreak())
A(Paragraph(ar('الجزء الأول: اللي اتبنى لحد دلوقتي — بالعامية'), S['h1ar']))
A(PAR('المشروع ده Backend لمنصة تعليمية (LMS): المدرس يعمل كورسات ودروس وواجبات وامتحانات، والطالب يسجل ويذاكر ويتقدم، والإدارة تشوف كل حاجة. زيادة على كده فيه نظام صلاحيات عام (مين يقدر يدي صلاحية لمين). الشغل متقسم مراحل، وكل مرحلة بتتقفل بمراجعة قبل اللي بعدها.'))
A(Paragraph(ar('المرحلة الأولى: التأسيس'), S['h1ar']))
A(PAR('نزلنا Laravel 13 من الصفر، وركبنا كل الباكدجات المطلوبة: تسجيل الدخول بـ JWT، الصلاحيات بـ Spatie (وضع الفرق)، سجل المراقبة، توثيق الـ API، الاختبارات بـ Pest، تنسيق الكود بـ Pint، والتحليل الاستاتيكي. وعملنا CI على GitHub بيشغل نفس الأربع فحوصات مع كل Push. كمان ظبطنا إن الشغل المحلي يشتغل بـ SQLite من غير ما تحتاج MySQL أو Redis.'))
A(Paragraph(ar('المرحلة الثانية: الهيكل'), S['h1ar']))
A(PAR('قسمنا المشروع 9 موديولات (Auth، الصلاحيات، الكورسات، التسجيل، الواجبات، الامتحانات، التقدم، الإشعارات، التقارير) بهيكل فاضي نضيف، وفعلنا JWT كبوابة الـ API الوحيدة، وركبنا لوحة تحكم Filament للإدارة، وعملنا ملف اتفاقيات قاعدة البيانات والمذكرات المعمارية (ADR) وسجل البذر اللي بيعمل حساب الأدمن الأول من متغيرات البيئة مش متكتب في الكود.'))
A(Paragraph(ar('المرحلة الثالثة: قاعدة البيانات'), S['h1ar']))
A(PAR('بنينا الـ 19 جدول بتوع المواصفة بكل العلاقات والفهارس اللي بتمنع السباقات (التسجيل المكرر، رقم المحاولة المكرر، ترتيب الدروس). وعملنا الموديلات والعلاقات ومنها العلاقة المتعددة الأشكال بتاعة إتمام المكونات، واختبارات بتثبت إن الجداول موجودة وإن القيود شغالة فعلا مش على الورق بس.'))
A(Paragraph(ar('التنقيح قبل المرحلة السابعة'), S['h1ar']))
A(PAR('مراجعة معمارية طلعت 5 تعديلات: تثبيت صريح لمنع حذف المستخدمين اللي عندهم بيانات، جدول قراءة سريع للصلاحيات بدل مسح السجل كل مرة، عمود عمق الشجرة لتسريع القراءة، أرشفة دورية لسجل المراقبة كل 90 يوم، ومراقب يمنع إتمام يتيم لما درس أو واجب أو امتحان يتمسح. كل قرار اتسجل في مذكرة ADR قبل الكود، واتعمل له اختبار.'))
A(PAR('الخلاصة: المشروع واقف على أرض صلبة — بيئة خضرا، قاعدة بيانات كاملة ومختبرة، وقواعد اللعبة متسجلة. اللي جاي: الـ API (التسجيل والدخول والموافقات)، وبعدين منطق التفويض والصلاحيات.'))

# ---------------- PART 2 (SCHEMA EN) ----------------
A(PageBreak())
A(P('Part 2 — Database Schema (English)', 'h1'))
A(P('20 tables (19 spec + <b>user_permissions</b> read model, ADR-012). Auto-increment integer PKs everywhere (ARCH-005). '
    'Portable SQL: runs on SQLite (dev) and MySQL (prod). Conventions: <b>UQ</b> = unique, <b>*</b> = NOT NULL.'))

TABLES = [
    ('users <i>(Auth ext: manager_id, approval_status, manager_depth)</i>',
     [['id', 'bigint PK *'], ['name', 'varchar(255) *'], ['email', 'varchar(255) * UQ'], ['email_verified_at', 'timestamp'],
      ['password', 'varchar(255) *'], ['manager_id', 'FK → users NULL (RULE-002)'], ['manager_depth', 'smallint default 0 (ADR-013 cache)'],
      ['approval_status', 'pending|approved NULL (RULE-010)']],
     'Population registry. One manager per user = Admin-rooted tree; students always NULL.'),
    ('groups <i>(owner_id → users; name NOT unique RULE-011)</i>',
     [['id', 'bigint PK * (= Spatie team_id)'], ['owner_id', 'FK → users *'], ['name', 'varchar(255) *']],
     'Permission scopes (“rooms”), not ranks. Ownership succession on removal (RULE-012).'),
    ('group_members <i>UQ(group_id, user_id)</i>',
     [['id', 'bigint PK *'], ['group_id', 'FK → groups *'], ['user_id', 'FK → users *'], ['joined_at', 'timestamp *']],
     'Guest list only — membership grants zero capabilities (RULE-009).'),
    ('permission_grants <i>append-only, no updated_at (RULE-005)</i>',
     [['id', 'bigint PK *'], ['granter_id', 'FK → users * RESTRICT'], ['grantee_id', 'FK → users * RESTRICT'],
      ['group_id', 'FK → groups NULL (=global)'], ['permission_name', 'varchar *'], ['action', 'granted|revoked *'],
      ['granted_at / revoked_at', 'timestamps'], ['created_at', 'timestamp *']],
     'Court record of delegation: subordinate-only (RULE-003) + ceiling (RULE-004), explicit team context (ADR-008).'),
    ('user_permissions <i>#20, UQ(user, permission, group) — ADR-012</i>',
     [['id', 'bigint PK *'], ['user_id', 'FK → users * cascade'], ['permission_name', 'varchar *'],
      ['group_id', 'FK → groups NULL'], ['granted_via_grant_id', 'FK → permission_grants *']],
     'O(1) authorization read model: CURRENT grants only, mirrored atomically with the ledger.'),
    ('courses <i>UQ(instructor, title)</i>',
     [['id', 'bigint PK *'], ['instructor_id', 'FK → users * RESTRICT'], ['title / category', 'varchar *'],
      ['description', 'text'], ['status', 'draft|published|archived *'], ['published_at / archived_at', 'timestamps']],
     'Linear lifecycle Draft→Published→Archived (RULE-013); archive blocked with active enrollments.'),
    ('lessons <i>UQ(course_id, order) RULE-014</i>',
     [['id', 'bigint PK *'], ['course_id', 'FK → courses * cascade'], ['title', 'varchar *'],
      ['content_reference', 'text (external pointer)'], ['order', 'uint *']],
     'Numbered chapters; progress references IDs so reorder never corrupts.'),
    ('enrollments <i>UQ(student, course)</i>',
     [['id', 'bigint PK *'], ['student_id', 'FK → users *'], ['course_id', 'FK → courses * cascade'],
      ['status', 'active|completed *'], ['enrolled_at / completed_at', 'timestamps']],
     'One seat per student per course; unique closes the concurrent-enroll race at DB level.'),
    ('assignments <i>variable scoring v2.1</i>',
     [['id', 'bigint PK *'], ['course_id', 'FK → courses * cascade'], ['title', 'varchar *'], ['description', 'text ( doubles as instructions)'],
      ['due_date', 'timestamp * required+future'], ['resubmission_allowed', 'bool default false'],
      ['max_score', 'decimal(6,2) * free-form'], ['passing_threshold', 'decimal(5,2) * % of max']],
     'Free-form ceilings (any max); passing = % of own max (RULE-018 rev).'),
    ('submissions <i>UQ(assignment, enrollment, attempt) RULE-015</i>',
     [['id', 'bigint PK *'], ['assignment_id / enrollment_id', 'FKs * cascade'], ['attempt_number', 'uint *'],
      ['file_path / content', 'both nullable'], ['submitted_at / is_late', 'timestamp / bool *'],
      ['grade / graded_by / graded_at', 'nullable'], ['status', 'submitted|graded *']],
     'All attempts preserved, independently gradable — no grade-of-record (RULE-016).'),
    ('quizzes <i>threshold = % of live point sum</i>',
     [['id', 'bigint PK *'], ['course_id', 'FK → courses * cascade'], ['title', 'varchar *'],
      ['opens_at / closes_at', 'timestamps * (window at start)'], ['max_attempts', 'uint default 1'],
      ['passing_threshold', 'decimal(5,2) *']],
     'Timed exam room; threshold recomputed from questions, never cached.'),
    ('questions <i>per-question points v2.1</i>',
     [['id', 'bigint PK *'], ['quiz_id', 'FK → quizzes * cascade'], ['prompt', 'text *'],
      ['type', '5 auto-scorable types *'], ['order', 'uint *'], ['points', 'decimal(6,2) default 1.00'],
      ['correct_boolean / correct_number / tolerance', 'type-specific, nullable']],
     'Weighted scorable moments; short answers accept variants (RULE-017).'),
    ('question_options', [['id', 'bigint PK *'], ['question_id', 'FK * cascade'], ['label', 'varchar *'],
                          ['is_correct', 'bool *'], ['order', 'uint *']], 'Pre-marked choices; grading = comparison.'),
    ('question_accepted_answers', [['id', 'bigint PK *'], ['question_id', 'FK * cascade'], ['answer_text', 'varchar *']],
     'Accepted phrasings for short answers (RULE-017).'),
    ('attempts <i>UQ(quiz, enrollment, attempt)</i>',
     [['id', 'bigint PK *'], ['quiz_id / enrollment_id', 'FKs * cascade'], ['attempt_number', 'uint *'],
      ['score', 'decimal NULL'], ['started_at / submitted_at', 'timestamps']],
     'One sitting per row; NULL score = in progress.'),
    ('attempt_answers <i>UQ(attempt, question)</i>',
     [['id', 'bigint PK *'], ['attempt_id / question_id', 'FKs * cascade'], ['selected_option_ids', 'json NULL'],
      ['answer_boolean / text / number', 'nullable'], ['is_correct', 'bool *']],
     'Per-question verdicts enabling review and appeals.'),
    ('progress_records <i>PK = enrollment FK (1-to-1)</i>',
     [['enrollment_id', 'FK PK *'], ['percent_complete', 'decimal default 0 *'], ['completed_at', 'timestamp']],
     'Dashboard percentage cache; all components done ⇒ course done.'),
    ('component_completions <i>UQ(enrollment, type, id); morph (ADR-010)</i>',
     [['id', 'bigint PK *'], ['enrollment_id', 'FK * cascade'], ['component_type', 'lesson|assignment|quiz *'],
      ['component_id', 'bigint * (no cross-FK)'], ['completed_at', 'timestamp'],
      ['is_override', 'bool default false (RULE-019)'], ['overridden_by', 'FK → users NULL']],
     'Gold stars via $completion-&gt;component; orphan-purged by observer (ADR-015).'),
    ('notifications <i>idx(user, read_at)</i>',
     [['id', 'bigint PK *'], ['user_id', 'FK * cascade'], ['type', 'varchar * (domain event)'], ['data', 'json *'],
      ['read_at', 'timestamp NULL']],
     'Queued mailbox; users see only their rows.'),
    ('activity_log <i>package-owned (ADR-006); idx(created_at) (ADR-014)</i>',
     [['id', 'bigint PK *'], ['log_name / description', 'varchar/text'], ['subject_* / causer_*', 'polymorphic, nullable'],
      ['event / properties / batch_uuid', 'nullable'], ['created_at / updated_at', 'timestamps']],
     'CCTV trail; pruned nightly after 90 days. Grants stay the court record.'),
]

for title, cols, note in TABLES:
    rows = [[P('<b>Column</b>', 'cell_h'), P('<b>Type / constraints</b>', 'cell_h')]]
    for cname, ctype in cols:
        rows.append([P(f'<font face="Courier" size="8">{cname}</font>', 'cell'), P(ctype, 'cell')])
    hrule_box(title, rows)
    A(P(f'<i>{note}</i>'))

# ---------------- PART 3 (RULES) ----------------
A(PageBreak())
A(P('Part 3 — Every Rule: what it requires, what we implemented', 'h1'))
A(PAR('كل قاعدة تحت: معناها بالإنجليزية، وبعدين سطر بالعامية بيقول عملنا إيه فيها بالظبط.'))

RULES = [
    ('RULE-001 — Published-content edits need elevated permission',
     'Editing a published course structure requires a permission instructors lack by default.',
     'Policy check lands in Phase 6 course slice; schema ready (status column).',
     'المحتوى المنشور عقد — تعديله محتاج صلاحية خاصة، وده هيتطبق في مرحلة الكورسات.'),
    ('RULE-002 — Strict single-manager tree rooted at Admin',
     'Every user has exactly one manager_id forming a tree; students always NULL.',
     'Migration 120000 adds manager_id self-FK + index; seeder sets admin NULL; Admin-first contract tested.',
     'كل واحد ليه مدير واحد بس، والشجرة جذرها الأدمن. اتنفذت في قاعدة البيانات والسيدر.'),
    ('RULE-003 — Subordinate-only delegation',
     'Grants/revokes only to direct or indirect subordinates.',
     'Enforced in Phase 5 GrantService; test matrix defined in Task 5.2 (403 otherwise).',
     'محدش يدي صلاحية لحد مش تحته في الشجرة — هيتطبق في مرحلة الصلاحيات.'),
    ('RULE-004 — Ceiling rule',
     'You can only grant permissions you personally hold (global or group-scoped).',
     'Checked with explicit team context per call (ADR-008); manager_depth cache (ADR-013) accelerates reads.',
     'متديش حاجة مش معاك — القاعدة دي قلب نظام التفويض.'),
    ('RULE-005 — Append-only audit ledger',
     'Every grant AND revoke permanently recorded; nothing updated or deleted.',
     'permission_grants has no updated_at; revoke = new row; prune command explicitly excludes it.',
     'سجل لا يتمسح: كل منح وسحب بيتسجل للأبد.'),
    ('RULE-006 — Reassignment is non-retroactive',
     'Changing a manager never rewrites history granted by the old manager.',
     'Service rule for Phase 5; history rows carry no cascade that could rewrite them.',
     'تغيير المدير مش بيغير الماضي.'),
    ('RULE-007 / RULE-008 — Global vs group-scoped permissions',
     'A grant is either global (group_id NULL) or valid inside one group.',
     'Nullable group_id FK; user_permissions mirrors both shapes with lineage.',
     'الصلاحية يا عامة يا جوَّا جروب واحد بس.'),
    ('RULE-009 — Membership ≠ permission',
     'Joining a group grants zero capabilities.',
     'Separate group_members table; test asserts member-without-grant gets 403 (Task 5.3).',
     'دخول الجروب مش بيدي أي صلاحية لوحده.'),
    ('RULE-010 — manager_id set at approval, not registration',
     'Instructor manager assigned when Admin approves, not when they register.',
     'Phase 4 approval endpoint wires ManagerAssignmentService; column nullable until then.',
     'المدير بيتحدد يوم الموافقة مش يوم التسجيل.'),
    ('RULE-011 — No group-name uniqueness',
     'Group names may repeat; groups are scopes, not identities.',
     'No unique index on groups.name (migration 120100).',
     'أسماء الجروبات ممكن تتكرر عادي.'),
    ('RULE-012 — Group ownership succession',
     'Removed owner → ownership passes to their manager, else any Admin.',
     'Phase 5 Task 5.3 with succession tests; tie-break deferred per spec.',
     'المالك لو مشي، الملكية بتروح لمديره أو لأي أدمن.'),
    ('RULE-013 — Linear course lifecycle',
     'Draft → Published → Archived only; archive blocked with active enrollments.',
     'status column + default draft; transitions enforced in Phase 6 service with tests.',
     'الكورس مسودة ثم منشور ثم مؤرشف، ومفيش رجوع.'),
    ('RULE-014 — Lesson order unique per course',
     'Two lessons in one course can never share a position.',
     'Composite unique (course_id, order); duplicate-order test proves it fires.',
     'ترتيب الدروس فريد، وقاعدة البيانات نفسها بتمنع التكرار.'),
    ('RULE-015 — Resubmission + race-safe attempts',
     'Second attempts only if allowed; attempt_number unique stops race duplicates.',
     'Composite unique on submissions + attempts; concurrent-submit test in Phase 8/9 tasks.',
     'مفيش محاولة تانية إلا لو مسموح، والسباقات مقفولة بفهرس فريد.'),
    ('RULE-016 — No grade-of-record',
     'All attempts preserved and independently gradable.',
     'No summary column exists by design; grading writes per-attempt grade only.',
     'كل المحاولات محفوظة ومفيش درجة واحدة بتمسح الباقي.'),
    ('RULE-017 — Multi-variant short answers',
     'Short-answer questions accept several correct phrasings.',
     'question_accepted_answers table; scoring compares against all variants (Phase 9).',
     'الإجابة المقالية ليها كذا صياغة مقبولة.'),
    ('RULE-018 (revised v2.1) — Percentage thresholds over variable totals',
     'Completion = best attempt ≥ threshold % of that component’s own max (not 0–100).',
     'questions.points, quizzes.passing_threshold, assignments.max_score+passing_threshold columns; Quiz::totalPoints() sums live.',
     'النجاح نسبة من مجموع كل مكوّن على حدة، مش من 100 ثابتة.'),
    ('RULE-019 — Instructor override',
     'Instructor/Admin may manually mark completion, attributed.',
     'is_override + overridden_by columns; override path in Phase 10 engine.',
     'المدرس يقدر ينجح حد يدوي، بس باسمه متسجل.'),
    ('SCOPE-001 — Payments are future scope', 'Out of scope now.', 'Nothing payment-shaped exists anywhere.', 'الدفع مش دلوقتي.'),
    ('SCOPE-002 — Access Management is a separate module',
     'Delegation is a general subsystem, not teaching logic.',
     'Own AccessManagement module: groups, grants, read model, depth helpers.', 'الصلاحيات موديول مستقل.'),
    ('SCOPE-003 — Groups are first-class scoped entities', 'Groups exist as permission scopes.',
     'groups + group_members tables; Spatie team_id = group id.', 'الجروبات كيانات أساسية.'),
    ('SCOPE-004 — Instructor approval workflow', 'Instructors browse-only until Admin approves.',
     'approval_status column; Phase 4 approval endpoint + event.', 'المدرس بيستنى موافقة الأدمن.'),
    ('SCOPE-005 — Five auto-scorable question types', 'single/multi choice, true/false, short-answer, numeric.',
     'type column + per-type answer columns; ScoringService in Phase 9.', 'خمسة أنواع أسئلة بتتصحح آلي.'),
    ('ARCH-005 — Auto-increment IDs everywhere', 'No UUIDs, no exceptions.',
     'Every migration uses $table->id(); platform pin keeps lock consistent.', 'أرقام مسلسلة في كل الجداول.'),
    ('ARCH-CONSTRAINT-002 — Mandated stack', 'Tymon JWT, Spatie Teams, Redis, Activitylog, Scramble, Pest, Pint.',
     'All installed with L13-compatible versions recorded in README/AGENTS.md.', 'الباكدجات المطلوبة كلها راكبة.'),
    ('ADR-006 — activity_log formalized as table #19', 'Package-owned table documented, not mistaken for drift.',
     'Spatie publish untouched; reproduced in spec §5.20.', 'جدول المراقبة بتاع الباكدج متوثق.'),
    ('ADR-007 — Service-layer cycle prevention', 'No DB constraint can police a tree; transactional service check instead.',
     'Rule locked; ManagerAssignmentService arrives in Phase 5 with FOR UPDATE + dedicated exception.', 'منع الحلقات بخدمة مش بقيد قاعدة بيانات.'),
    ('ADR-008 — Spatie Teams context split', 'Ambient for single-group flows; explicit per-call for grants + reporting.',
     'Lint-able convention; stale-context regression tests required in Tasks 5.2/12.1.', 'سياق الفريق صريح في الحساس، وضمني في العادي.'),
    ('ADR-009 — Admin seeder first', 'Exactly one root Admin from .env, manager NULL, before all else.',
     'DatabaseSeeder firstOrCreate + Schema guard; verified seeding 1 admin; never hardcoded.', 'الأدمن الأول من ملف البيئة.'),
    ('ADR-010 — Polymorphic completions', 'morphTo/morphMany, no cross-table FK; code-layer only.',
     'ComponentCompletion::component() + morph map + completions() on all three; SchemaTest proves resolution.', 'علاقة مرنة بدل مفاتيح مستحيلة.'),
    ('ADR-011 — Explicit RESTRICT on user-owned FKs', 'Course/grant owners cannot be hard-deleted; soft-delete only.',
     'Migration 120600 restrictOnDelete (MySQL; SQLite skips by design); delete-blocked tests green.', 'صاحب الكورسات والسجلات مبيتمسحش.'),
    ('ADR-012 — user_permissions read model (table #20)', 'O(1) auth reads; ledger stays history source; atomic writes.',
     'Table + UserPermission model + PermissionReadModelSync seam tested; Phase 5 wires atomicity.', 'جدول قراءة سريع جنب سجل التاريخ.'),
    ('ADR-013 — manager_depth cache', 'Denormalized depth (root=0), subtree recompute in-transaction, no cap.',
     'Column + ManagerDepth helper tested on 3-level tree; Phase 5 service calls it post-cycle-check.', 'عمق الشجرة متخزن للسرعة وبيتحدث مع كل نقل.'),
    ('ADR-014 — 90-day activity_log retention', 'Nightly prune via indexed created_at; grants never pruned.',
     'Migration 120603 index + PruneActivityLog command + daily schedule + env var; both prune tests green.', 'سجل المراقبة بيتنضف كل 90 يوم.'),
    ('ADR-015 — Orphan protection without FK', 'Observer purges completions when components die; trashed-keep rule for future.',
     'ComponentDeletionObserver on all three models + deleted()/forceDeleted() semantics; 2 purge tests green.', 'مراقب يمسح الإتمام اليتيم لما المكوّن يتمسح.'),
]

for title, req, impl, arg in RULES:
    A(P(f'<b>{title}</b>', 'h2'))
    A(P(f'<b>Requires:</b> {req}'))
    A(P(f'<b>Implemented:</b> {impl}'))
    A(Paragraph(ar(arg), S['rule_ar']))

A(PageBreak())
A(P('Verification & sign-off', 'h1'))
A(P('Gates run in order, all green: <b>Pint</b> passed · <b>PHPStan</b> (Larastan wired) no errors · '
    '<b>migrate:fresh</b> 28 migrations · <b>Pest</b> 20 passed / 78 assertions (9 RefinementTest + 11 existing).<br/>'
    'CI mirrors the same four checks on PHP 8.4. Phase checkpoints awaiting human sign-off before Phase 4 (Auth slice).'))
A(Paragraph(ar('كل البوابات خضرا: التنسيق والتحليل والترحيل والاختبارات. المشروع جاهز لمرحلة الـ API.'), S['body_ar']))

doc = SimpleDocTemplate(r'C:\Programming\LMS-Project\docs\LMS-Report.pdf', pagesize=A4,
                        leftMargin=1.6 * cm, rightMargin=1.6 * cm, topMargin=1.6 * cm, bottomMargin=1.8 * cm,
                        title='LMS Backend Report', author='LMS Team')


def footer(canvas, d):
    canvas.saveState()
    canvas.setFont('Helvetica', 8)
    canvas.setFillColor(GREY)
    canvas.drawCentredString(A4[0] / 2, 1.1 * cm, f'LMS Backend Report — Page {d.page}')
    canvas.restoreState()


doc.build(story, onFirstPage=footer, onLaterPages=footer)
print('PDF OK')
