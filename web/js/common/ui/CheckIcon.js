export default ({ value }) => {
  var className = value ? 'i i-check text-success' : 'i i-close text-danger';

  return <i className={className} />;
};
