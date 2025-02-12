import React from 'react';
import { connect } from 'react-redux';
import {
  Amount,
  Box,
  Card,
  CardBody,
  CardHeader,
  CardHeaderLeading,
} from '@razorpay/blade/components';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

function CurrentBalance({ currentBalance, user }) {
  const balance = currentBalance.data?.balance || 0;

  return (
    <Card>
      <CardHeader>
        <CardHeaderLeading title="Current Balance" />
      </CardHeader>
      <CardBody>
        <Box display="flex" alignItems="center">
          {balance < 0 && <p className="negative-marker">-</p>}
          <Amount
            size="large"
            weight="semibold"
            type="heading"
            value={i18nifyConvertToMajorUnit(balance, user.merchant.currency)}
            currency={user.merchant.currency}
            color={balance < 0 ? 'feedback.text.negative.intense' : ''}
          />
        </Box>
      </CardBody>
    </Card>
  );
}

const mapStateToProps = (state) => {
  return {
    ...state.session,
    currentBalance: state.home.current_balance,
  };
};

export default connect(mapStateToProps, null)(CurrentBalance);
