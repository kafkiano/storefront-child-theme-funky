/**
 * Off-canvas menu functionality for Storefront Child Theme
 * Prevents body scroll when menu is open and closes menu when clicking outside
 */
document.addEventListener('DOMContentLoaded', function() {
    const nav = document.querySelector('#site-navigation');
    const menuToggle = document.querySelector('#site-navigation-menu-toggle');
    const body = document.body;
    
    if (!nav) {
        console.warn('Off-canvas menu: #site-navigation not found');
        return;
    }
    
    // Create overlay element
    let overlay = document.querySelector('.off-canvas-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'off-canvas-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('role', 'presentation');
        // Insert after the navigation container
        nav.parentNode.insertBefore(overlay, nav.nextSibling);
    }
    
    // Function to update overlay visibility and body class
    function updateMenuState() {
        const isToggled = nav.classList.contains('toggled');
        if (isToggled) {
            overlay.style.display = 'block';
            body.classList.add('menu-open');
            overlay.setAttribute('aria-hidden', 'false');
        } else {
            overlay.style.display = 'none';
            body.classList.remove('menu-open');
            overlay.setAttribute('aria-hidden', 'true');
        }
    }
    
    // Initial sync
    updateMenuState();
    
    // Observe class changes on nav to keep overlay in sync
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                updateMenuState();
            }
        });
    });
    observer.observe(nav, { attributes: true });
    
    // Close menu when clicking overlay
    overlay.addEventListener('click', function() {
        nav.classList.remove('toggled');
        // aria-expanded will be updated by storefront's navigation.js
        updateMenuState();
    });
    
    // Close menu when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && nav.classList.contains('toggled')) {
            nav.classList.remove('toggled');
            updateMenuState();
        }
    });
    
    // Ensure body scroll lock when menu is toggled via storefront's button
    // (storefront's navigation.js already toggles the class, our observer will handle it)
});