import React, { useEffect } from 'react';
import { withRouter } from 'shell/deprecated/withRouter';
import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import NoSearchResult from 'apps/self-serve/src/App/Transactions/v2/common/components/NoSearchResult';
import { TransactionsPagesMap } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { trackNoSearchResult } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { Page } from 'apps/self-serve/src/App/Transactions/v2/common/types';

const EmptyComponent = ({ location: { pathname } }: RouteComponentProps): JSX.Element => {
  useEffect(() => {
    trackNoSearchResult({ section: TransactionsPagesMap[pathname] });
  }, [pathname]);
  return <NoSearchResult page={Page.PAYMENTS} />;
};

export default withRouter(EmptyComponent);
