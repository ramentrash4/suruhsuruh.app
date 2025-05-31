</main> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const wrapper = document.getElementById('wrapper');
        const crudSubmenuEl = document.getElementById('crudSubmenu');
        var crudSubmenu = null; // Definisikan di luar agar bisa diakses

        if (crudSubmenuEl) {
            crudSubmenu = new bootstrap.Collapse(crudSubmenuEl, {
                toggle: false 
            });
        }

        if (menuToggle && wrapper) {
            menuToggle.addEventListener('click', () => {
                wrapper.classList.toggle('toggled');
                // Jika sidebar tertutup saat di mobile (karena wrapper tidak ditoggle)
                // dan submenu CRUD terbuka, maka tutup submenu CRUD
                if (!wrapper.classList.contains('toggled') && window.innerWidth < 992) {
                     if (crudSubmenu && crudSubmenuEl.classList.contains('show')) {
                        crudSubmenu.hide();
                    }
                }
            });
        }
        
        // Menutup submenu CRUD saat item lain di sidebar diklik (terutama untuk mobile)
        const sidebarLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item-action:not([data-bs-toggle="collapse"])');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992 && wrapper && wrapper.classList.contains('toggled')) { 
                    if (crudSubmenu && crudSubmenuEl.classList.contains('show')) {
                        crudSubmenu.hide();
                    }
                    // Opsi: Tutup sidebar setelah klik di mobile
                    // wrapper.classList.remove('toggled'); 
                }
            });
        });
    </script>
    <?php if (isset($additional_js)): foreach ($additional_js as $js_file): ?>
        <script src="<?= BASE_URL ?><?= $js_file ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>