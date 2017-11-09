import React from 'react';

/*
   Definition: Label - value pair in a row.
   Example: Check MerchantEntity.js
   Props:
     Label: string / fn.
     Value: string / fn.
*/
export default ({ label, value, ...otherProps }) => {
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
        {typeof value === 'function' ? value() : <span>{value + ''}</span>}
      </div>
    </div>
  );
};
