<?php require_once '../app/views/layouts/header.php'; ?>
<div class="card card-body">
    <h3>Open Maintenance Ticket</h3>
    <form action="?page=tickets/create" method="post">
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Related Asset (Optional)</label>
                <select name="asset_id" class="form-select">
                    <option value="">-- None --</option>
                    <?php foreach($data['assets'] as $asset): ?>
                        <option value="<?php echo $asset['id']; ?>"><?php echo $asset['asset_tag'] . ' - ' . $asset['brand']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label>Or Location (Lab)</label>
                <select name="location_id" class="form-select">
                    <option value="">-- None --</option>
                    <?php foreach($data['labs'] as $lab): ?>
                        <option value="<?php echo $lab['id']; ?>"><?php echo $lab['name_en']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label>Priority</label>
            <select name="priority" class="form-select">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Ticket</button>
    </form>
</div>
<?php require_once '../app/views/layouts/footer.php'; ?>