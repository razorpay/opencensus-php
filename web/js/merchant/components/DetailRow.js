export default ({ label, value, ...otherProps }) => {
  if (value === null || value === undefined || value === '') {
    value = '--';
  }

  return (
    <div className="list-group-item" {...otherProps}>
      {typeof label === 'function' ? label() : <span>{label}</span>}
      {typeof value === 'function' ? value() : <span>{value + ''}</span>}
    </div>
  );
};
