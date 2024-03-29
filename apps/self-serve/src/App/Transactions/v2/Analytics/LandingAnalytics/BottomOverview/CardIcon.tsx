import React from 'react';
import { AlertTriangleIcon } from '@razorpay/blade/components';

import FailedCardIcon from 'apps/self-serve/src/assets/failed-cross.svg';
import RefundsCardIcon from 'apps/self-serve/src/assets/refund-card.svg';
import { PaymentTypes } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';

const CardIcon = ({ name }: { name: PaymentTypes }): JSX.Element | null => {
  switch (name) {
    case PaymentTypes.Refunds:
      return <img src={RefundsCardIcon} alt="refund details" />;
    case PaymentTypes.Disputes:
      return <AlertTriangleIcon color="feedback.icon.negative.intense" size="medium" />;
    case PaymentTypes.Failed:
      return <img src={FailedCardIcon} alt="failed payment details" />;
    /* istanbul ignore next */
    default:
      return null;
  }
};

export default CardIcon;
