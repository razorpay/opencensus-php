export default ({ label, value, ...otherProps }) => {
  if (value === null || value === undefined || value === '') {
    value = '--';
  }

  return (
    <div class="row detail-row" {...otherProps}>
      <label class="col-sm-4">
        {typeof label === 'function' ? label() : label}
      </label>
      <div class="col-sm-8">
        {typeof value === 'function' ? value() : value + ''}
      </div>
    </div>
  );
};
