import React from 'react';

//types
import { TogglePropsInterface } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';

const Toggle: React.FC<TogglePropsInterface> = ({ isOpen, currency, onToggleClick }) => {
  const toggleText: string = isOpen === currency ? 'Hide' : 'Account';
  return (
    <div className="toggle-container" data-testid="toggle">
      <p className="toggle-text" onClick={onToggleClick}>
        {toggleText} details
      </p>

      <span className="icon-wrapper">
        <i className="i i-chevron-right" />
      </span>
    </div>
  );
};

export default Toggle;
