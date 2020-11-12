import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
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
        <Popover align="left" theme="dark">
          <PopoverBody>{infoComp ? <span>{infoComp}</span> : <span>{infoText}</span>}</PopoverBody>
        </Popover>
      </i>
    </span>
  );
};

export default TotalAmount;
