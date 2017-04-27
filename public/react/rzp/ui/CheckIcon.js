export default ({ value }) => {
  var className = value
    ? ' fa fa-check text-success'
    : ' fa fa-times text-danger';

  return <i class={className} />;
};
