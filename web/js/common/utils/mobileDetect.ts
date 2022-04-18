type MobileDetect = {
  isMobile: () => boolean;
  isDesktop: () => boolean;
  isAndroid: () => boolean;
  isIos: () => boolean;
  isSSR: () => boolean;
  isWebView: () => boolean;
};

const getMobileDetect = (userAgent: string = navigator.userAgent): MobileDetect => {
  const isAndroid = (): boolean => Boolean(userAgent.match(/Android/i));
  const isIos = (): boolean => Boolean(userAgent.match(/iPhone|iPad|iPod/i));
  const isOpera = (): boolean => Boolean(userAgent.match(/Opera Mini/i));
  const isWindows = (): boolean => Boolean(userAgent.match(/IEMobile/i));
  const isSSR = (): boolean => Boolean(userAgent.match(/SSR/i));

  const isMobile = (): boolean => Boolean(isAndroid() || isIos() || isOpera() || isWindows());
  const isDesktop = (): boolean => Boolean(!isMobile() && !isSSR());
  // we are specifically expecting source = webview in query params from app team when ever they open pages in webview.
  const isWebView = (): boolean =>
    Boolean(userAgent.includes('wv') || window.location.search.includes('source=webview'));

  return {
    isMobile,
    isDesktop,
    isAndroid,
    isIos,
    isSSR,
    isWebView,
  };
};

export default getMobileDetect;
