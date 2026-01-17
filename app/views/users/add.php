<?php
require APPROOT . '/views/inc/header.php';

$name     = $data['name']     ?? '';
$username = $data['username'] ?? '';
$email    = $data['email']    ?? '';
$role     = $data['role']     ?? 'User';

$name_err     = $data['name_err']     ?? '';
$username_err = $data['username_err'] ?? '';
$email_err    = $data['email_err']    ?? '';
$password_err = $data['password_err'] ?? '';
$role_err     = $data['role_err']     ?? '';

$actionUrl = defined('URLROOT') ? URLROOT . '/index.php?page=users/add' : 'index.php?page=users/add';
$backUrl   = defined('URLROOT') ? URLROOT . '/index.php?page=users/index' : 'index.php?page=users/index';
?>

<style>
  :root{
    --bg: #e6ebf2;
    --card: #edf2f7;

    --text: #0b1220;
    --muted: #3f4d66;

    --shadowDark: rgba(148,163,184,.78);
    --shadowLight: rgba(255,255,255,.96);

    --fieldBg: #f7f9fc;
    --fieldBorder: rgba(2,6,23,.18);
    --focusRing: rgba(29,78,216,.20);

    --primary: #1d4ed8;
    --primary2: #2563eb;
    --danger: #dc2626;

    --radius: 20px;
  }

  body{ background: var(--bg) !important; color: var(--text) !important; }

  .page-wrap{ max-width: 980px; margin: 0 auto; padding: 18px 14px 50px; }

  .page-head{
    display:flex; align-items:flex-start; justify-content:space-between; gap: 12px;
    margin-bottom: 16px;
  }

  .page-title{
    font-weight: 900; letter-spacing: .2px; margin: 0;
    color: var(--text) !important;
  }

  .page-sub{
    margin-top: 6px;
    color: var(--muted) !important;
    font-size: .95rem;
    font-weight: 650;
  }

  .req{ color: var(--danger); font-weight: 900; }

  .ltr{
    direction:ltr;
    unicode-bidi: plaintext;
    text-align:left;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  }

  /* Outer card (clearer, more contrast) */
  .neo-surface{
    background: linear-gradient(180deg, rgba(255,255,255,.35), rgba(255,255,255,.12)), var(--card);
    border-radius: var(--radius);
    border: 1px solid rgba(2,6,23,.08);
    box-shadow:
      16px 16px 34px var(--shadowDark),
      -16px -16px 34px var(--shadowLight);
  }

  .neo-body{ padding: 20px 20px 18px; }

  /* Inset field (same for all inputs) */
  .neo-inset{
    background: linear-gradient(180deg, rgba(255,255,255,.75), rgba(255,255,255,.25)), var(--fieldBg);
    border-radius: 16px;
    border: 1px solid var(--fieldBorder);
    box-shadow:
      inset 10px 10px 18px rgba(148,163,184,.72),
      inset -10px -10px 18px rgba(255,255,255,.98);
  }

  .neo-field{
    min-height: 52px;
    padding: 12px 14px;
    display:flex;
    align-items:center;
    gap: 10px;
  }

  .neo-field:focus-within{
    box-shadow:
      inset 10px 10px 18px rgba(148,163,184,.72),
      inset -10px -10px 18px rgba(255,255,255,.98),
      0 0 0 4px var(--focusRing);
  }

  .form-label{
    color: var(--text) !important;
    font-weight: 900;
    margin-bottom: .45rem;
  }

  .form-control{
    border: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    height: auto !important;
    color: var(--text) !important;
    font-weight: 750;
    letter-spacing: .1px;
    width: 100%;
  }

  .form-control::placeholder{
    color: rgba(11,18,32,.48) !important;
    font-weight: 650;
  }

  .help{
    color: var(--muted);
    font-size: .9rem;
    font-weight: 650;
    margin-top: 8px;
  }

  .is-invalid{
    border-color: rgba(220,38,38,.40) !important;
    box-shadow:
      inset 10px 10px 18px rgba(148,163,184,.72),
      inset -10px -10px 18px rgba(255,255,255,.98),
      0 0 0 4px rgba(220,38,38,.16) !important;
  }
  .invalid-feedback{ display:block; font-weight:700; }

  .btn{
    border-radius: 14px;
    border: 0;
    font-weight: 900;
    letter-spacing: .2px;
  }

  .btn-neo{
    background: var(--card);
    color: var(--text);
    box-shadow:
      10px 10px 18px rgba(148,163,184,.55),
      -10px -10px 18px rgba(255,255,255,.96);
  }

  .btn-neo:active{
    box-shadow:
      inset 8px 8px 16px rgba(148,163,184,.68),
      inset -8px -8px 16px rgba(255,255,255,.98);
  }

  .btn-primary{
    background: linear-gradient(180deg, var(--primary2), var(--primary));
    color:#fff;
    box-shadow:
      10px 10px 18px rgba(148,163,184,.55),
      -10px -10px 18px rgba(255,255,255,.88);
  }
  .btn-primary:hover{ filter: brightness(.98); transform: translateY(-1px); }

  .field-btn{
    padding: 8px 11px;
    border-radius: 13px;
    background: var(--card);
    box-shadow:
      7px 7px 14px rgba(148,163,184,.55),
      -7px -7px 14px rgba(255,255,255,.97);
    font-weight: 900;
    color: var(--text);
    border: 0;
    display:inline-flex;
    align-items:center;
    gap: 6px;
    white-space: nowrap;
  }
  .field-btn:active{
    box-shadow:
      inset 6px 6px 12px rgba(148,163,184,.70),
      inset -6px -6px 12px rgba(255,255,255,.98);
  }

  .soft-hr{
    border: 0;
    height: 1px;
    background: linear-gradient(to left, rgba(0,0,0,0), rgba(2,6,23,.16), rgba(0,0,0,0));
    margin: 18px 0;
  }

  .grid-2{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
  }
  @media (max-width: 900px){
    .grid-2{ grid-template-columns: 1fr; }
  }

  /* Role cards */
  .roles{
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
  }
  @media (max-width: 900px){
    .roles{ grid-template-columns: 1fr; }
  }

  .role-card{
    background: linear-gradient(180deg, rgba(255,255,255,.35), rgba(255,255,255,.12)), var(--card);
    border-radius: 16px;
    padding: 14px 14px;
    cursor: pointer;
    border: 1px solid rgba(2,6,23,.08);
    box-shadow:
      12px 12px 22px rgba(148,163,184,.55),
      -12px -12px 22px rgba(255,255,255,.97);
    transition: .15s;
    user-select:none;
    position: relative;
    min-height: 88px;
  }

  .role-chip{
    position:absolute;
    top: 10px; left: 10px;
    font-size: .78rem;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.7);
    border: 1px solid rgba(2,6,23,.10);
    font-weight: 900;
  }

  .role-title{
    font-weight: 950;
    font-size: 1.08rem;
    margin: 0 0 6px;
  }
  .role-desc{
    margin: 0;
    color: var(--muted);
    font-weight: 700;
    font-size: .92rem;
    line-height: 1.4;
  }

  .role-card.active{
    box-shadow:
      inset 10px 10px 18px rgba(148,163,184,.70),
      inset -10px -10px 18px rgba(255,255,255,.98),
      0 0 0 4px var(--focusRing);
    border-color: rgba(29,78,216,.22);
  }

  /* Password meter */
  .pw-meter{
    height: 9px;
    border-radius: 999px;
    background: rgba(11,18,32,.12);
    overflow: hidden;
    margin-top: 10px;
  }
  .pw-meter > div{ height: 100%; width: 0%; transition: width .2s; background: #16a34a; }

  .pw-row{ display:flex; align-items:center; justify-content:space-between; gap: 10px; margin-top: 8px; }
  .pw-note{ color: var(--muted); font-weight: 750; font-size: .9rem; }
  .pw-badge{ font-weight: 900; font-size: .9rem; padding: 2px 10px; border-radius: 999px; background: rgba(255,255,255,.6); border:1px solid rgba(2,6,23,.10); }

  /* Footer actions */
  .actions{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 12px;
    margin-top: 14px;
    flex-wrap: wrap;
  }

  .actions .left{
    display:flex; gap:10px; align-items:center; flex-wrap: wrap;
  }

  .note-bottom{
    color: var(--muted);
    font-weight: 750;
    font-size: .92rem;
  }

</style>

<div class="page-wrap">
  <div class="page-head">
    <div>
      <h2 class="page-title">إضافة مستخدم جديد</h2>
      <div class="page-sub">عبّئ البيانات الأساسية. يمكن توليد اسم المستخدم تلقائيًا من البريد.</div>
    </div>
    <a class="btn btn-neo" href="<?php echo $backUrl; ?>">← رجوع</a>
  </div>

  <div class="neo-surface">
    <div class="neo-body">

      <form id="userAddForm" action="<?php echo $actionUrl; ?>" method="POST" autocomplete="off" novalidate>

        <div class="grid-2">

          <!-- Username -->
          <div>
            <label class="form-label" for="username">اسم المستخدم (Username) <span class="req">*</span></label>
            <div class="neo-inset neo-field <?php echo !empty($username_err) ? 'is-invalid' : ''; ?>">
              <input
                id="username"
                name="username"
                type="text"
                class="form-control ltr"
                placeholder="مثال: aziz"
                value="<?php echo htmlspecialchars($username); ?>"
                inputmode="text"
                autocapitalize="off"
                spellcheck="false"
              />
            </div>
            <?php if (!empty($username_err)): ?>
              <div class="invalid-feedback"><?php echo $username_err; ?></div>
            <?php else: ?>
              <div class="help">إذا تركته فارغًا سيتم توليده من البريد الإلكتروني.</div>
            <?php endif; ?>
          </div>

          <!-- Full name -->
          <div>
            <label class="form-label" for="name">الاسم الكامل <span class="req">*</span></label>
            <div class="neo-inset neo-field <?php echo !empty($name_err) ? 'is-invalid' : ''; ?>">
              <input
                id="name"
                name="name"
                type="text"
                class="form-control"
                placeholder="مثال:  محمد"
                value="<?php echo htmlspecialchars($name); ?>"
              />
            </div>
            <?php if (!empty($name_err)): ?>
              <div class="invalid-feedback"><?php echo $name_err; ?></div>
            <?php endif; ?>
          </div>

          <!-- Email -->
          <div>
            <label class="form-label" for="email">البريد الإلكتروني <span class="req">*</span></label>
            <div class="neo-inset neo-field <?php echo !empty($email_err) ? 'is-invalid' : ''; ?>">
              <input
                id="email"
                name="email"
                type="email"
                class="form-control ltr"
                placeholder="name@uj.edu.sa"
                value="<?php echo htmlspecialchars($email); ?>"
                autocapitalize="off"
                spellcheck="false"
              />
            </div>
            <?php if (!empty($email_err)): ?>
              <div class="invalid-feedback"><?php echo $email_err; ?></div>
            <?php else: ?>
              <div class="help">يفضّل استخدام البريد الرسمي إن وجد.</div>
            <?php endif; ?>
          </div>

          <!-- Password -->
          <div>
            <label class="form-label" for="password">كلمة المرور <span class="req">*</span></label>

            <div class="neo-inset neo-field <?php echo !empty($password_err) ? 'is-invalid' : ''; ?>">
              <button type="button" class="field-btn" id="btnGen">
                 توليد
              </button>

              <input
                id="password"
                name="password"
                type="password"
                class="form-control ltr"
                placeholder="••••••••"
                value=""
              />

              <button type="button" class="field-btn" id="btnToggle">
                 عرض
              </button>

              <button type="button" class="field-btn" id="btnCopy">
                 نسخ
              </button>
            </div>

            <?php if (!empty($password_err)): ?>
              <div class="invalid-feedback"><?php echo $password_err; ?></div>
            <?php else: ?>
              <div class="pw-row">
                <div class="pw-note">6 أحرف على الأقل.</div>
                <div class="pw-badge" id="pwStrengthLabel">—</div>
              </div>
              <div class="pw-meter" aria-hidden="true"><div id="pwStrengthBar"></div></div>
            <?php endif; ?>
          </div>

        </div>

        <hr class="soft-hr" />

        <!-- Role -->
        <div>
          <label class="form-label">نوع الحساب (الصلاحية) <span class="req">*</span></label>

          <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($role); ?>">

          <div class="roles">
            <div class="role-card <?php echo ($role === 'User') ? 'active' : ''; ?>" data-role="User" tabindex="0">
              <span class="role-chip">User</span>
              <div class="role-title">موظف</div>
              <p class="role-desc">استخدام النظام فقط حسب الصلاحيات.</p>
            </div>

            <div class="role-card <?php echo ($role === 'Manager') ? 'active' : ''; ?>" data-role="Manager" tabindex="0">
              <span class="role-chip">Manager</span>
              <div class="role-title">مدير</div>
              <p class="role-desc">متابعة المستخدمين/المهام التابعة حسب إعداداتك.</p>
            </div>

            <div class="role-card <?php echo ($role === 'SuperAdmin') ? 'active' : ''; ?>" data-role="SuperAdmin" tabindex="0">
              <span class="role-chip">Super Admin</span>
              <div class="role-title">سوبر أدمن</div>
              <p class="role-desc">صلاحيات كاملة لإدارة المستخدمين.</p>
            </div>
          </div>

          <?php if (!empty($role_err)): ?>
            <div class="invalid-feedback" style="margin-top:8px;"><?php echo $role_err; ?></div>
          <?php else: ?>
            <div class="help" style="margin-top:10px;">
              سيتم التحقق من البريد واسم المستخدم قبل الحفظ.
            </div>
          <?php endif; ?>
        </div>

        <div class="actions">
          <div class="note-bottom">تأكد من اختيار الصلاحية المناسبة قبل الحفظ.</div>

          <div class="left">
            <a class="btn btn-neo" href="<?php echo $backUrl; ?>">إلغاء</a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
               حفظ البيانات
            </button>
          </div>
        </div>

      </form>

    </div>
  </div>
</div>

<script>
(function(){
  const emailEl = document.getElementById('email');
  const userEl  = document.getElementById('username');
  const passEl  = document.getElementById('password');
  const formEl  = document.getElementById('userAddForm');

  const btnGen    = document.getElementById('btnGen');
  const btnCopy   = document.getElementById('btnCopy');
  const btnToggle = document.getElementById('btnToggle');

  const strengthBar   = document.getElementById('pwStrengthBar');
  const strengthLabel = document.getElementById('pwStrengthLabel');

  const roleInput = document.getElementById('roleInput');
  const roleCards = document.querySelectorAll('.role-card');

  // Helpers
  function trimAll(){
    if (emailEl) emailEl.value = (emailEl.value || '').trim();
    if (userEl)  userEl.value  = (userEl.value  || '').trim();
  }

  function sanitizeUsername(s){
    s = (s || '').trim().toLowerCase();
    // remove spaces + arabic
    s = s.replace(/\s+/g, '');
    s = s.replace(/[\u0600-\u06FF]/g, '');
    // keep a-z 0-9 . _ -
    s = s.replace(/[^a-z0-9._-]/g, '');
    return s;
  }

  function suggestUsernameFromEmail(){
    if (!emailEl || !userEl) return;
    const email = (emailEl.value || '').trim();
    if (!email) return;
    // only auto-fill if username empty
    if ((userEl.value || '').trim() !== '') return;
    const local = email.split('@')[0] || '';
    userEl.value = sanitizeUsername(local);
  }

  // Password generate
  function genPassword(len=10){
    const a = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    const b = "abcdefghijkmnopqrstuvwxyz";
    const c = "23456789";
    const d = "!@#$%^&*";
    const all = a + b + c + d;

    function pick(str){ return str[Math.floor(Math.random()*str.length)]; }

    let out = "";
    out += pick(a); out += pick(b); out += pick(c); out += pick(d);
    for(let i=out.length; i<len; i++) out += pick(all);

    // shuffle
    out = out.split('').sort(()=>Math.random()-0.5).join('');
    return out;
  }

  function setStrength(){
    if (!strengthBar || !strengthLabel || !passEl) return;
    const p = passEl.value || '';
    let score = 0;

    if (p.length >= 6) score += 1;
    if (p.length >= 10) score += 1;
    if (/[A-Z]/.test(p)) score += 1;
    if (/[a-z]/.test(p)) score += 1;
    if (/[0-9]/.test(p)) score += 1;
    if (/[^A-Za-z0-9]/.test(p)) score += 1;

    // 0..6 => percent
    const pct = Math.min(100, Math.round((score/6)*100));
    strengthBar.style.width = pct + '%';

    let label = 'ضعيفة';
    if (pct >= 34) label = 'متوسطة';
    if (pct >= 67) label = 'قوية';

    strengthLabel.textContent = label;
    // change bar color softly without specifying brand palette
    strengthBar.style.background = (pct >= 67) ? '#16a34a' : (pct >= 34 ? '#f59e0b' : '#dc2626');
  }

  // Toggle show/hide password
  function togglePassword(){
    if (!passEl) return;
    const isPass = passEl.type === 'password';
    passEl.type = isPass ? 'text' : 'password';
    btnToggle.textContent = isPass ? ' إخفاء' : ' عرض';
  }

  // Copy password
  async function copyPassword(){
    if (!passEl) return;
    const val = passEl.value || '';
    if (!val) return;

    try{
      await navigator.clipboard.writeText(val);
      btnCopy.textContent = '✅ تم';
      setTimeout(()=> btnCopy.textContent = '📋 نسخ', 900);
    }catch(e){
      // fallback
      passEl.select();
      document.execCommand('copy');
      btnCopy.textContent = '✅ تم';
      setTimeout(()=> btnCopy.textContent = '📋 نسخ', 900);
    }
  }

  // Roles select
  function setRole(role){
    roleInput.value = role;
    roleCards.forEach(c => c.classList.toggle('active', c.dataset.role === role));
  }

  roleCards.forEach(card=>{
    card.addEventListener('click', ()=> setRole(card.dataset.role));
    card.addEventListener('keydown', (e)=>{
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setRole(card.dataset.role);
      }
    });
  });

  // Events
  if (emailEl){
    emailEl.addEventListener('input', ()=> {
      // keep LTR & auto-suggest username if empty
      suggestUsernameFromEmail();
    });
    emailEl.addEventListener('blur', ()=> trimAll());
  }

  if (userEl){
    userEl.addEventListener('input', ()=> {
      userEl.value = sanitizeUsername(userEl.value);
    });
    userEl.addEventListener('blur', ()=> trimAll());
  }

  if (passEl){
    passEl.addEventListener('input', setStrength);
    setStrength();
  }

  if (btnGen){
    btnGen.addEventListener('click', ()=> {
      passEl.value = genPassword(10);
      setStrength();
      passEl.focus();
    });
  }

  if (btnToggle) btnToggle.addEventListener('click', togglePassword);
  if (btnCopy) btnCopy.addEventListener('click', copyPassword);

  // Prevent double submit + loading text
  if (formEl){
    formEl.addEventListener('submit', ()=>{
      trimAll();
      const btn = document.getElementById('btnSubmit');
      if (btn){
        btn.disabled = true;
        btn.textContent = '⏳ جاري الحفظ...';
      }
    });
  }

})();
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
