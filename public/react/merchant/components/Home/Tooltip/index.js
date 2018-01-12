import React from 'react';
import Tp from 'rzp/ui/Tooltip';
import {
  getFormattedAmount,
  getFormattedNumber,
  paiseToRupees,
} from 'rzp/utils/rzp-utils';

const Tooltip = ({ value, align, isCurrency = false, currency = 'INR' }) => {
  if (value < 1000 || (isCurrency && paiseToRupees(value) < 1000)) {
    return null;
  }

  return (
    <Tp align={align}>
      {isCurrency
        ? getFormattedAmount(value, true, currency)
        : getFormattedNumber(value)}
    </Tp>
  );
};

export default Tooltip;
