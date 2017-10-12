import React from 'react';

export default ({ label, value, children, ...otherProps }) => {
  if (value === null || value === undefined || value === '') {
    value = '--';
  }

  return (
    <div class="row-item" {...otherProps}>
      {typeof label === 'function' ? (
        label()
      ) : (
        <div class="row-label">{label}</div>
      )}
      <div class="row-value">
        {children ? (
          children
        ) : typeof value === 'function' ? (
          value()
        ) : (
          <span>{value + ''}</span>
        )}
      </div>
    </div>
  );
};
