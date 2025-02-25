// For now experiments array will always be empty since we are
// excluding that from /user call itself
const getExpStatus = (data, name) => {
  return ((data?.experiments || {})[name] || {}).result === 'on';
};

export default getExpStatus;
