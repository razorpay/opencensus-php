import React from 'react';
import Tp from 'rzp/ui/Tooltip';
import {
  getFormattedAmountNew,
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
        ? getFormattedAmountNew(value, true, currency)
        : getFormattedNumber(value)}
    </Tp>
  );
};

export default Tooltip;
