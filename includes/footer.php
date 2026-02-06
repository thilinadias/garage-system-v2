    </div> <!-- End Main Content -->
    <footer class="text-center py-3 border-top mt-auto bg-white print-hide">
        <div class="container">
            <span class="text-muted small">All rights reserved &copy; <?php echo date('Y'); ?> | Garage System V3</span>
        </div>
    </footer>
</div> <!-- End Flex Container -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery (Optional for some plugins, but keeping it light if possible) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Custom JS -->
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Dark Mode Logic
    const toggleBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Check local storage
    if(localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('bg-dark', 'text-white');
        document.querySelectorAll('.card').forEach(c => c.classList.add('bg-secondary', 'text-white'));
    }

    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            body.classList.toggle('bg-dark');
            body.classList.toggle('text-white');
            
            // Simple approach: toggle classes on common elements or use a data-theme attribute with CSS variables for better support
            // For now, toggling body bg is basic visual cue. 
            // Better: reload or use CSS variables. Let's just toggle 'dark-mode' class and have specific CSS in header.
            
            if(body.classList.contains('bg-dark')) {
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.setItem('darkMode', 'disabled');
            }
            location.reload(); // Reload to re-render server-side based or just apply comprehensive CSS
        });
    }
</script>
</body>
</html>
