/**
 * MindSpace — Main JavaScript
 * =================================
 * Handles global UI interactions:
 *  - Mobile navbar toggle
 *  - Rotating motivational quote strip
 *  - Form submit loading states
 *  - Auto-dismiss alerts
 *  - Smooth scroll for anchor links
 *
 * No dependencies — pure vanilla JavaScript.
 */

/* ─────────────────────────────────────────────────────────────
   1. MOBILE NAVBAR TOGGLE
   ───────────────────────────────────────────────────────────── */
(function initNavbar() {
  const toggle  = document.getElementById('navToggle');
  const navMenu = document.getElementById('navMenu');

  if (!toggle || !navMenu) return;

  // Toggle open/closed
  toggle.addEventListener('click', () => {
    const isOpen = navMenu.classList.toggle('open');
    toggle.setAttribute('aria-expanded', isOpen);
  });

  // Close menu when a nav link is clicked (mobile UX)
  navMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      navMenu.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    });
  });

  // Close menu on outside click
  document.addEventListener('click', (e) => {
    if (!toggle.contains(e.target) && !navMenu.contains(e.target)) {
      navMenu.classList.remove('open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
})();


/* ─────────────────────────────────────────────────────────────
   2. ROTATING QUOTE STRIP (home page)
   Each quote rotates every 8 seconds with a fade transition.
   ───────────────────────────────────────────────────────────── */
(function initQuoteStrip() {
  const strip = document.getElementById('quoteStrip');
  if (!strip) return;

  const quotes = [
    "✨ \"You don't have to be positive all the time. It's okay to feel sad.\" – Lori Deschene",
    "🌱 \"Start where you are. Use what you have. Do what you can.\" – Arthur Ashe",
    "💙 \"Mental health is not a luxury — it is a necessity.\" – Unknown",
    "🌻 \"Your feelings are valid. Your struggles are real. You are enough.\" – Unknown",
    "🔑 \"Asking for help is a sign of strength, not weakness.\" – Unknown",
    "🌿 \"Take a deep breath. You have survived 100% of your hardest days.\" – Unknown",
    "☀️ \"Even the darkest night will end, and the sun will rise.\" – Victor Hugo",
    "💪 \"You are braver than you believe, stronger than you seem.\" – A.A. Milne",
  ];

  let currentIndex = 0;

  // Add fade transition to the strip
  strip.style.transition = 'opacity 0.6s ease';

  function rotateQuote() {
    // Fade out
    strip.style.opacity = '0';

    setTimeout(() => {
      currentIndex = (currentIndex + 1) % quotes.length;
      strip.textContent = quotes[currentIndex];
      // Fade in
      strip.style.opacity = '1';
    }, 600);
  }

  setInterval(rotateQuote, 8000);
})();


/* ─────────────────────────────────────────────────────────────
   3. FORM SUBMIT LOADING STATE
   Disables submit button and shows a spinner text while the
   form is being submitted (prevents double-clicks).
   ───────────────────────────────────────────────────────────── */
(function initFormLoadingState() {
  document.querySelectorAll('form[action]').forEach(form => {
    form.addEventListener('submit', function () {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (!submitBtn) return;

      // Store original text so we can restore it if needed
      submitBtn.dataset.originalText = submitBtn.textContent;
      submitBtn.disabled    = true;
      submitBtn.textContent = 'Please wait…';

      // Safety net: re-enable after 10s in case of network error
      setTimeout(() => {
        submitBtn.disabled    = false;
        submitBtn.textContent = submitBtn.dataset.originalText || 'Submit';
      }, 10000);
    });
  });
})();


/* ─────────────────────────────────────────────────────────────
   4. AUTO-DISMISS ALERTS
   Any .alert element auto-hides after 6 seconds.
   ───────────────────────────────────────────────────────────── */
(function initAutoDismissAlerts() {
  // Run after a short delay so the user has time to read it
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
      alert.style.transition = 'opacity 0.6s ease, max-height 0.6s ease';
      alert.style.opacity    = '0';
      alert.style.maxHeight  = '0';
      alert.style.overflow   = 'hidden';
      alert.style.padding    = '0';
      setTimeout(() => alert.remove(), 700);
    });
  }, 6000);
})();


/* ─────────────────────────────────────────────────────────────
   5. SMOOTH SCROLL FOR ANCHOR LINKS
   ───────────────────────────────────────────────────────────── */
(function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
})();


/* ─────────────────────────────────────────────────────────────
   6. PASSWORD STRENGTH INDICATOR (register page)
   Provides visual feedback as the user types their password.
   ───────────────────────────────────────────────────────────── */
(function initPasswordStrength() {
  const pwField = document.getElementById('password');
  if (!pwField) return;

  // Create indicator element below the password input
  const indicator = document.createElement('div');
  indicator.style.cssText = 'margin-top:0.3rem; font-size:0.8rem; font-weight:600;';
  pwField.parentNode.appendChild(indicator);

  pwField.addEventListener('input', () => {
    const val      = pwField.value;
    const strength = getPasswordStrength(val);

    const colors  = { weak: '#ef5350', fair: '#FFD54F', strong: '#4CAF50' };
    const labels  = { weak: '⚠️ Weak password', fair: '⚡ Fair — try adding symbols', strong: '✅ Strong password' };

    indicator.textContent = val.length === 0 ? '' : labels[strength];
    indicator.style.color = colors[strength] || '#636e72';
  });

  function getPasswordStrength(pw) {
    if (pw.length < 8) return 'weak';
    const hasUpper   = /[A-Z]/.test(pw);
    const hasLower   = /[a-z]/.test(pw);
    const hasNumber  = /\d/.test(pw);
    const hasSpecial = /[^A-Za-z0-9]/.test(pw);
    const score = [hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;
    if (score <= 2) return 'weak';
    if (score === 3) return 'fair';
    return 'strong';
  }
})();


/* ─────────────────────────────────────────────────────────────
   7. CONFIRM PASSWORD MATCH INDICATOR (register page)
   ───────────────────────────────────────────────────────────── */
(function initConfirmPasswordCheck() {
  const pwField      = document.getElementById('password');
  const confirmField = document.getElementById('confirm_password');
  if (!pwField || !confirmField) return;

  const matchMsg = document.createElement('div');
  matchMsg.style.cssText = 'margin-top:0.3rem; font-size:0.8rem; font-weight:600;';
  confirmField.parentNode.appendChild(matchMsg);

  function checkMatch() {
    if (!confirmField.value) {
      matchMsg.textContent = '';
      return;
    }
    if (pwField.value === confirmField.value) {
      matchMsg.textContent = '✅ Passwords match';
      matchMsg.style.color = '#4CAF50';
    } else {
      matchMsg.textContent = '❌ Passwords do not match';
      matchMsg.style.color = '#ef5350';
    }
  }

  confirmField.addEventListener('input', checkMatch);
  pwField.addEventListener('input', checkMatch);
})();


/* ─────────────────────────────────────────────────────────────
   8. CARD ENTRANCE ANIMATION
   Adds a subtle fade-in + slide-up effect to .card elements
   when they enter the viewport.
   ───────────────────────────────────────────────────────────── */
(function initCardAnimations() {
  const cards = document.querySelectorAll('.card, .post-card, .resource-card');
  if (!cards.length || !('IntersectionObserver' in window)) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        entry.target.style.opacity    = '1';
        entry.target.style.transform  = 'translateY(0)';
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  cards.forEach(card => {
    // Start hidden and shifted down
    card.style.opacity   = '0';
    card.style.transform = 'translateY(20px)';
    observer.observe(card);
  });
})();


/* ─────────────────────────────────────────────────────────────
   9. UTILITY: flash a temporary toast message
   Usage from other scripts: window.MindSpace.toast('Saved!', 'success')
   ───────────────────────────────────────────────────────────── */
window.MindSpace = {
  toast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed; bottom: 1.5rem; right: 1.5rem;
      z-index: 9999; min-width: 220px; max-width: 360px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      animation: slideInRight 0.3s ease;
    `;

    // Inject slide-in keyframe if not already present
    if (!document.getElementById('toastStyle')) {
      const style = document.createElement('style');
      style.id = 'toastStyle';
      style.textContent = `
        @keyframes slideInRight {
          from { opacity:0; transform:translateX(40px); }
          to   { opacity:1; transform:translateX(0); }
        }
      `;
      document.head.appendChild(style);
    }

    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.transition = 'opacity 0.4s ease';
      toast.style.opacity    = '0';
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  }
};
