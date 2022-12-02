import React from 'react';
import Tp from 'common/ui/Tooltip';
import {
  getFormattedAmountNew,
  getFormattedNumber,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
} from 'common/utils/rzp-utils';

const Tooltip = ({ value, align, isCurrency = false, currency = 'INR' }) => {
  if (
    value < 1000 ||
    (isCurrency && i18CurrencyConversionFromMinorUnitToCommonUnit(value) < 1000)
  ) {
    return null;
  }

  return (
    <Tp align={align}>
      {isCurrency
        ? getFormattedAmountNew(value, true, currency)
        : getFormattedNumber(value, false, currency)}
    </Tp>
  );
};

export default Tooltip;
