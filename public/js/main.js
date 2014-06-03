$(document).ready(function()
{
    rzpd = {};

    rzpd.views = {
        toggleLivemode: function() {
            $('#livemode .button-wrap').toggleClass("button-active");
            $('.button-desc').toggleClass('active-desc');
        }
    };

    rzpd.hooks = {
        toggleLivemode: function() {
            rzpd.views.toggleLivemode();
        }
    };

    if (document.getElementById('livemode') !== null)
        $('#livemode .button-wrap').click(rzpd.hooks.toggleLivemode);
});