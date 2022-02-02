import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';

export default function ReducingRepaymentTooltip() {
  return (
    <small className="small">
      <i className="i i-info-outline" />
      <Popover align="top" theme="dark" parentQuerySelector=".withdrawals__top-summary">
        <PopoverBody>
          <div className="text-center">
            Earlier you pay before the due date, <br /> lesser the interest amount'
          </div>
        </PopoverBody>
      </Popover>
    </small>
  );
}
