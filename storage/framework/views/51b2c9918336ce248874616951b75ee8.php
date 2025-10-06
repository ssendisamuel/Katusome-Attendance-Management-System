<?php
  use Illuminate\Support\Facades\Vite;

  // Get primary color - first from cookie, then from config
  $primaryColor = isset($_COOKIE['front-primaryColor']) ? $_COOKIE['front-primaryColor'] : $configData['color'] ?? null;
?>
<!-- laravel style -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/js/helpers.js']); ?>
<!-- beautify ignore:start -->
<?php if($configData['hasCustomizer']): ?>
<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/js/template-customizer.js']); ?>
<?php endif; ?>

  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/front-config.js']); ?>

<?php if($configData['hasCustomizer']): ?>
<script type="module">
  document.addEventListener('DOMContentLoaded', function() {
    // Initialize template customizer after DOM is loaded
    if (window.TemplateCustomizer) {
      try {
        window.templateCustomizer = new TemplateCustomizer({
          defaultTextDir: "<?php echo e($configData['textDirection']); ?>",
          <?php if($primaryColor): ?>
            defaultPrimaryColor: "<?php echo e($primaryColor); ?>",
          <?php endif; ?>
          defaultTheme: "<?php echo e($configData['themeOpt']); ?>",
          defaultShowDropdownOnHover: "<?php echo e($configData['showDropdownOnHover']); ?>",
          displayCustomizer: "<?php echo e($configData['displayCustomizer']); ?>",
          lang: '<?php echo e(app()->getLocale()); ?>',
          'controls': <?php echo json_encode(['color', 'theme', 'rtl']); ?>,
        });

        // Ensure color is applied on page load
        <?php if($primaryColor): ?>
          if (window.Helpers && typeof window.Helpers.setColor === 'function') {
            window.Helpers.setColor("<?php echo e($primaryColor); ?>", true);
          }
        <?php endif; ?>
      } catch (error) {
        console.warn('Template customizer initialization error:', error);
      }
    }
  });
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\Attendance Project\Ssendi_Attendance\resources\views\layouts\sections\scriptsIncludesFront.blade.php ENDPATH**/ ?>