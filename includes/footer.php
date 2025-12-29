</div> <div class="my-5"></div>

    <footer class="footer mt-auto py-3 bg-white border-top shadow-sm">
        <div class="container text-center">
            <span class="text-muted">
                جميع الحقوق محفوظة &copy; <?= date('Y') ?> نظام إدارة العهد (IT Inventory).
            </span>
            <div class="small text-muted mt-1">
                تم التطوير بواسطة <span class="fw-bold text-primary"><?= $_SESSION['user_name'] ?? 'IT Team' ?></span>
            </div>
        </div>
    </footer>

    <style>
        /* عندما يكون الوضع الليلي مفعلاً، غير خلفية الفوتر */
        body.dark-mode .footer {
            background-color: #16213e !important; /* نفس لون الناف بار */
            border-color: #30475e !important;
            color: #aab8c5;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </body>
</html>