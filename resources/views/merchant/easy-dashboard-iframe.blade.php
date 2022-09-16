@if(env('APP_ENV') === 'production')
<script type="module">
    import {Workbox} from 'https://storage.googleapis.com/workbox-cdn/releases/6.4.1/workbox-window.prod.mjs';
    if ('serviceWorker' in navigator) {
        const wb = new Workbox('/sw-merchant.js');
        wb.register();
    }
</script>
@endif
