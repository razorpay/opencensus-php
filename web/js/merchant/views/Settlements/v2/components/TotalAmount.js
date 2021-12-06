import React from 'react';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import Amount from 'common/ui/Amount';

const TotalAmount = ({ type, value, infoText, infoComp, isNew }) => {
  return (
    <span class="settled-amount">
      Total {type} amount:{' '}
      {isNew && type === 'debit' ? (
        <Amount value={value * -1} currency="INR" />
      ) : (
        <Amount value={value} currency="INR" />
      )}
      <i class="i i-info-circle">
        <PopoverComponent align="bottom" theme="dark" data-testid="total-amount-popover">
          <PopoverBody>{infoComp ? <span>{infoComp}</span> : <span>{infoText}</span>}</PopoverBody>
        </PopoverComponent>
      </i>
    </span>
  );
};

export default TotalAmount;
