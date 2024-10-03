import React from 'react';

import { AVAILABLE_BORDER_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

export const RoundedIcon = (props) => {
  const isSelected = props.selectedButton === AVAILABLE_BORDER_STYLE.ROUNDED;

  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 9 9" fill="none">
      <path
        d="M9 1.25H5.5C3.29086 1.25 1.5 3.04086 1.5 5.25V8.75"
        stroke={isSelected ? '#00F' : '#40566D'}
        strokeWidth="1.5"
      />
    </svg>
  );
};
