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
    <table class="table table-hover table-striped bg-white align-middle">
        <thead class="table-dark">
            <tr>
                <th style="white-space:nowrap;">رقم التذكرة</th>
                <th>الموضوع</th>
                <th style="white-space:nowrap;">صاحب الطلب</th>
                <th style="white-space:nowrap;">المطلوبة لـ</th>
                <th style="white-space:nowrap;">المسؤول</th>
                <th style="white-space:nowrap;">الأصل</th>
                <th style="white-space:nowrap;">القسم</th>
                <th style="white-space:nowrap;">الحالة</th>
                <th style="white-space:nowrap;">الأولوية</th>
                <th style="white-space:nowrap;">آخر تحديث</th>
                <th style="white-space:nowrap;">إجراءات</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($data['tickets'])): ?>
                <tr>
                    <td colspan="11" class="text-center">لا توجد تذاكر حالياً.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['tickets'] as $ticket) : ?>
                    <tr>
                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->ticket_no ?? ('#' . $ticket->id)); ?>
                        </td>

                        <td style="min-width:260px;">
                            <div class="fw-bold">
                                <?php echo htmlspecialchars($ticket->subject ?? ''); ?>
                            </div>
                            <?php if (!empty($ticket->description)): ?>
                                <div class="text-muted small">
                                    <?php
                                        $desc = (string)$ticket->description;
                                        $desc = mb_substr($desc, 0, 80);
                                        echo htmlspecialchars($desc) . (mb_strlen((string)$ticket->description) > 80 ? '…' : '');
                                    ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->user_name ?? '-'); ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->requested_for_name ?? '-'); ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->assigned_to_name ?? 'غير مسند'); ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->asset_tag ?? '-'); ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->team ?? '-'); ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php if (($ticket->status ?? '') === 'Open'): ?>
                                <span class="badge bg-primary">مفتوحة</span>
                            <?php elseif (($ticket->status ?? '') === 'Closed'): ?>
                                <span class="badge bg-secondary">مغلقة</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($ticket->status ?? ''); ?></span>
                            <?php endif; ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php if (($ticket->priority ?? '') === 'High'): ?>
                                <span class="badge bg-danger">عالية</span>
                            <?php elseif (($ticket->priority ?? '') === 'Medium'): ?>
                                <span class="badge bg-warning text-dark">متوسطة</span>
                            <?php else: ?>
                                <span class="badge bg-success">عادية</span>
                            <?php endif; ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <?php echo htmlspecialchars($ticket->updated_at ?? ($ticket->created_at ?? '')); ?>
                        </td>

                        <td style="white-space:nowrap;">
                            <a href="<?php echo URLROOT; ?>/index.php?page=tickets/add" class="btn btn-primary">
                              <i class="fa fa-plus"></i> فتح تذكرة جديدة
                            </a>

                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
