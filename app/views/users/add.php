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
    --bg: #eef0f3;
    --surface: #f6f7f9;
    --surface2:#f2f4f7;

    --text: #0b1220;
    --muted:#6b7280;

    --stroke: rgba(17,24,39,.10);

    --shadowOut:
      14px 14px 28px rgba(0,0,0,.10),
      -14px -14px 28px rgba(255,255,255,.92);

    --shadowIn:
      inset 10px 10px 18px rgba(0,0,0,.10),
      inset -10px -10px 18px rgba(255,255,255,.92);

    --dark: #0b0f17;
    --danger:#dc2626;

    --radius: 22px;
  }

  body{ background: var(--bg) !important; color: var(--text) !important; }

  .page-wrap{ max-width: 980px; margin: 0 auto; padding: 18px 14px 50px; }

  .page-head{
    display:flex; align-items:flex-start; justify-content:space-between; gap: 12px;
    margin-bottom: 16px;
  }

  .page-title{ font-weight: 950; margin: 0; letter-spacing:.2px; }
  .page-sub{
    margin-top: 6px; color: var(--muted) !important;
    font-size: .95rem; font-weight: 750;
  }

  .req{ color: var(--danger); font-weight: 950; }

  .ltr{
    direction:ltr;
    unicode-bidi: plaintext;
    text-align:left;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
  }

  .neo-surface{
    background: linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,.18)), var(--surface);
    border-radius: var(--radius);
    border: 1px solid var(--stroke);
    box-shadow: var(--shadowOut);
  }
  .neo-body{ padding: 20px 20px 18px; }

  .neo-inset{
    background: linear-gradient(180deg, rgba(255,255,255,.78), rgba(255,255,255,.25)), var(--surface2);
    border-radius: 18px;
    border: 1px solid var(--stroke);
    box-shadow: var(--shadowIn);
  }

  .neo-field{
    min-height: 54px;
    padding: 12px 14px;
    display:flex;
    align-items:center;
    gap: 10px;
  }

  .form-label{ color: var(--text) !important; font-weight: 950; margin-bottom: .45rem; }

  .form-control{
    border: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    height: auto !important;
    color: var(--text) !important;
    font-weight: 850;
    letter-spacing: .1px;
    width: 100%;
  }
  .form-control::placeholder{ color: rgba(11,18,32,.45) !important; font-weight: 750; }

  .help{ color: var(--muted); font-size: .9rem; font-weight: 750; margin-top: 8px; }

  .is-invalid{
    border-color: rgba(220,38,38,.35) !important;
    box-shadow: var(--shadowIn), 0 0 0 4px rgba(220,38,38,.14) !important;
  }
  .invalid-feedback{ display:block; font-weight:800; }

  .btn{
    border-radius: 999px;
    border: 0;
    font-weight: 950;
    letter-spacing: .2px;
    padding: 10px 16px;
  }

  .btn-neo{
    background: var(--surface);
    color: var(--text);
    box-shadow:
      10px 10px 18px rgba(0,0,0,.10),
      -10px -10px 18px rgba(255,255,255,.92);
    border: 1px solid var(--stroke);
  }
  .btn-neo:active{ box-shadow: var(--shadowIn); }

  .btn-primary{
    background: var(--dark);
    color:#fff;
    box-shadow:
      12px 12px 26px rgba(0,0,0,.18),
      -12px -12px 26px rgba(255,255,255,.70);
    border: 1px solid rgba(255,255,255,.08);
  }
  .btn-primary:hover{ filter: brightness(.98); transform: translateY(-1px); }

  /* Password pill buttons */
  .field-btn{
    height: 40px;
    padding: 0 14px;
    border-radius: 999px;
    background: var(--surface);
    border: 1px solid var(--stroke);
    box-shadow:
      8px 8px 16px rgba(0,0,0,.10),
      -8px -8px 16px rgba(255,255,255,.92);
    font-weight: 950;
    color: var(--text);
    display:inline-flex;
    align-items:center;
    gap: 8px;
    white-space: nowrap;
    cursor:pointer;
    user-select:none;
  }
  .field-btn:active{ box-shadow: var(--shadowIn); }
  .field-btn i{ opacity: .9; }

  .soft-hr{
    border: 0;
    height: 1px;
    background: linear-gradient(to left, rgba(0,0,0,0), rgba(17,24,39,.16), rgba(0,0,0,0));
    margin: 18px 0;
  }

  .grid-2{ display:grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media (max-width: 900px){ .grid-2{ grid-template-columns: 1fr; } }

  /* Segmented pills (مثل الصور) */
  .seg{
    display:flex;
    gap: 10px;
    padding: 10px;
    border-radius: 999px;
    background: var(--surface);
    border: 1px solid var(--stroke);
    box-shadow: var(--shadowIn);
    flex-wrap: wrap;
  }

  .seg-btn{
    height: 42px;
    padding: 0 16px;
    border-radius: 999px;
    background: transparent;
    border: 1px solid transparent;
    color: var(--muted);
    font-weight: 950;
    display:inline-flex;
    align-items:center;
    gap: 10px;
    cursor:pointer;
    user-select:none;
    transition: transform .08s ease, filter .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease;
  }
  .seg-btn:hover{ filter: brightness(.98); }
  .seg-btn:active{ transform: translateY(1px); }

  .seg-btn.active{
    background: var(--dark);
    color: #fff;
    box-shadow: 0 12px 28px rgba(0,0,0,.22);
    border-color: rgba(255,255,255,.08);
  }

  .seg-chip{
    font-size: .78rem;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.70);
    border: 1px solid var(--stroke);
    font-weight: 950;
    color: var(--text);
  }
  .seg-btn.active .seg-chip{
    background: rgba(255,255,255,.14);
    border-color: rgba(255,255,255,.14);
    color: #fff;
  }

  /* Password meter (neutral) */
  .pw-meter{
    height: 9px;
    border-radius: 999px;
    background: rgba(11,18,32,.10);
    overflow: hidden;
    margin-top: 10px;
  }
  .pw-meter > div{
    height: 100%;
    width: 0%;
    transition: width .2s;
    background: rgba(11,15,23,.82);
  }
  .pw-row{
    display:flex; align-items:center; justify-content:space-between; gap: 10px; margin-top: 8px;
  }
  .pw-note{ color: var(--muted); font-weight: 850; font-size: .9rem; }
  .pw-badge{
    font-weight: 950; font-size: .9rem; padding: 4px 12px; border-radius: 999px;
    background: rgba(255,255,255,.65); border:1px solid var(--stroke); color: var(--text);
  }

  .actions{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 12px;
    margin-top: 14px;
    flex-wrap: wrap;
  }
  .actions .left{ display:flex; gap:10px; align-items:center; flex-wrap: wrap; }

  .note-bottom{ color: var(--muted); font-weight: 850; font-size: .92rem; }

  .role-help{
    margin-top: 10px;
    padding: 14px 14px;
    font-weight: 850;
    color: var(--muted);
    line-height: 1.45;
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
                placeholder="مثال: محمد"
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
                <i class="bi bi-shuffle"></i> توليد
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
                <i class="bi bi-eye"></i> عرض
              </button>

              <button type="button" class="field-btn" id="btnCopy">
                <i class="bi bi-clipboard"></i> نسخ
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

        <!-- Role (segmented pills like الصور) -->
        <div>
          <label class="form-label">نوع الحساب (الصلاحية) <span class="req">*</span></label>

          <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($role); ?>">

          <div class="seg" id="roleSeg" aria-label="Role selector">
            <button type="button"
                    class="seg-btn <?php echo ($role === 'User') ? 'active' : ''; ?>"
                    data-role="User">
              <span class="seg-chip">User</span>
              موظف
            </button>

            <button type="button"
                    class="seg-btn <?php echo ($role === 'Manager') ? 'active' : ''; ?>"
                    data-role="Manager">
              <span class="seg-chip">Manager</span>
              مدير
            </button>

            <button type="button"
                    class="seg-btn <?php echo ($role === 'SuperAdmin') ? 'active' : ''; ?>"
                    data-role="SuperAdmin">
              <span class="seg-chip">Super Admin</span>
              سوبر أدمن
            </button>
          </div>

          <?php if (!empty($role_err)): ?>
            <div class="invalid-feedback" style="margin-top:8px;"><?php echo $role_err; ?></div>
          <?php else: ?>
            <div class="neo-inset role-help" id="roleHelp"></div>
          <?php endif; ?>

          <div class="help" style="margin-top:10px;">
            سيتم التحقق من البريد واسم المستخدم قبل الحفظ.
          </div>
        </div>

        <div class="actions">
          <div class="note-bottom">تأكد من اختيار الصلاحية المناسبة قبل الحفظ.</div>

          <div class="left">
            <a class="btn btn-neo" href="<?php echo $backUrl; ?>">إلغاء</a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">حفظ البيانات</button>
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
  const roleBtns  = document.querySelectorAll('#roleSeg .seg-btn');
  const roleHelp  = document.getElementById('roleHelp');

  const roleText = {
    'User': 'استخدام النظام فقط حسب الصلاحيات.',
    'Manager': 'متابعة المستخدمين/المهام التابعة حسب إعداداتك.',
    'SuperAdmin': 'صلاحيات كاملة لإدارة المستخدمين.'
  };

  // Helpers
  function trimAll(){
    if (emailEl) emailEl.value = (emailEl.value || '').trim();
    if (userEl)  userEl.value  = (userEl.value  || '').trim();
  }

  function sanitizeUsername(s){
    s = (s || '').trim().toLowerCase();
    s = s.replace(/\s+/g, '');
    s = s.replace(/[\u0600-\u06FF]/g, '');
    s = s.replace(/[^a-z0-9._-]/g, '');
    return s;
  }

  function suggestUsernameFromEmail(){
    if (!emailEl || !userEl) return;
    const email = (emailEl.value || '').trim();
    if (!email) return;
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

    const pct = Math.min(100, Math.round((score/6)*100));
    strengthBar.style.width = pct + '%';

    let label = 'ضعيفة';
    if (pct >= 34) label = 'متوسطة';
    if (pct >= 67) label = 'قوية';
    strengthLabel.textContent = label;
  }

  function togglePassword(){
    if (!passEl) return;
    const isPass = passEl.type === 'password';
    passEl.type = isPass ? 'text' : 'password';

    const icon = btnToggle.querySelector('i');
    if (icon) icon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
    btnToggle.lastChild.textContent = isPass ? ' إخفاء' : ' عرض';
  }

  async function copyPassword(){
    if (!passEl) return;
    const val = passEl.value || '';
    if (!val) return;

    try{
      await navigator.clipboard.writeText(val);
      btnCopy.innerHTML = '<i class="bi bi-check2"></i> تم';
      setTimeout(()=> btnCopy.innerHTML = '<i class="bi bi-clipboard"></i> نسخ', 900);
    }catch(e){
      passEl.select();
      document.execCommand('copy');
      btnCopy.innerHTML = '<i class="bi bi-check2"></i> تم';
      setTimeout(()=> btnCopy.innerHTML = '<i class="bi bi-clipboard"></i> نسخ', 900);
    }
  }

  // Roles segmented
  function setRole(role){
    roleInput.value = role;
    roleBtns.forEach(b => b.classList.toggle('active', b.dataset.role === role));
    if (roleHelp) roleHelp.textContent = roleText[role] || '';
  }

  roleBtns.forEach(btn=>{
    btn.addEventListener('click', ()=> setRole(btn.dataset.role));
    btn.addEventListener('keydown', (e)=>{
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setRole(btn.dataset.role); }
    });
  });

  // Init role help
  setRole(roleInput.value || 'User');

  // Events
  if (emailEl){
    emailEl.addEventListener('input', suggestUsernameFromEmail);
    emailEl.addEventListener('blur', trimAll);
  }

  if (userEl){
    userEl.addEventListener('input', ()=> { userEl.value = sanitizeUsername(userEl.value); });
    userEl.addEventListener('blur', trimAll);
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
        btn.textContent = 'جاري الحفظ...';
      }
    });
  }
})();
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
