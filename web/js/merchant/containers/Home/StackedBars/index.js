import React from 'react';

import { isFunction, getPercentage } from 'common/utils/rzp-utils';
// eslint-disable-next-line import/no-named-as-default
import ProgressBar from 'common/ui/ProgressBar';

const StackedBars = ({
  textKey = 'text',
  formatText,
  valueKey = 'value',
  formatValue,
  colorKey = 'color',
  getColor,
  data = [],
  orderBy = null,
  user,
}) => {
  const totalSum = data.reduce((sum, item) => {
    const value = (item.hasOwnProperty(valueKey) && +item[valueKey]) || 0;
    const text = (item.hasOwnProperty(textKey) && item[textKey]) || '';
    const color = (item.hasOwnProperty(colorKey) && item[colorKey]) || null;

    item.__value = value;
    item.__formattedValue =
      (isFunction(formatValue) && formatValue(value, item, user.merchant.currency)) || value;
    item.__text = text;
    item.__formattedText = (isFunction(formatText) && formatText(text, item)) || text;
    item.__color = (isFunction(getColor) && getColor(text)) || color;

    return sum + value;
  }, 0);

  if (isFunction(orderBy)) {
    data = data.slice();

    data.sort(orderBy);
  }

  return (
    <div className="rzp-stacked-bars">
      {data.map((item, index) => {
        const percentage = getPercentage(totalSum, item.__value);

        return (
          <div className="rzp-stacked-bar" key={index}>
            <div className="rzp-stacked-bar-header clearfix">
              <div className="rzp-stacked-bar-title pull-left">{item.__formattedText}</div>
              <div className="rzp-stacked-bar-value pull-right">
                {`${item.__formattedValue}  `}
                <span className="text-fade">{`(${percentage}%)`}</span>
              </div>
            </div>
            <div className="rzp-stacked-bar-body">
              <ProgressBar value={item.__value} color={item.__color} max={totalSum} type="custom" />
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default StackedBars;
