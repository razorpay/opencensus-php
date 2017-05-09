/* global document:true */

export default src => {
  return new Promise((resolve, reject) => {
    try {
      let script = document.createElement('script');
      script.async = true;
      script.src = src;
      script.onload = function onLoad() {
        resolve();
      };
      document.head.appendChild(script);
    } catch (e) {
      reject(e);
    }
  });
};
