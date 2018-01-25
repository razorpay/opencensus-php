export default ({ value }) => {
  var className = value ? 'i i-done text-success' : 'i i-close text-danger';

  return <i class={className} />;
};
