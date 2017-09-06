export default ({ label, value, ...otherProps }) => {
  if (value === null || value === undefined || value === '') {
    value = '--';
  }

  return (
    <div class="pair-group-item" {...otherProps}>
      {typeof label === 'function'
        ? label()
        : <div class="pair-label">{label}</div>}
      {/*<span class="pair-separator">:</span>*/}
      <div class="pair-value">
        {typeof value === 'function'
          ? value()
          : <span class="label--primary">{value + ''}</span>}
      </div>
    </div>
  );
};
