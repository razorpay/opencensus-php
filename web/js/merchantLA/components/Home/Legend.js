import React from 'react';

import Legend, {
  LegendItem,
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'rzp/ui/Legend';
import { rupeesToPaise } from 'rzp/utils/rzp-utils';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';

import Tooltip from 'merchantLA/components/Home/Tooltip';

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
  // if passed a function, it will be called with value
  // related to the legend item
  valueTransformer = null,
  isCurrency = false,
  tooltipAlign = 'bottom',
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
            <LegendLabel color={item.color}>
              {(item.value / total * 100).toFixed(2)}%
            </LegendLabel>
            <LegendTitle>{item.label}</LegendTitle>
            <LegendContent>
              <span>
                {isCurrency
                  ? humanReadableIndianCurrency(item.value)
                  : humanReadableIndian(item.value)}
              </span>
              <Tooltip
                value={isCurrency ? rupeesToPaise(item.value) : item.value}
                isCurrency={isCurrency}
                align={tooltipAlign}
              />
            </LegendContent>
          </LegendItem>
        );
      })}
    </Legend>
  );
};
