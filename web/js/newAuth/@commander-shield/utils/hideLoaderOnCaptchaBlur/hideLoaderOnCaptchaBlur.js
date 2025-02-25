/**
 * Hides the loader when user clicks outside of the captcha
 * challenge box
 * @param {Function} hideLoader Function to hide the loader
 */
export const hideLoaderOnCaptchaBlur = (hideLoader) => {
  /*
  Add observer to detect if the captcha iframe is added to the DOM
  One added, attach a listener to the body to detect if the user clicked outside
  the captcha challenge and tried to close it, hide the loader in that state
  Disconnect the observer and remove click listener on the body
  This whole logic is added to avoid clicking on the Signup button twice
  while the captcha is loading in slower networks.
   */
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (
          node.nodeName === 'IFRAME' &&
          node.src.includes('https://www.google.com/recaptcha/api2/bframe')
        ) {
          document.body.addEventListener(
            'click',
            () => {
              hideLoader();
            },
            { once: true },
          );
          observer.disconnect();
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
};

export default hideLoaderOnCaptchaBlur;
