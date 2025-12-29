<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Spare Parts & Inventory</h1>
    <a href="?page=parts/add" class="btn btn-primary"><i class="fa fa-plus"></i> Add New Part</a>
</div>

<?php flash('part_msg'); ?>

<div class="card">
    <div class="card-body">
        <table class="table table-bordered text-center">
            <thead class="table-light">
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>In Stock</th>
                    <th>Min Level</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['parts'] as $part): ?>
                    <?php $isLow = $part['quantity'] <= $part['min_stock']; ?>
                    
                    <tr class="<?php echo $isLow ? 'table-danger' : ''; ?>">
                        <td class="text-start"><?php echo $part['name']; ?></td>
                        <td><?php echo $part['category']; ?></td>
                        <td class="fw-bold fs-5"><?php echo $part['quantity']; ?></td>
                        <td><?php echo $part['min_stock']; ?></td>
                        <td>
                            <?php if($isLow): ?>
                                <span class="badge bg-danger">Low Stock!</span>
                            <?php else: ?>
                                <span class="badge bg-success">Good</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="?page=parts/update_stock" method="post" class="d-flex gap-1 justify-content-center">
                                <input type="hidden" name="id" value="<?php echo $part['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $part['quantity']; ?>" class="form-control form-control-sm" style="width: 70px;">
                                <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>