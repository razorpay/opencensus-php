// Todo: delete this file, it's available in @dashboard/shared-utils
const debounce = (fn, delay = 0) => {
  let timer = null;

  return function debouncedFunction(...args) {
    function cancel() {
      window.clearTimeout(timer);
    }
    cancel();
    // eslint-disable-next-line babel/no-invalid-this
    timer = window.setTimeout(fn.bind(this, ...args), delay);

    debouncedFunction.cancel = cancel;
  };
};

export default debounce;
