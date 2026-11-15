            </main>
        </div>
    </div>
    
    <!-- Modals, custom find bar, and ALL your scripts go here -->
    <script>
        function markOrderAsViewed(orderId) { ... }
        // ... rest of your JS ...
            
        // Close all when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                const prof = document.getElementById('profileDropdown');
                if (prof) prof.classList.remove('show');
            }
            const c = document.getElementById('notifContainer');
            if (c && !c.contains(event.target)) {
                const d = document.getElementById('notifDropdown');
                if (d) d.classList.remove('show');
            }
            if (!event.target.closest('.menu-wrap')) {
                document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            }
        });
    </script>
<!-- SCROLL MEMORY -->
<script>
    if (sessionStorage.getItem('scrollPos')) { 
        document.documentElement.style.visibility = 'hidden'; 
    }
    window.addEventListener('load', function() {
        const savedPosition = sessionStorage.getItem('scrollPos');
        if (savedPosition) {
            window.scrollTo(0, parseInt(savedPosition));
            document.documentElement.style.visibility = 'visible';
            sessionStorage.removeItem('scrollPos');
        }
    });
    document.addEventListener('mousedown', function(event) {
        if (event.target.closest('th a') || 
            event.target.closest('.pagination a') || 
            event.target.closest('.filter-select') ||
            event.target.closest('.week-controls a') ||
            event.target.closest('button[type="submit"]')) {
            sessionStorage.setItem('scrollPos', window.scrollY);
        }
    });
        
        // Close all when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.contains(event.target)) {
                const prof = document.getElementById('profileDropdown');
                if (prof) prof.classList.remove('show');
            }
            const c = document.getElementById('notifContainer');
            if (c && !c.contains(event.target)) {
                const d = document.getElementById('notifDropdown');
                if (d) d.classList.remove('show');
            }
            if (!event.target.closest('.menu-wrap')) {
                document.querySelectorAll('.pop').forEach(p => p.classList.remove('show'));
            }
        });
    </script>
</body>
</html>