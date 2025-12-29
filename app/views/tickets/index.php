<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <h1><i class="fa fa-headset"></i> التذاكر والدعم الفني</h1>
    </div>
    <div class="col-md-6 text-start">
        <a href="<?php echo URLROOT; ?>/index.php?page=Tickets/add" class="btn btn-primary">
            <i class="fa fa-plus"></i> فتح تذكرة جديدة
        </a>
    </div>
</div>

<div class="card card-body bg-light mt-2 shadow-sm">
    <?php flash('ticket_msg'); ?>
    
    <div class="table-responsive">
        <table class="table table-hover table-striped bg-white">
            <thead class="table-dark">
                <tr>
                    <th>رقم التذكرة</th>
                    <th>الموضوع</th>
                    <th>الأولوية</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($data['tickets'])): ?>
                    <tr>
                        <td colspan="6" class="text-center">لا توجد تذاكر حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($data['tickets'] as $ticket) : ?>
                        <tr>
                            <td>#<?php echo $ticket->id; ?></td>
                            
                            <td><?php echo $ticket->description; ?></td>
                            
                            <td>
                                <?php if($ticket->priority == 'High'): ?>
                                    <span class="badge bg-danger">عالية</span>
                                <?php elseif($ticket->priority == 'Medium'): ?>
                                    <span class="badge bg-warning text-dark">متوسطة</span>
                                <?php else: ?>
                                    <span class="badge bg-success">عادية</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if($ticket->status == 'Open'): ?>
                                    <span class="badge bg-primary">مفتوحة</span>
                                <?php elseif($ticket->status == 'Closed'): ?>
                                    <span class="badge bg-secondary">مغلقة</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><?php echo $ticket->status; ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <td><?php echo $ticket->created_at; ?></td>
                            
                            <td>
                                <a href="<?php echo URLROOT; ?>/index.php?page=Tickets/show&id=<?php echo $ticket->id; ?>" class="btn btn-info btn-sm">
                                    <i class="fa fa-eye"></i> تفاصيل
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>