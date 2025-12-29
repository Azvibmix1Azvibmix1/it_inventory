<?php require_once '../app/views/layouts/header.php'; ?>

<h2>Ongoing Inventory Session</h2>
<?php flash('inv_msg'); ?>

<form action="?page=inventory/conduct&id=<?php echo $data['session_id']; ?>" method="post">
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Found?</th>
                    <th>Asset Tag</th>
                    <th>Device</th>
                    <th>Serial</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['details'] as $item): ?>
                <tr>
                    <td class="text-center" style="width: 100px;">
                        <input type="checkbox" name="items[<?php echo $item['id']; ?>]" 
                               class="form-check-input" style="transform: scale(1.5);"
                               <?php echo ($item['is_found']) ? 'checked' : ''; ?>>
                    </td>
                    <td><?php echo $item['asset_tag']; ?></td>
                    <td><?php echo $item['brand'] . ' ' . $item['model']; ?></td>
                    <td><?php echo $item['serial_number']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-between mt-3">
        <button type="submit" name="save" class="btn btn-primary">Save Progress</button>
        <button type="submit" name="finish" class="btn btn-danger" onclick="return confirm('Finish session?');">Finalize & Close</button>
    </div>
</form>

<?php require_once '../app/views/layouts/footer.php'; ?>