</main> <footer class="bg-dark text-white py-4 mt-auto border-top border-secondary">
        <div class="container">
            <div class="row align-items-center">
                
                <div class="col-md-6 text-center text-md-end mb-2 mb-md-0">
                    <p class="mb-0 fs-6">
                        &copy; <?php echo date('Y'); ?> جميع الحقوق محفوظة - 
                        <span class="text-info fw-bold"><?php echo defined('SITENAME') ? SITENAME : 'نظام إدارة العهد'; ?></span>
                    </p>
                </div>
                
                <div class="col-md-6 text-center text-md-start">
                    <small class="text-white-50">
                        Version <?php echo defined('APPVERSION') ? APPVERSION : '1.0.0'; ?>
                    </small>
                </div>

            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                let alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 4000);
        });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchBox = document.getElementById('searchBox');
        const searchResults = document.getElementById('searchResults');

        if(searchBox){
            searchBox.addEventListener('keyup', function(){
                let query = this.value;
                if(query.length > 2){
                    let formData = new FormData();
                    formData.append('query', query);
                    fetch('<?php echo URLROOT; ?>/ajax.php', { method: 'POST', body: formData })
                    .then(response => response.text())
                    .then(data => {
                        if(searchResults) {
                            searchResults.innerHTML = data;
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(error => console.error('Error:', error));
                } else {
                    if(searchResults) {
                        searchResults.innerHTML = '';
                        searchResults.style.display = 'none';
                    }
                }
            });
            document.addEventListener('click', function(e){
                if (searchResults && e.target !== searchBox && e.target !== searchResults) {
                    searchResults.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>
</html>