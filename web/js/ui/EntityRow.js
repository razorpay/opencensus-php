import React from 'react';

// TODO: Separate out toggleChildren to ToggleEntityRow component which maintains its own state for show/hide (Check ListGroupToggler.js)
/*
   Definition: Label - value pair in a row. It can take toggleChildren to display children in next row (Check pricing plans in merchant entity)
   Example: Check MerchantEntity.js
   Props:
     Label: string /fn.
     Value: string / fn.
     toggleChildren: value (or components)
*/
export default ({ label, value, toggleChildren, ...otherProps }) => {
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
      {toggleChildren && <div class="row-children">{toggleChildren}</div>}
    </div>
  );
};
