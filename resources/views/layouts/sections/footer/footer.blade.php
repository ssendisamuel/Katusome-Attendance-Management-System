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
          , Developed with ❤️ by <a href="{{ config('variables.creatorUrl') ?: url('/') }}" target="_blank" class="footer-link fw-medium">{{ config('variables.creatorName', 'Ssendi Samuel') }}</a>
        </span>
        @php
          $twitter = config('variables.twitterUrl');
          $youtube = config('variables.youtubeUrl');
          $linkedin = config('variables.linkedinUrl');
        @endphp
        @if(!empty($twitter))
          <a href="{{ $twitter }}" class="footer-link" target="_blank" aria-label="X"><i class="icon-base ri ri-twitter-x-fill"></i></a>
        @endif
        @if(!empty($youtube))
          <a href="{{ $youtube }}" class="footer-link" target="_blank" aria-label="YouTube"><i class="icon-base ri ri-youtube-fill"></i></a>
        @endif
        @if(!empty($linkedin))
          <a href="{{ $linkedin }}" class="footer-link" target="_blank" aria-label="LinkedIn"><i class="icon-base ri ri-linkedin-fill"></i></a>
        @endif
      </div>
    </div>
  </div>
</footer>
<!-- / Footer -->
