@if(\App\Services\TurnstileService::enabled())
<div class="cf-turnstile" data-sitekey="{{ \App\Services\TurnstileService::siteKey() }}" data-theme="light"></div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
