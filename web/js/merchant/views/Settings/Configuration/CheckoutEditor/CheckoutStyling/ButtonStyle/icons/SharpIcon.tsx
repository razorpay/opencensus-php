import React from 'react';

import { AVAILABLE_BORDER_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

export const SharpIcon = (props) => {
  const isSelected = props.selectedButton === AVAILABLE_BORDER_STYLE.SHARP;

  return (
    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 9 9" fill="none">
      <path d="M8.5 1.25H1V8.75" stroke={isSelected ? '#00F' : '#40566D'} strokeWidth="1.5" />
    </svg>
  );
};
