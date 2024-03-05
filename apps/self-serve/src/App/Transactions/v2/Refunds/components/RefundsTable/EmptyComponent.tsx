import React, { useEffect } from 'react';

import NoSearchResult from 'apps/self-serve/src/App/Transactions/v2/common/components/NoSearchResult';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { trackNoSearchResult } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { Page } from 'apps/self-serve/src/App/Transactions/v2/common/types';

const EmptyComponent = (): JSX.Element => {
  useEffect(() => {
    trackNoSearchResult({ section: TransactionsPagesMap[TransactionsEntityRoute.REFUNDS] });
  }, []);
  return <NoSearchResult page={Page.REFUNDS} />;
};

export default EmptyComponent;
