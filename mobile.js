/**
 * MINDCARE - MOBILE MENU FUNCTIONALITY
 * Handles hamburger menu toggle for mobile devices
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    
    // ================================================================
    // CREATE MOBILE MENU ELEMENTS
    // ================================================================
    function createMobileMenuElements() {
      // Check if elements already exist
      if (document.querySelector('.mobile-menu-toggle')) {
        return;
      }

      // Create hamburger button
      const menuToggle = document.createElement('button');
      menuToggle.className = 'mobile-menu-toggle';
      menuToggle.setAttribute('aria-label', 'Toggle menu');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      `;

      // Create overlay
      const overlay = document.createElement('div');
      overlay.className = 'mobile-overlay';
      overlay.setAttribute('aria-hidden', 'true');

      // Add to body
      document.body.appendChild(menuToggle);
      document.body.appendChild(overlay);

      return { menuToggle, overlay };
    }

    // ================================================================
    // TOGGLE MENU FUNCTION
    // ================================================================
    function toggleMenu() {
      const sidebar = document.querySelector('.sidebar') || document.getElementById('sidebar');
      const overlay = document.querySelector('.mobile-overlay');
      const menuToggle = document.querySelector('.mobile-menu-toggle');

      if (!sidebar) return;

      const isOpen = sidebar.classList.contains('show');

      if (isOpen) {
        // Close menu
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        `;
        document.body.style.overflow = '';
      } else {
        // Open menu
        sidebar.classList.add('show');
        overlay.classList.add('show');
        menuToggle.setAttribute('aria-expanded', 'true');
        menuToggle.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        `;
        // Prevent body scroll when menu is open on mobile
        if (window.innerWidth <= 768) {
          document.body.style.overflow = 'hidden';
        }
      }
    }

    // ================================================================
    // CLOSE MENU ON LINK CLICK
    // ================================================================
    function closeMenuOnLinkClick() {
      const sidebar = document.querySelector('.sidebar') || document.getElementById('sidebar');
      if (!sidebar) return;

      const navLinks = sidebar.querySelectorAll('.nav-link, a[href]');
      
      navLinks.forEach(link => {
        link.addEventListener('click', function() {
          // Only close on mobile
          if (window.innerWidth <= 768) {
            toggleMenu();
          }
        });
      });
    }

    // ================================================================
    // CLOSE MENU ON ESC KEY
    // ================================================================
    function handleEscKey(event) {
      if (event.key === 'Escape') {
        const sidebar = document.querySelector('.sidebar') || document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('show')) {
          toggleMenu();
        }
      }
    }

    // ================================================================
    // HANDLE WINDOW RESIZE
    // ================================================================
    function handleResize() {
      const sidebar = document.querySelector('.sidebar') || document.getElementById('sidebar');
      const overlay = document.querySelector('.mobile-overlay');
      const menuToggle = document.querySelector('.mobile-menu-toggle');

      if (window.innerWidth > 768) {
        // Desktop view
        if (sidebar) {
          sidebar.classList.remove('show');
        }
        if (overlay) {
          overlay.classList.remove('show');
        }
        if (menuToggle) {
          menuToggle.setAttribute('aria-expanded', 'false');
        }
        document.body.style.overflow = '';
      }
    }

    // ================================================================
    // INITIALIZE
    // ================================================================
    function init() {
      // Create mobile menu elements
      const elements = createMobileMenuElements();
      
      if (!elements) return;

      const { menuToggle, overlay } = elements;

      // Add event listeners
      menuToggle.addEventListener('click', toggleMenu);
      overlay.addEventListener('click', toggleMenu);
      document.addEventListener('keydown', handleEscKey);

      // Handle link clicks
      closeMenuOnLinkClick();

      // Handle window resize with debounce
      let resizeTimer;
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(handleResize, 250);
      });

      // Initial check
      handleResize();
    }

    // ================================================================
    // START
    // ================================================================
    init();

  });

  // ================================================================
  // PREVENT ZOOM ON DOUBLE TAP (iOS Safari)
  // ================================================================
  let lastTouchEnd = 0;
  document.addEventListener('touchend', function(event) {
    const now = Date.now();
    if (now - lastTouchEnd <= 300) {
      event.preventDefault();
    }
    lastTouchEnd = now;
  }, false);

  // ================================================================
  // IMPROVE SCROLL PERFORMANCE
  // ================================================================
  let scrollTimer = null;
  window.addEventListener('scroll', function() {
    if (scrollTimer !== null) {
      clearTimeout(scrollTimer);
    }
    scrollTimer = setTimeout(function() {
      // Add any scroll-related functionality here
    }, 150);
  }, { passive: true });

})();