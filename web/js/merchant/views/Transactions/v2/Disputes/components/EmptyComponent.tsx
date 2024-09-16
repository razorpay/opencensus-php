import React, { useEffect } from 'react';

import NoSearchResult from 'merchant/views/Transactions/v2/common/components/NoSearchResult';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { trackNoSearchResult } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const EmptyComponent = (): JSX.Element => {
  useEffect(() => {
    trackNoSearchResult({ section: TransactionsPagesMap[TransactionsEntityRoute.DISPUTES] });
  }, []);
  return <NoSearchResult page={Page.DISPUTES} />;
};

export default EmptyComponent;
