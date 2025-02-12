import React from 'react';

export default ({ label, value, children, pairClass = '', ...otherProps }) => {
  if (value === null || value === undefined || value === '') {
    value = '--';
  }

  return (
    <div className={`pair-group-item ${pairClass}`} {...otherProps}>
      {typeof label === 'function' ? label() : <div className="pair-label">{label}</div>}
      {/*<span className="pair-separator">:</span>*/}
      <div className="pair-value">
        {children ? (
          children
        ) : typeof value === 'function' ? (
          value()
        ) : (
          <span className="label--primary">{`${value}`}</span>
        )}
      </div>
    </div>
  );
};
