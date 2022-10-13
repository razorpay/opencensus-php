<script>
  // redirect to react.js auth flows post angular auth eol
  const getRedirectPath = (loc = {}) => {
    try {
      if (loc.pathname === '/') {
        // old angular paths have /access/ in it
        if (loc.hash.includes('/access/')) {
          // oldAngularPathsRegexQ matches url created by /captcha page
          const oldAngularPathsRegexQ = /\?\#\/access\/(signin|signup|resetpassword|forgotpassword)\?/;
          const parserAnchor = document.createElement('a');
          if (loc.href.match(oldAngularPathsRegexQ)) {
            parserAnchor.href = loc.href.replace(oldAngularPathsRegexQ, '?');
            if (loc.hash.includes('/access/signup?')) {
              return '/signup' + parserAnchor.search;
            } else if (loc.hash.includes('/access/resetpassword?')) {
              return '/resetpassword' + parserAnchor.search;
            }
            return '/signin' + parserAnchor.search;
          }
          // if only captcha redirect didn't happen.
          const oldAngularPathsRegex = /\#\/access\/(signin|signup|resetpassword|forgotpassword)/;
          parserAnchor.href = loc.href.replace(oldAngularPathsRegex, '');;
          if (loc.hash.includes('/access/signup')) {
            return '/signup' + parserAnchor.search;
          } else if (loc.hash.includes('/access/resetpassword')) {
            return '/resetpassword' + parserAnchor.search;
          }
          return '/signin' + parserAnchor.search;
        }
      } // else let app handle path
    } catch (e) {
      console.error(e);
    }
    return null;
  }
  const redirectPath = getRedirectPath(location);
  if (redirectPath) location.href = redirectPath;
</script>