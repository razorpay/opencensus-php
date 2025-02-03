import React, { useEffect } from 'react';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { withRouter } from 'common/deprecated/withRouter';
import NoSearchResult from 'merchant/views/Transactions/v2/common/components/NoSearchResult';
import { TransactionsPagesMap } from 'merchant/views/Transactions/v2/common/constants';
import { trackNoSearchResult } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';
import EmptyList from 'merchant/components/EmptyList';

const EmptyComponent = ({ location: { pathname } }: RouteComponentProps): JSX.Element => {
  useEffect(() => {
    trackNoSearchResult({ section: TransactionsPagesMap[pathname] });
  }, [pathname]);
  return <NoSearchResult page={Page.PAYMENTS} />;
};

export const EmptyRoutesComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>No route payments found for the selected duration and criteria!</div>
        <div>Create a linked account first to route payments.</div>
      </React.Fragment>
    }
  />
);

export default withRouter(EmptyComponent);
