import React, { useState } from 'react';
import moment from 'moment';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { classList } from 'common/utils/rzp-utils';
import { TENURE_OPTIONS_30, TENURE_OPTIONS_90 } from 'merchant/views/Capital/CashAdvance/constants';
import './StaticTenureSelector.styl';

const StaticTenureSelector = ({ isRepaymentFrequencyDays90, handleDueDateChange, withdrawCTA }) => {
  const defaultValue = isRepaymentFrequencyDays90
    ? null
    : TENURE_OPTIONS_30[TENURE_OPTIONS_30.length - 1];
  const [selectedOption, setSelectedOption] = useState(defaultValue);
  const options = isRepaymentFrequencyDays90 ? TENURE_OPTIONS_90 : TENURE_OPTIONS_30;

  const handleOptionClick = (val) => {
    setSelectedOption(val);

    // BE expects due_date to be 1 day less
    // in case of end_day_limit
    // This logic to decrease 1 day only when
    // 30 or 90 days option selected
    const datesToBeAdded = val === 30 || val === 90 ? val - 1 : val;
    handleDueDateChange(moment().utc().utcOffset(330).add(datesToBeAdded, 'days').endOf('date'));
  };

  return (
    <div className="static-tenure">
      <div className="flex gap--12">
        <div className="static-tenure__label">
          I will repay the amount by{' '}
          <small className="help-content small small-icon">
            <i className="i i-info-outline" />
            <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
              <PopoverBody>
                <div className="text-center">Your equated repayments will start from tomorrow</div>
              </PopoverBody>
            </Popover>
          </small>
        </div>
        {!selectedOption && (
          <div className="static-tenure__error">Choose a repayment tenure to withdraw</div>
        )}
      </div>
      <div className="static-tenure__options">
        {options.map((option, index) => {
          return (
            <div
              className={classList(
                'static-tenure__option',
                index === 0 && 'static-tenure__option--left',
                index === options.length - 1 && 'static-tenure__option--right',
                selectedOption === option && 'static-tenure__option--active',
              )}
              key={option}
              onClick={() => handleOptionClick(option)}
            >
              {option} days
            </div>
          );
        })}
        <div className="static-tenure__cta-container">
          {withdrawCTA({ tenure: selectedOption })}
        </div>
      </div>
      <div className="static-tenure__banner">
        <img alt="party_icon" src="/dist/css/assets/capital/party.svg" />
        <p>You can always repay before the due date without any additional charges.</p>
      </div>
    </div>
  );
};

export default StaticTenureSelector;
