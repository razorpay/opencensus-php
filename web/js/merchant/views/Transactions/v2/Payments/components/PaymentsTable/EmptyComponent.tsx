import React, { useEffect } from 'react';
import { RouteComponentProps, withRouter } from 'react-router-dom';

import NoSearchResult from 'merchant/views/Transactions/v2/common/components/NoSearchResult';
import { TransactionsPagesMap } from 'merchant/views/Transactions/v2/common/constants';
import { trackNoSearchResult } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const EmptyComponent = ({ location: { pathname } }: RouteComponentProps): JSX.Element => {
  useEffect(() => {
    trackNoSearchResult({ section: TransactionsPagesMap[pathname] });
  }, [pathname]);
  return <NoSearchResult page={Page.PAYMENTS} />;
};

export default withRouter(EmptyComponent);
