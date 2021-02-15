import React, { Fragment } from 'react';
import moment from 'moment';

import Popover, { PopoverBody } from 'common/ui/Popover';

export const NetbankingMeta = () => {
  return (
    <div className="toggle-active-meta perfios-meta">
      <div className="toggle-active-meta__title">
        Upload statements securely from the account connected to Razorpay. None of you login details
        will be stored.
      </div>
    </div>
  );
};

export const NativeUploadMeta = () => {
  const now = moment();
  const startOfCurrentMonth = moment().startOf('month');
  const endDate = moment().subtract('6', 'months');
  const currentMonth = startOfCurrentMonth.format('MMM');
  const currentYear = startOfCurrentMonth.year();
  const months = [];

  let currentMonthRange = `01 ${currentMonth}, ${currentYear}`;

  if (now.date() !== startOfCurrentMonth.date()) {
    currentMonthRange = `01 ${currentMonth} - ${now.date()} ${currentMonth}, ${currentYear}`;
  }

  startOfCurrentMonth.subtract(1, 'days');

  while (startOfCurrentMonth > endDate) {
    const month = startOfCurrentMonth.format('MMMM');
    const year = startOfCurrentMonth.year();

    months.push(`${month}, ${year}`);
    startOfCurrentMonth.subtract(1, 'months');
  }

  const monthsRange = months.map((month, index) => {
    return (
      <span index={index}>
        {month}
        {index < months.length - 1 ? ',' : ''}
      </span>
    );
  });

  const content = [<span>{currentMonthRange} &</span>, <Fragment>{monthsRange}</Fragment>];

  return (
    <div className="toggle-active-meta native-meta">
      <div className="toggle-active-meta__title">
        Upload PDF statements that include transactions for the months:
      </div>
      <ul>
        {content.map((item, index) => {
          return (
            <li style={{ fontWeight: 'bold', textTransform: 'uppercase' }} key={index}>
              {item}
            </li>
          );
        })}
      </ul>
    </div>
  );
};

export const NetbankingHint = () => {
  return (
    <div className="netbanking-hint-wrapper">
      <Popover
        className="hint-popover"
        align="right"
        theme="dark"
        parentQuerySelector=".netbanking-hint-wrapper"
      >
        <PopoverBody>
          <p>
            <i className="i fa fa-bolt" />
            Instant processing under 1 min
          </p>
          <p>
            <i className="i fa fa-share-alt" />
            No mistakes with perfect information sharing
          </p>
        </PopoverBody>
      </Popover>

      <div className="netbanking-hint">
        <i className="i fa fa-star" />
        RECOMMENDED
      </div>
    </div>
  );
};
