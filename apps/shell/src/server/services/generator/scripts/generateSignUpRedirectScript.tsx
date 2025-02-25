import React from 'react';

export const generateSignUpRedirectScript = () => {
  return (
    <script
      key="signup-redirector"
      type="module"
      dangerouslySetInnerHTML={{
        __html: `
          // redirect to react.js auth flows post angular auth eol
          const getRedirectPath = (loc = window.location) => {
            try {
              if (loc.pathname === '/') {
                if (loc.hash.includes('/access/')) {
                  const oldAngularPathsRegexQ = /\\?\\#\\/access\\/(signin|signup|resetpassword|forgotpassword|emailupdate)\\?/;
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
                  
                  const oldAngularPathsRegex = /\\#\\/access\\/(signin|signup|resetpassword|forgotpassword|emailupdate)/;
                  parserAnchor.href = loc.href.replace(oldAngularPathsRegex, '');
                  if (loc.hash.includes('/access/signup')) {
                    return '/signup' + parserAnchor.search;
                  } else if (loc.hash.includes('/access/resetpassword')) {
                    return '/resetpassword' + parserAnchor.search;
                  } else if (loc.hash.includes('/access/emailupdate')) {
                    return '/emailupdate' + parserAnchor.search;
                  }
                  return '/signin' + parserAnchor.search;
                }
              } else if (loc.pathname === '//signup') {
                loc.href = loc.href.replace('//signup', '/signup');
              }
            } catch (e) {
              console.error(e);
            }
            return null;
          }
          const redirectPath = getRedirectPath(location);
          if (redirectPath) loc.href = redirectPath;
        `,
      }}
    />
  );
};
