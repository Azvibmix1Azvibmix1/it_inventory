    </div> <!-- /container-main -->
</main>

<footer class="mt-auto py-4 bg-light border-top">
    <div class="container text-center">
        <div class="row align-items-center">

            <div class="col-md-4 text-center text-md-end mb-3 mb-md-0">
                <span class="text-muted fw-bold">
                    <?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?>
                    &copy; <?php echo date('Y'); ?>
                </span>
                <br>
                <small class="text-muted">جميع الحقوق محفوظة</small>
            </div>

            <div class="col-md-4 text-center mb-3 mb-md-0">
                <img src="<?php echo URLROOT; ?>/img/uoj-footer.png" alt="University of Jeddah"
                     style="height:60px; max-width:100%;">
            </div>

            <div class="col-md-4 text-center text-md-start">
                <small class="text-muted">Version 1.0</small>
            </div>

        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo URLROOT; ?>/js/main.js"></script>

</body>
</html>
