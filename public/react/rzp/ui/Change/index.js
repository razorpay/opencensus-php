import React from 'react';

import { getFormattedNumber } from 'rzp/utils/rzp-utils';

import './styles.styl';

export default ({ value, children }) => {
  const classNames = ['rzp-change'];

  if (value < 0) {
    classNames.push('down');
  }

  return (
    <span className={classNames.join(' ')}>
      <span className="text">
        {children ? children : value && getFormattedNumber(Math.abs(value))}
      </span>
    </span>
  );
};
