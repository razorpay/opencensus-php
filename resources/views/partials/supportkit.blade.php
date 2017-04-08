<script>
window.smoochScript = $.getScript('https://cdn.smooch.io/smooch.min.js', function() {
  Smooch
    .init({appToken: '02o6kuyoscqkwiqr3ld3lbehw'})
    .then(function () {
        Smooch._rzpReady = true; // custom prop
    });
})
</script>