<script>
  // redirect to react.js auth flows post angular auth eol
  const getRedirectPath = (loc = {}) => {
    try {
      if (loc.pathname === '/') {
        // old angular paths have /access/ in it
        if (loc.hash.includes('/access/')) {
          // oldAngularPathsRegexQ matches url created by /captcha page
          const oldAngularPathsRegexQ = /\?\#\/access\/(signin|signup|resetpassword|forgotpassword|emailupdate)\?/;
          const parserAnchor = document.createElement('a');
          if (loc.href.match(oldAngularPathsRegexQ)) {
            parserAnchor.href = loc.href.replace(oldAngularPathsRegexQ, '?');
            if (loc.hash.includes('/access/signup?')) {
              return '/signup' + parserAnchor.search;
            } else if (loc.hash.includes('/access/resetpassword?')) {
              return '/resetpassword' + parserAnchor.search;
            } else if (loc.hash.includes('/access/emailupdate?')) {
              return '/emailupdate' + parserAnchor.search;
            }

            return '/signin' + parserAnchor.search;
          }
          // if only captcha redirect didn't happen.
          const oldAngularPathsRegex = /\#\/access\/(signin|signup|resetpassword|forgotpassword|emailupdate)/;
          parserAnchor.href = loc.href.replace(oldAngularPathsRegex, '');;
          if (loc.hash.includes('/access/signup')) {
            return '/signup' + parserAnchor.search;
          } else if (loc.hash.includes('/access/resetpassword')) {
            return '/resetpassword' + parserAnchor.search;
          } else if (loc.hash.includes('/access/emailupdate')) {
            return '/emailupdate' + parserAnchor.search;
          }
          return '/signin' + parserAnchor.search;
        }
      } else if (location.pathname === '//signup') {
        // partner referral shortened url is wrongly translating links by adding extra slash in pathname
        // Eg: https://rzp.io/i/TSvxo0WXs ==> https://dashboard.razorpay.com//signup?referral_code=bluehosti2lycr
        // Since already there are huge number of links created and being used we can't just fix the translation from BE
        // as issue will persist for existing links, hence adding a fallback redirect
        location.href = location.href.replace('//signup', "/signup");
      } // else let app handle path
    } catch (e) {
      console.error(e);
    }
    return null;
  }
  const redirectPath = getRedirectPath(location);
  if (redirectPath) location.href = redirectPath;
</script>