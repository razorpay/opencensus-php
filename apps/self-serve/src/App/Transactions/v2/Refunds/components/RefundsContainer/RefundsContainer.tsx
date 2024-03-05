import React, { useEffect, useState } from 'react';

import { merchantFetch } from '@dashboard/shared-utils/ajax';
import { useStore } from 'shell/commonStore';

import Content from './Content';
import { View } from 'apps/self-serve/src/App/Transactions/v2/common/types';

const { LOADING, FTUX, FAILED_FTUX, LIST } = View;

const RefundsContainer = () => {
  const [state, setState] = useState<View>(LOADING);
  const showNotification = useStore((state) => state.showNotification);

  const checkFtuxView = async () => {
    try {
      const {
        data: { count },
      } = await merchantFetch({ url: 'refunds?count=1' });
      const view = count === 0 ? FTUX : LIST;
      setState(view);
    } catch (error) {
      setState(FAILED_FTUX);
      showNotification({
        type: 'error',
        message: 'Unable to fetch refunds information at this moment, please try again later.',
      });
    }
  };

  useEffect(() => {
    checkFtuxView();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="content-wrapper" data-testid="refunds-list">
      <Content view={state} />
    </div>
  );
};

export default RefundsContainer;
