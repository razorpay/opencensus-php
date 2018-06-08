import React from 'react';

import { isFunction, getPercentage } from 'rzp/utils/rzp-utils';
import ProgressBar from 'rzp/ui/ProgressBar';

const StackedBars = ({
  textKey = 'text',
  formatText,
  valueKey = 'value',
  formatValue,
  colorKey = 'color',
  getColor,
  data = [],
}) => {
  const totalSum = data.reduce((sum, item) => {
    const value = (item.hasOwnProperty(valueKey) && +item[valueKey]) || 0,
      text = (item.hasOwnProperty(textKey) && item[textKey]) || '',
      color = (item.hasOwnProperty(colorKey) && item[colorKey]) || null;

    item.__value = value;
    item.__formattedValue =
      (isFunction(formatValue) && formatValue(value, item)) || value;
    item.__text = text;
    item.__formattedText =
      (isFunction(formatText) && formatText(text, item)) || text;
    item.__color = (isFunction(getColor) && getColor(text)) || color;

    return sum + value;
  }, 0);

  return (
    <div className="rzp-stacked-bars">
      {data.map((item, index) => {
        const percentage = getPercentage(totalSum, item.__value);

        return (
          <div className="rzp-stacked-bar" key={index}>
            <div className="rzp-stacked-bar-header clearfix">
              <div className="rzp-stacked-bar-title pull-left">
                {item.__formattedText}
              </div>
              <div className="rzp-stacked-bar-value pull-right">
                {item.__formattedValue + `(${percentage}%)`}
              </div>
            </div>
            <div className="rzp-stacked-bar-body">
              <ProgressBar
                value={item.__value}
                color={item.__color}
                max={totalSum}
                type="custom"
              />
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default StackedBars;
