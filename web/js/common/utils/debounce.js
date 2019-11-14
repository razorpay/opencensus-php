const debounce = (fn, delay = 0) => {
  let timer = null;

  return function(...args) {
    window.clearTimeout(timer);

    timer = window.setTimeout(fn.bind(this, ...args), delay);
  };
};

export default debounce;
