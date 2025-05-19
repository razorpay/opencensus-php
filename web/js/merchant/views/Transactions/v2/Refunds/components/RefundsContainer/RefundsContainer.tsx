import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';

import { merchantFetch } from 'merchant/utils/ajax';
import { View } from 'merchant/views/Transactions/v2/common/types';
import { showNotification } from 'merchant_common/reducers/notifications';

import Content from './Content';
import useFetchStores from 'merchant/views/Transactions/v2/common/utils/useFetchStores';

const { LOADING, FTUX, FAILED_FTUX, LIST } = View;

const RefundsContainer = ({ showNotification }) => {
  const [state, setState] = useState<View>(LOADING);

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

  useFetchStores(showNotification);

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

export default connect(null, {
  showNotification,
})(RefundsContainer);
