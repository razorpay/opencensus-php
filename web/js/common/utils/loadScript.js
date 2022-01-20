export default (src, options = { async: true }) => {
  return new Promise((resolve, reject) => {
    try {
      const script = document.createElement('script');
      script.src = src;
      Object.keys(options).forEach((key) => {
        script[key] = options[key];
      });
      script.onload = resolve;
      document.head.appendChild(script);
    } catch (e) {
      reject(e);
    }
  });
};
