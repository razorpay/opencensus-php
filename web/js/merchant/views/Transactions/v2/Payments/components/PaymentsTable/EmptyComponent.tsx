import React from 'react';

import NoSearchResult from 'merchant/views/Transactions/v2/common/components/NoSearchResult';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const EmptyComponent = (): JSX.Element => {
  return <NoSearchResult page={Page.PAYMENTS} />;
};

export default EmptyComponent;
