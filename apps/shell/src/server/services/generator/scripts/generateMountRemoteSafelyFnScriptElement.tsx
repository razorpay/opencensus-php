import React from 'react';

// Todo: Test in production with asset CDN URL
export const generateMountRemoteSafelyFnScriptElement = () => {
  return (
    <script
      key="mount-remote-safely-fn"
      dangerouslySetInnerHTML={{
        __html: `
        (function() {
          /**
           * Helper function to safely load a remote module by its full URL and handle future failures.
           * @param {string} url - The full remote entry URL.
           * @param {string} remoteKey - The key for the remote module (used for referencing it on window).
           * @param {number} [retries=3] - Number of retries in case of failure.
           * @param {number} [timeout=15000] - Timeout in milliseconds for loading the remote.
           * @returns {Promise<any>} - A promise that resolves to the remote module or a fallback in case of an error.
           */
          window.mountRemoteSafely = function(url, remoteKey, retries, timeout) {
            retries = (typeof retries !== 'undefined') ? retries : 3;
            timeout = (typeof timeout !== 'undefined') ? timeout : 15000;
        
            return new Promise(function(resolve, reject) {
              function attemptLoad(retryCount) {
                if (!url) {
                  console.warn('Remote URL not provided for ' + remoteKey);
                  resolve({}); // Fallback to an empty object if the remote URL is not provided
                  return;
                }
        
                // Create the script element
                var script = document.createElement('script');
                script.src = url;
                script.async = true; // Ensure the script loads asynchronously
        
                var timedOut = false;
        
                // Set a timeout to handle cases where the remote script takes too long to load
                var timeoutId = setTimeout(function() {
                  timedOut = true;
                  console.error('Loading remote ' + remoteKey + ' timed out');
                  document.head.removeChild(script);
                  if (retryCount > 0) {
                    console.warn('Retrying to load remote: ' + remoteKey + ', attempts left: ' + (retryCount - 1));
                    attemptLoad(retryCount - 1); // Retry logic
                  } else {
                    console.error('Failed to load remote after retries: ' + remoteKey);
                    resolve({}); // Fallback after retries
                  }
                }, timeout);
        
                // Script loaded successfully
                script.onload = function() {
                  if (!timedOut) {
                    clearTimeout(timeoutId);
                    if (window[remoteKey]) {
                      console.log('Remote loaded successfully: ' + remoteKey);
                      resolve(window[remoteKey]); // Return the remote module
                    } else {
                      console.error('Remote key not found on window after loading: ' + remoteKey);
                      resolve({}); // Fallback if the remote key isn't found
                    }
                  }
                };
        
                // Handle script loading errors
                script.onerror = function() {
                  clearTimeout(timeoutId);
                  console.error('Failed to load remote: ' + remoteKey);
                  document.head.removeChild(script);
                  if (retryCount > 0) {
                    console.warn('Retrying to load remote: ' + remoteKey + ', attempts left: ' + (retryCount - 1));
                    attemptLoad(retryCount - 1); // Retry logic
                  } else {
                    console.error('Failed to load remote after retries: ' + remoteKey);
                    resolve({}); // Fallback after retries
                  }
                };
        
                // Append the script to the document head
                document.head.appendChild(script);
              }
        
              // Start the loading attempt with the given number of retries
              attemptLoad(retries);
            });
          };
        })();        
        `,
      }}
    />
  );
};
