import { ONE_TAP_SCALING } from './GoogleAuthConstants';

export const resizeOneTapIframe = (oneTapIframe, oneTapWrapperRef) => {
  // MutationObserver observes for the changes in height of onetap iframe as it changes 2-3 times after it loads on browser and when someone clicks "more account"
  // hence accordingly we need set explicit height to onetap wrapper as its taking more space that required due to css scale down(ONE_TAP_SCALING).
  const observer = new MutationObserver(() => {
    if (oneTapIframe.style.height) {
      oneTapWrapperRef.current.style.height = `${Math.round(
        parseInt(oneTapIframe.style.height, 10) * ONE_TAP_SCALING + 20,
      )}px`;
    }
  });
  observer.observe(oneTapIframe, { attributes: true, attributeFilter: ['style'] });
};

export const initOneTap = (context, authClientId, callback, notifyCallback) => {
  // setting up an interval to wait and check if onetap(window.google) has loaded from script
  // assuming window.google would be available here wouldn't work.
  const checkScriptLoadingInterval = setInterval(() => {
    if (window.google) {
      window.google.accounts.id.prompt(notifyCallback);
      clearInterval(checkScriptLoadingInterval);
    }
  }, 100);
};

/* 
Since google script is not loaded in shield code itself but at consumer end 
hence setting up an interval to wait and check if google button(window.gapi) 
has loaded from script or not.
*/
export const loadingGauthScript = () => {
  return new Promise((resolve) => {
    const checkScriptLoadingInterval = setInterval(() => {
      if (window.google) {
        clearInterval(checkScriptLoadingInterval);
        resolve(window.google);
      }
    }, 100);
  });
};
