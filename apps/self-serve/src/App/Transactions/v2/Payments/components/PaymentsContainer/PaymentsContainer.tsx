import React, { useEffect, useState } from 'react';
import { useLocation } from 'react-router-dom';

import { merchantFetch } from '@dashboard/shared-utils/ajax';
import { useStore } from 'shell/commonStore';
import { View } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import Content from './Content';

const { LOADING, FTUX, FAILED_FTUX, LIST } = View;

const PaymentsContainer = () => {
  const showNotification = useStore((state) => state.showNotification);
  const [state, setState] = useState<View>(LOADING);
  const { pathname } = useLocation();
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

export default PaymentsContainer;
