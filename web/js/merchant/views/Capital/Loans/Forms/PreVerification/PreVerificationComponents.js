import React from 'react';
import moment from 'moment';

import Popover, { PopoverBody } from 'common/ui/Popover';
import Button from 'common/new-ui/Button';

export const NetbankingMeta = ({ text, onClick }) => {
  return (
    <div className="toggle-active-meta perfios-meta">
      <p>{text}</p>
      <Button.Primary onClick={onClick}>
        Retry
        <i className="i i-arrow-forward" />
      </Button.Primary>
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
    currentMonthRange = `01 ${currentMonth} - ${now.date()} ${currentMonth}, ${currentYear} (${now.diff(
      startOfCurrentMonth,
      'days',
    )} Days)`;
  }

  startOfCurrentMonth.subtract(1, 'days');

  while (startOfCurrentMonth > endDate) {
    const month = startOfCurrentMonth.format('MMM');
    const year = startOfCurrentMonth.year();

    months.push(`${month} ${year}`);
    startOfCurrentMonth.subtract(1, 'months');
  }

  const monthsRange = months.map((month, index) => {
    return (
      <div index={index}>
        <i className="i i-document" />
        <span>{month}</span>
      </div>
    );
  });

  const content = [
    ...monthsRange,
    <div key="date-range">
      <i className="i i-document" />
      <span>{currentMonthRange}</span>
    </div>,
  ];

  return (
    <div className="toggle-active-meta native-meta">
      <div className="toggle-active-meta__title">Statement Requirements:</div>
      <ul className="native-meta__requirements">
        <li>Use official bank statements(No Excel or CSV)</li>
        <li>Use .PDF file format</li>
        <li>Upload statements for the following months</li>
      </ul>
      <ul className="native-meta__date-range">
        {content.map((item, index) => {
          return <li key={index}>{item}</li>;
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
