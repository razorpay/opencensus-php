const isProd = window.location.hostname.endsWith('razorpay.com');

const getStageBranch = () => {
  if (isProd) return '';
  const branchFromQueryParam = new URLSearchParams(location.search).get('branch');
  if (!branchFromQueryParam) {
    return 'master/';
  }
  return `${branchFromQueryParam}/`;
};

const getScriptSrc = (project) => {
  const parentScriptHost = isProd ? 'cdn.razorpay.com' : 'betacdn.np.razorpay.in';
  const stageBranch = getStageBranch();
  return `//${parentScriptHost}/capital/${stageBranch}${project}/main.js`;
};

const random = (length) =>
  // eslint-disable-next-line no-bitwise
  Array.from({ length }, () => (~~(Math.random() * 36)).toString(36)).join('');

const scripts = new Map();

export function loadScript(project, externals) {
  if (scripts.has(project)) {
    return scripts.get(project);
  }
  const promise = new Promise((resolve, reject) => {
    const el = document.createElement('script');
    const cb = `__webpack_cb_${random(20)}`;
    el.dataset.cb = cb;
    window[cb] = { ...externals };
    el.onload = () => {
      const output = window[cb].default;
      if (output) {
        delete window[cb];
        el.parentNode.removeChild(el);
        resolve(output);
      } else {
        el.onerror();
      }
    };
    el.onerror = () => {
      delete window[cb];
      scripts.delete(project);
      el.parentNode.removeChild(el);
      reject();
    };
    el.src = getScriptSrc(project);
    el.async = true;
    document.documentElement.appendChild(el);
  });
  scripts.set(project, promise);
  return promise;
}
