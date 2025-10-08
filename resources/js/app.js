import './bootstrap';
/*
  Add custom scripts here
*/
import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);

// Provide a global SweetAlert2 Toast mixin for front/admin pages
try {
  if (window.Swal && typeof window.Swal.mixin === 'function' && !window.Toast) {
    window.Toast = window.Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true
    });
  }
} catch (e) {
  // ignore
}

// Intercept native confirm() inline handlers on delete buttons/links
document.addEventListener(
  'click',
  function (e) {
    const el = e.target.closest('[onclick]');
    if (!el) return;
    const onclickAttr = el.getAttribute('onclick') || '';
    if (!onclickAttr.includes('confirm(')) return;

    // Prevent the native inline confirm from running
    e.preventDefault();
    e.stopPropagation();

    // Extract message inside confirm("..."), fallback to generic
    let message = 'Are you sure?';
    const match = onclickAttr.match(/confirm\((['"])(.*?)\1\)/);
    if (match && match[2]) message = match[2];

    const proceed = () => {
      const form = el.closest('form');
      if (form) {
        form.submit();
      } else if (el.tagName === 'A' && el.getAttribute('href')) {
        const href = el.getAttribute('href');
        if (href && href !== '#') window.location.href = href;
      }
    };

    if (window.Swal) {
      window.Swal.fire({
        title: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
        customClass: {
          confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
          cancelButton: 'btn btn-outline-secondary waves-effect'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.isConfirmed) proceed();
      });
    } else {
      if (confirm(message)) proceed();
    }
  },
  true
);

// Initialize report export script globally (ensures PDF/Print work cross-pages)
try {
  import('../assets/js/report-export.js');
} catch (e) {
  // ignore
}
