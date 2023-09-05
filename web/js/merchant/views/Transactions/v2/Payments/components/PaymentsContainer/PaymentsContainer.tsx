import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';

import { merchantFetch } from 'merchant/utils/ajax';
import { View } from 'merchant/views/Transactions/v2/common/types';
import { showNotification } from 'merchant_common/reducers/notifications';

import Content from './Content';

const { LOADING, FTUX, FAILED_FTUX, LIST } = View;

const PaymentsContainer = ({ showNotification, location: { pathname } }) => {
  const [state, setState] = useState<View>(LOADING);
  const shouldShowFailedPayments = pathname === '/failed-payments';

  const checkFtuxView = async () => {
    const url = `payments?count=1${shouldShowFailedPayments ? '&status=failed' : ''}`;
    try {
      const {
        data: { count },
      } = await merchantFetch({ url });
      const view = count === 0 ? FTUX : LIST;
      setState(view);
    } catch (error) {
      setState(FAILED_FTUX);
      showNotification({
        type: 'error',
        message: 'Unable to fetch payments information at this moment, please try again later.',
      });
    }
  };

  useEffect(() => {
    checkFtuxView();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="content-wrapper" data-testid="payments-list">
      <Content view={state} shouldShowFailedPayments={shouldShowFailedPayments} />
    </div>
  );
};

export default connect(null, {
  showNotification,
})(PaymentsContainer);
