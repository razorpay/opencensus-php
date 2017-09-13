export default ({ label, value, children, ...otherProps }) => {
  if (value === null || value === undefined || value === '') {
    value = '--';
  }

  return (
    <div class="pair-group-item" {...otherProps}>
      {typeof label === 'function'
        ? label()
        : <div class="pair-label">
            {label}
          </div>}
      {/*<span class="pair-separator">:</span>*/}
      <div class="pair-value">
        {children
          ? children
          : typeof value === 'function'
            ? value()
            : <span class="label--primary">
                {value + ''}
              </span>}
      </div>
    </div>
  );
};
