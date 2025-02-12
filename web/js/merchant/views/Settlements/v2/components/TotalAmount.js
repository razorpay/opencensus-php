import React from 'react';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';

const TotalAmount = ({ type, value, infoText, infoComp, isNew, currency }) => {
  return (
    <span className="settled-amount">
      Total {type} amount:{' '}
      {isNew && type === 'debit' ? (
        <Amount value={value * -1} currency={currency} />
      ) : (
        <Amount value={value} currency={currency} />
      )}
      <i className="i i-info-circle">
        <PopoverComponent align="bottom" theme="dark" data-testid="total-amount-popover">
          <PopoverBody>{infoComp ? <span>{infoComp}</span> : <span>{infoText}</span>}</PopoverBody>
        </PopoverComponent>
      </i>
    </span>
  );
};

export default TotalAmount;
