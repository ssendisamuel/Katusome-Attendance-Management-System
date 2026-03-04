@php
    $containerFooter =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
            ? 'container-xxl'
            : 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
    <div class="{{ $containerFooter }}">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="mb-2 mb-md-0 d-flex align-items-center gap-3">
                <span>
                    &#169;
                    <script>
                        document.write(new Date().getFullYear());
                    </script>
                    <a href="https://mubs.ac.ug/" target="_blank" class="footer-link">Makerere University Business
                        School</a>. All Rights Reserved.
                </span>
            </div>
            <div>
                <a href="https://mubsep.mubs.ac.ug" target="_blank" class="footer-link me-4">MUBSEP</a>
                <a href="https://myportal.mubs.ac.ug" target="_blank" class="footer-link">Student Portal</a>
            </div>
        </div>
    </div>
</footer>
<!-- / Footer -->
