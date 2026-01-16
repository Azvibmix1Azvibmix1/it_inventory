<?php
require APPROOT . '/views/inc/header.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$username = $data['username'] ?? '';
$name     = $data['name'] ?? '';
$email    = $data['email'] ?? '';
$password = $data['password'] ?? '';
$role     = $data['role'] ?? 'User';

$username_err = $data['username_err'] ?? '';
$name_err     = $data['name_err'] ?? '';
$email_err    = $data['email_err'] ?? '';
$password_err = $data['password_err'] ?? '';
$role_err     = $data['role_err'] ?? '';
?>

<style>
  :root{
    /* Base */
    --bg: #e9edf3;
    --card: #eef2f7;
    --text: #0b1220;
    --muted: #51607a;

    /* Neumorphism shadows */
    --shadowDark: rgba(163,177,198,.55);
    --shadowLight: rgba(255,255,255,.92);

    /* Fields */
    --fieldBg: #f3f6fb;
    --fieldBorder: rgba(15,23,42,.12);
    --focusRing: rgba(29,78,216,.18);

    /* Brand */
    --primary: #1d4ed8;
    --danger: #dc2626;

    --radius: 18px;
  }

  body{
    background: var(--bg) !important;
    color: var(--text) !important;
  }

  .page-wrap{ max-width: 980px; margin: 0 auto; }

  .page-title{
    font-weight: 900;
    letter-spacing: .2px;
    color: var(--text) !important;
  }

  .hint{ color: var(--muted) !important; font-size: .92rem; }
  .req{ color: var(--danger); font-weight: 800; }

  .ltr{
    direction:ltr;
    unicode-bidi: plaintext;
    text-align:left;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  }

  /* Card surface (outer) */
  .neo-surface{
    background: var(--card);
    border-radius: var(--radius);
    border: 1px solid rgba(15,23,42,.06);
    box-shadow:
      16px 16px 34px var(--shadowDark),
      -16px -16px 34px var(--shadowLight);
  }

  /* Inset fields (ALL inputs) - same as password look */
  .neo-inset{
    background: var(--fieldBg);
    border-radius: 16px;
    border: 1px solid var(--fieldBorder);
    box-shadow:
      inset 8px 8px 16px rgba(163,177,198,.55),
      inset -8px -8px 16px rgba(255,255,255,.98);
  }

  .neo-field{
    min-height: 50px;
    padding: 12px 14px;
    display:flex;
    align-items:center;
    gap: 10px;
  }

  /* Labels */
  .form-label{
    color: var(--text) !important;
    font-weight: 900;
    margin-bottom: .45rem;
  }

  /* Inputs */
  .form-control{
    border: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    height: auto !important;
    color: var(--text) !important;
    font-weight: 650;
    letter-spacing: .1px;
    width: 100%;
  }

  .form-control::placeholder{
    color: rgba(11,18,32,.42) !important;
    font-weight: 600;
  }

  /* Focus */
  .neo-field:focus-within{
    box-shadow:
      inset 8px 8px 16px rgba(163,177,198,.55),
      inset -8px -8px 16px rgba(255,255,255,.98),
      0 0 0 4px var(--focusRing);
  }

  /* Validation */
  .is-invalid{
    border-color: rgba(220,38,38,.35) !important;
    box-shadow:
      inset 8px 8px 16px rgba(163,177,198,.55),
      inset -8px -8px 16px rgba(255,255,255,.98),
      0 0 0 4px rgba(220,38,38,.14) !important;
  }
  .invalid-feedback{ display:block; }

  /* Buttons general */
  .btn{
    border-radius: 14px;
    border: 0;
    font-weight: 800;
  }

  .btn-primary{
    background: var(--primary);
    color: #fff;
    box-shadow:
      10px 10px 18px rgba(163,177,198,.45),
      -10px -10px 18px rgba(255,255,255,.80);
  }
  .btn-primary:hover{ filter: brightness(.98); transform: translateY(-1px); }

  .btn-outline-secondary{
    background: var(--card);
    color: var(--text);
    box-shadow:
      10px 10px 18px rgba(163,177,198,.45),
      -10px -10px 18px rgba(255,255,255,.92);
  }
  .btn-outline-secondary:hover{ transform: translateY(-1px); }

  /* Buttons inside fields (eye, generate, copy) */
  .neo-field .btn-outline-secondary{
    padding: 7px 10px;
    border-radius: 13px;
    background: var(--card);
    box-shadow:
      7px 7px 14px rgba(163,177,198,.45),
      -7px -7px 14px rgba(255,255,255,.95);
    font-weight: 900;
  }
  .neo-field .btn-outline-secondary:active{
    transform: translateY(0);
    box-shadow:
      inset 6px 6px 12px rgba(163,177,198,.55),
      inset -6px -6px 12px rgba(255,255,255,.95);
  }

  /* Role cards */
  .role-card{
    background: var(--card);
    border-radius: 16px;
    padding: 14px 16px;
    cursor:pointer;
    border: 1px solid rgba(255,255,255,.65);
    box-shadow:
      12px 12px 22px rgba(163,177,198,.50),
      -12px -12px 22px rgba(255,255,255,.95);
    transition: .15s;
    color: var(--text);
  }
  .role-card:hover{ transform: translateY(-1px); }
  .role-card.active{
    box-shadow:
      inset 8px 8px 16px rgba(163,177,198,.55),
      inset -8px -8px 16px rgba(255,255,255,.98),
      0 0 0 4px var(--focusRing);
    border-color: rgba(29,78,216,.22);
  }

  /* Divider */
  .soft-hr{
    border: 0;
    height: 1px;
    background: linear-gradient(to left, rgba(0,0,0,0), rgba(15,23,42,.12), rgba(0,0,0,0));
    margin: 18px 0;
  }

  /* Password strength meter */
  .pw-meter{
    height: 8px;
    border-radius: 999px;
    background: rgba(11,18,32,.10);
    overflow: hidden;
  }
  .pw-meter > div{
    height: 100%;
    width: 0%;
    transition: width .2s;
  }
</style>



<div class="container-fluid py-4">
  <div class="page-wrap">

    <div class="d-flex align-items-center justify-content-between mb-3">
      <a class="btn btn-outline-secondary"
         href="<?= URLROOT; ?>/index.php?page=users/index">
        ← رجوع
      </a>

      <div class="text-end">
        <h3 class="mb-0 fw-bold">إضافة مستخدم جديد</h3>
        <div class="text-muted small">عبّئ البيانات الأساسية ثم اختر الصلاحية.</div>
      </div>
    </div>

    <div class="card card-soft neo-surface">
  <div class="card-body p-4">


        <form id="userAddForm" method="post" action="<?= URLROOT; ?>/index.php?page=users/add" autocomplete="off">

          <!-- صف 1 -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
              <label class="form-label fw-bold">اسم المستخدم (Username) <span class="req">*</span></label>
              <input
                type="text"
                name="username"
                id="username"
                class="form-control ltr <?= $username_err ? 'is-invalid' : ''; ?>"
                value="<?= h($username); ?>"
                placeholder="مثال: aziz"
                inputmode="latin"
                autocomplete="new-username"
              >
              <div class="hint mt-1">إذا تركته فارغًا سيتم توليده من البريد تلقائيًا.</div>
              <?php if ($username_err): ?><div class="invalid-feedback"><?= h($username_err); ?></div><?php endif; ?>
            </div>

            <div class="col-12 col-lg-6">
              <label class="form-label fw-bold">الاسم الكامل <span class="req">*</span></label>
              <input
                type="text"
                name="name"
                class="form-control <?= $name_err ? 'is-invalid' : ''; ?>"
                value="<?= h($name); ?>"
                placeholder="مثال: عبدالعزيز فلاته"
                autocomplete="name"
              >
              <?php if ($name_err): ?><div class="invalid-feedback"><?= h($name_err); ?></div><?php endif; ?>
            </div>
          </div>

          <!-- صف 2 -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
              <label class="form-label fw-bold">البريد الإلكتروني <span class="req">*</span></label>
              <input
                type="email"
                name="email"
                id="email"
                class="form-control ltr <?= $email_err ? 'is-invalid' : ''; ?>"
                value="<?= h($email); ?>"
                placeholder="name@uj.edu.sa"
                autocomplete="email"
              >
              <div class="hint mt-1">يفضّل استخدام البريد الرسمي إن وجد.</div>
              <?php if ($email_err): ?><div class="invalid-feedback"><?= h($email_err); ?></div><?php endif; ?>
            </div>

            <div class="col-12 col-lg-6">
  <label class="form-label fw-bold">كلمة المرور <span class="req">*</span></label>

  <div class="neo-inset neo-field <?= $password_err ? 'is-invalid' : ''; ?>">
    <button class="btn btn-sm btn-outline-secondary" type="button" id="togglePw" title="إظهار/إخفاء">👁️</button>

    <input
      type="password"
      name="password"
      id="password"
      class="form-control ltr"
      value="<?= h($password); ?>"
      placeholder="••••••"
      autocomplete="new-password"
      minlength="6"
    >

    <button class="btn btn-sm btn-outline-secondary" type="button" id="genPw" title="توليد كلمة مرور">🎲</button>
    <button class="btn btn-sm btn-outline-secondary" type="button" id="copyPw">نسخ</button>
  </div>

  <div class="d-flex align-items-center justify-content-between mt-2">
    <div class="hint">يفضّل 6 أحرف على الأقل.</div>
    <div class="hint" id="pwText">—</div>
  </div>

  <div class="pw-meter mt-2"><div id="pwBar"></div></div>

  <?php if ($password_err): ?><div class="text-danger small mt-1"><?= h($password_err); ?></div><?php endif; ?>
</div>

          </div>

          <!-- الدور -->
          <div class="mb-3">
            <hr class="my-4" style="opacity:.12;">
            <label class="form-label fw-bold">نوع الحساب (الصلاحية) <span class="req">*</span></label>

            <!-- نخلي الاختيار UX على شكل Cards لكن نرسل value في select مخفي (عشان الخلفية ما تتأثر) -->
            <input type="hidden" name="role" id="role" value="<?= h($role); ?>">

            <div class="row g-3">
              <?php
                $roles = [
                  'User' => ['title'=>'موظف', 'desc'=>'استخدام النظام فقط حسب الصلاحيات.'],
                  'Manager' => ['title'=>'مدير', 'desc'=>'متابعة المستخدمين/المهام التابعة حسب إعداداتك.'],
                  'Super Admin' => ['title'=>'سوبر أدمن', 'desc'=>'صلاحيات كاملة لإدارة المستخدمين.'],
                ];
              ?>
              <?php foreach ($roles as $key => $meta): ?>
                <div class="col-12 col-lg-4">
                  <div class="role-card <?= ($role === $key ? 'active' : ''); ?>" data-role="<?= h($key); ?>">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="fw-bold"><?= h($meta['title']); ?></div>
                      <span class="badge text-bg-light ltr"><?= h($key); ?></span>
                    </div>
                    <div class="hint mt-2"><?= h($meta['desc']); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if ($role_err): ?><div class="text-danger small mt-2"><?= h($role_err); ?></div><?php endif; ?>
          </div>

          <!-- أزرار -->
          <div class="d-flex gap-2 mt-4">
            <a class="btn btn-outline-secondary btn-wide"
               href="<?= URLROOT; ?>/index.php?page=users/index">
              إلغاء
            </a>

            <button class="btn btn-primary btn-wide" id="submitBtn" type="submit">
              💾 حفظ البيانات
            </button>

            <div class="ms-auto hint d-none d-lg-block">
              سيتم التحقق من البريد واسم المستخدم قبل الحفظ.
            </div>
          </div>

        </form>

      </div>
    </div>

  </div>
</div>

<script>
(function(){
  const form = document.getElementById('userAddForm');
  const email = document.getElementById('email');
  const username = document.getElementById('username');
  const password = document.getElementById('password');
  const togglePw = document.getElementById('togglePw');
  const genPw = document.getElementById('genPw');
  const copyPw = document.getElementById('copyPw');
  const roleInput = document.getElementById('role');
  const roleCards = document.querySelectorAll('.role-card');
  const submitBtn = document.getElementById('submitBtn');

  // توليد Username من البريد إذا كان فاضي
  function slugifyLatin(s){
    return (s || '')
      .toLowerCase()
      .trim()
      .replace(/\s+/g,'')
      .replace(/[^a-z0-9._-]/g,''); // لاتيني فقط
  }
  function autoUsername(){
    const u = username.value.trim();
    if (u) return;
    const e = (email.value || '').trim();
    if (!e.includes('@')) return;
    const beforeAt = e.split('@')[0];
    const cand = slugifyLatin(beforeAt);
    if (cand) username.value = cand;
  }
  email.addEventListener('blur', autoUsername);
  email.addEventListener('change', autoUsername);

  // منع مسافات في username
  username.addEventListener('input', () => {
    username.value = slugifyLatin(username.value);
  });

  // Show/Hide password
  togglePw.addEventListener('click', () => {
    password.type = (password.type === 'password') ? 'text' : 'password';
  });

  // توليد كلمة مرور
  function randomPw(len=10){
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let out = '';
    for(let i=0;i<len;i++){
      out += chars[Math.floor(Math.random()*chars.length)];
    }
    return out;
  }
  genPw.addEventListener('click', () => {
    password.value = randomPw(10);
    updatePwMeter();
  });

  // نسخ كلمة المرور
  copyPw.addEventListener('click', async () => {
    try{
      await navigator.clipboard.writeText(password.value || '');
      copyPw.textContent = 'تم';
      setTimeout(()=> copyPw.textContent='نسخ', 900);
    }catch(e){
      alert('ما قدرت أنسخ. انسخ يدويًا.');
    }
  });

  // مقياس قوة بسيط
  const pwBar = document.getElementById('pwBar');
  const pwText = document.getElementById('pwText');

  function scorePw(p){
    let s = 0;
    if (!p) return 0;
    if (p.length >= 6) s += 1;
    if (p.length >= 10) s += 1;
    if (/[A-Z]/.test(p)) s += 1;
    if (/[a-z]/.test(p)) s += 1;
    if (/[0-9]/.test(p)) s += 1;
    if (/[^A-Za-z0-9]/.test(p)) s += 1;
    return Math.min(s, 6);
  }

  function updatePwMeter(){
    const p = password.value || '';
    const s = scorePw(p);
    const pct = (s/6)*100;

    pwBar.style.width = pct + '%';

    // بدون ألوان محددة بشكل قاسي: نخليها تعتمد على opacity فقط
    // (بس نحتاج لون واحد للبار عشان يبان)
    pwBar.style.background = (s <= 2) ? '#ef4444' : (s <= 4 ? '#f59e0b' : '#22c55e');

    if (!p) pwText.textContent = '—';
    else if (s <= 2) pwText.textContent = 'ضعيفة';
    else if (s <= 4) pwText.textContent = 'متوسطة';
    else pwText.textContent = 'قوية';
  }
  password.addEventListener('input', updatePwMeter);
  updatePwMeter();

  // اختيار الدور عبر cards
  roleCards.forEach(card => {
    card.addEventListener('click', () => {
      roleCards.forEach(c => c.classList.remove('active'));
      card.classList.add('active');
      roleInput.value = card.getAttribute('data-role') || 'User';
    });
  });

  // منع double submit + trim
  form.addEventListener('submit', () => {
    email.value = (email.value || '').trim().toLowerCase();
    username.value = slugifyLatin(username.value);
    if (!username.value) autoUsername();

    submitBtn.disabled = true;
    submitBtn.textContent = '... جاري الحفظ';
  });
})();
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
