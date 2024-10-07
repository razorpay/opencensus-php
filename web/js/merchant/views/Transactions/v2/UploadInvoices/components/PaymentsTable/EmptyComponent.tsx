import React, { useEffect } from 'react';
import { useLocation } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import NoSearchResult from 'merchant/views/Transactions/v2/common/components/NoSearchResult';
import { TransactionsPagesMap } from 'merchant/views/Transactions/v2/common/constants';
import { trackNoSearchResult } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const EmptyComponent = (): JSX.Element => {
  const location = useLocation();
  useEffect(() => {
    trackNoSearchResult({ section: TransactionsPagesMap[location.pathname] });
  }, [location.pathname]);
  return <NoSearchResult page={Page.PAYMENTS} />;
};

export default withRouter(EmptyComponent);
