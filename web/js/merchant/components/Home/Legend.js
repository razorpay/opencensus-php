import React from 'react';

import Legend, { LegendItem, LegendLabel, LegendTitle, LegendContent } from 'common/ui/Legend';
import { i18CurrencyConversionFromCommonUnitToMinorUnit } from 'common/utils/rzp-utils';
import { i18HumanReadableNumerals, i18HumanReadableCurrency } from 'common/utils/numerals';

import Tooltip from 'merchant/components/Home/Tooltip';

/*
 * This component automatically calculates the percentage to be shown
 * in each `LegendLabel` and shows the `LegendItem`s, data to be passed
 * should be like
 *
 * {
 *   "label": "Cards",
 *   "value": "1200", // should be a number
 *   "color": "#FFFFFF"
 * }
 */

export default ({
  data,
  alignment = 'horizontal',
  isCurrency = false,
  tooltipAlign = 'bottom',
  user,
}) => {
  if (!Array.isArray(data) || data.length === 0) {
    return null;
  }

  const total = data.reduce((sum, item) => {
    return sum + item.value;
  }, 0);

  if (!total) {
    return null;
  }

  return (
    <Legend alignment={alignment}>
      {data.map((item, key) => {
        return (
          <LegendItem key={key}>
            <LegendLabel color={item.color}>{((item.value / total) * 100).toFixed(2)}%</LegendLabel>
            <LegendTitle>{item.label}</LegendTitle>
            <LegendContent>
              <span>
                {isCurrency
                  ? i18HumanReadableCurrency(item.value, user.merchant.currency)
                  : i18HumanReadableNumerals(item.value, user.merchant.currency)}
              </span>
              <Tooltip
                value={
                  isCurrency
                    ? i18CurrencyConversionFromCommonUnitToMinorUnit(item.value)
                    : item.value
                }
                isCurrency={isCurrency}
                align={tooltipAlign}
                currency={user.merchant.currency}
              />
            </LegendContent>
          </LegendItem>
        );
      })}
    </Legend>
  );
};
