import browserDetect from 'browser-detect';

const getBrowserDetails = () => {
  const browser = browserDetect();
  return {
    device: browser.mobile ? 'mobile' : 'web',
    browser: browser.name,
    browserVersion: browser.version,
    os: browser.os,
  };
};

export default getBrowserDetails;
