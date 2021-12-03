let friendBuyLoaded = false;
// eslint-disable-next-line no-multi-assign
window.friendbuyAPI = window.friendbuyAPI = window.friendbuyAPI || [];
function loadFriendBuy() {
  // registers your merchant using your merchant ID found in the
  // retailer app https://retailer.friendbuy.io/settings/general
  window.friendbuyAPI.merchantId = '05c3a537-257c-48ae-a0f6-319bff3ac55f';
  window.friendbuyAPI.push(['merchant', window.friendbuyAPI.merchantId]);

  // load the merchant SDK and your campaigns
  if (!friendBuyLoaded) {
    // eslint-disable-next-line func-names
    (function (f, r, n, d, b, u, y) {
      while ((u = n.shift())) {
        // eslint-disable-next-line
        (b = f.createElement(r)), (y = f.getElementsByTagName(r)[0]);
        b.async = 1;
        b.src = u;
        y.parentNode.insertBefore(b, y);
      }
    })(document, 'script', [
      'https://static.fbot.me/friendbuy.js',
      `https://campaign.fbot.me/${window.friendbuyAPI.merchantId}/campaigns.js`,
    ]);
    friendBuyLoaded = true;
  }
}

export default loadFriendBuy;
