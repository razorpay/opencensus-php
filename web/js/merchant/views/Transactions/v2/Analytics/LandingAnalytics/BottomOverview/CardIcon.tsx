import React from 'react';
import { connect } from 'react-redux';
import { AlertTriangleIcon, RotateCounterClockWiseIcon } from '@razorpay/blade/components';

import FailedCardIcon from 'assets/transactions/failed-cross.svg';
import RefundsCardIcon from 'assets/transactions/refund-card.svg';
import { PaymentTypes } from 'merchant/views/Transactions/v2/Analytics/types';
import User from 'common/typings/User';

const CardIcon = ({ name, user }: { name: PaymentTypes; user: User }): JSX.Element | null => {
  switch (name) {
    case PaymentTypes.Refunds:
      return user.isCountryIndia ? (
        <img src={RefundsCardIcon} alt="refund details" />
      ) : (
        <RotateCounterClockWiseIcon
          data-testid="RotateCounterClockWiseIcon"
          color="interactive.icon.information.normal"
          size="medium"
        />
      );
    case PaymentTypes.Disputes:
      return <AlertTriangleIcon color="feedback.icon.negative.intense" size="medium" />;
    case PaymentTypes.Failed:
      return <img src={FailedCardIcon} alt="failed payment details" />;
    /* istanbul ignore next */
    default:
      return null;
  }
};

const mapStateToProps = (state: any) => ({
  user: state.session.user,
});
export default connect(mapStateToProps)(CardIcon);
