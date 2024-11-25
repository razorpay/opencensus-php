import { Tooltip } from '@razorpay/blade/components';
import React from 'react';

interface Props {
  showInternationalCardRequest: boolean;
  children: React.ReactElement;
}

const InternationalCardRequestContainer = ({ children, showInternationalCardRequest }: Props) => {
  if (showInternationalCardRequest) {
    return children;
  } else {
    return (
      <Tooltip content={'Please contact your bank to activate international payments'}>
        {children}
      </Tooltip>
    );
  }
};

export default InternationalCardRequestContainer;
