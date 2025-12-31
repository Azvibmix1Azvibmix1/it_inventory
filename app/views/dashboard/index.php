<?php require APPROOT . '/views/includes/header.php'; ?>

<div class="container fade-in py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <div>
            <h1 class="h3 text-dark fw-bold" style="font-family: 'Tajawal', sans-serif;">
                <i class="fas fa-chart-line text-primary ms-2"></i> لوحة المعلومات
            </h1>
            <p class="text-muted mb-0">نظرة عامة على حالة النظام والأصول</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> طباعة تقرير
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">إجمالي الأصول</div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $data['assets_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-primary-light text-primary">
                                <i class="fas fa-desktop fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">تذاكر الدعم المفتوحة</div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $data['tickets_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-danger-light text-danger">
                                <i class="fas fa-ticket-alt fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">تنبيهات المخزون</div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $data['low_stock']; ?></div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-warning-light text-warning">
                                <i class="fas fa-exclamation-triangle fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 py-2 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">الموظفين المسجلين</div>
                            <div class="h3 mb-0 fw-bold text-dark"><?php echo $data['users_count']; ?></div>
                        </div>
                        <div class="col-auto">
                            <div class="icon-circle bg-success-light text-success">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 bg-white border-bottom-0">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-chart-pie text-primary ms-1"></i> توزيع الأصول حسب النوع</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:250px; width:100%">
                        <canvas id="myPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 bg-white border-bottom-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="fas fa-history text-primary ms-1"></i> آخر التذاكر والطلبات</h6>
                    <a href="<?php echo URLROOT; ?>/index.php?page=tickets/index" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        عرض الكل <i class="fas fa-arrow-left ms-1"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="bg-light text-muted small">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>الموضوع</th>
                                <th>بواسطة</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($data['recent_tickets'])): ?>
                                <?php foreach($data['recent_tickets'] as $ticket): ?>
                                <tr>
                                    <td class="ps-4 text-muted small">#<?php echo $ticket->id; ?></td>
                                    <td class="fw-bold text-dark"><?php echo $ticket->subject; ?></td>
                                    <td class="small text-muted">
                                        <i class="fas fa-user-circle ms-1"></i> <?php echo isset($ticket->user_name) ? $ticket->user_name : 'غير محدد'; ?>
                                    </td>
                                    <td>
                                        <?php if($ticket->status == 'Open'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">مفتوحة</span>
                                        <?php elseif($ticket->status == 'In Progress'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">جاري العمل</span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">مغلقة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo date('Y/m/d', strtotime($ticket->created_at)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        لا توجد تذاكر حديثة للعرض
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
    body {
        background-color: #f8f9fc; /* خلفية رمادية فاتحة جداً للموقع */
    }
    
    .card {
        border-radius: 0.5rem; /* حواف ناعمة */
    }

    /* ألوان الخلفيات الفاتحة للأيقونات */
    .bg-primary-light { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
    .bg-danger-light { background-color: rgba(220, 53, 69, 0.1); }

    .icon-circle {
        height: 3rem;
        width: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* تحسين الجدول */
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* ألوان البادج الجديدة (Subtle) */
    .bg-danger-subtle { background-color: #f8d7da; }
    .bg-warning-subtle { background-color: #fff3cd; }
    .bg-success-subtle { background-color: #d1e7dd; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
var ctx = document.getElementById("myPieChart");
var myPieChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ["لابتوب", "مكتبي", "شاشة", "طابعة", "أخرى"],
    datasets: [{
      data: [
          <?php echo $data['asset_chart_data']['Laptop']; ?>, 
          <?php echo $data['asset_chart_data']['Desktop']; ?>, 
          <?php echo $data['asset_chart_data']['Monitor']; ?>, 
          <?php echo $data['asset_chart_data']['Printer']; ?>,
          <?php echo $data['asset_chart_data']['Other']; ?>
      ],
      // استخدام ألوان زرقاء متدرجة تتناسب مع شعار الجامعة
      backgroundColor: ['#0d6efd', '#0dcaf0', '#6610f2', '#6c757d', '#adb5bd'],
      hoverBackgroundColor: ['#0b5ed7', '#31d2f2', '#6f42c1', '#5c636a', '#495057'],
      hoverBorderColor: "rgba(234, 236, 244, 1)",
    }],
  },
  options: {
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                    family: "'Tajawal', sans-serif"
                }
            }
        }
    },
    cutout: '75%', // دائرة أنحف
  },
});
</script>

<?php require APPROOT . '/views/includes/footer.php'; ?>