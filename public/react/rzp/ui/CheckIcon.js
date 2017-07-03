export default ({ value }) => {
  var className = value
    ? 'icon icon-check text-success'
    : 'icon icon-close text-danger';

  return <i class={className} />;
};
