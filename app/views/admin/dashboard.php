<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Assets</h5>
                <p class="card-text display-4"><?php echo $data['total_assets']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5 class="card-title">Open Tickets</h5>
                <p class="card-text display-4"><?php echo $data['open_tickets']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Users</h5>
                <p class="card-text display-4"><?php echo $data['total_users']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                System Status
            </div>
            <div class="card-body">
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <?php if($_SESSION['user_role'] == 'admin'): ?>
        <div class="card mb-3">
            <div class="card-header bg-dark text-white">Post Announcement</div>
            <div class="card-body">
                <form action="?page=dashboard/announce" method="post">
                    <input type="text" name="title" class="form-control mb-2" placeholder="Title" required>
                    <textarea name="body" class="form-control mb-2" placeholder="Message" required></textarea>
                    <button class="btn btn-sm btn-dark w-100">Post</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="list-group">
            <?php foreach($data['announcements'] as $ann): ?>
                <a href="#" class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1"><?php echo $ann['title']; ?></h5>
                        <small class="text-muted"><?php echo substr($ann['created_at'], 0, 10); ?></small>
                    </div>
                    <p class="mb-1"><?php echo $ann['body']; ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Assets', 'Users', 'Open Tickets'],
            datasets: [{
                label: '# of Items',
                data: [<?php echo $data['total_assets']; ?>, <?php echo $data['total_users']; ?>, <?php echo $data['open_tickets']; ?>],
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>