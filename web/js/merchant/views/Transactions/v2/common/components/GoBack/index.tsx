import React from 'react';
import { Link, ChevronLeftIcon, Box } from '@razorpay/blade/components';
import qs from 'query-string';
import { withRouter } from 'react-router-dom';

import { GoBackProps } from 'merchant/views/Transactions/v2/common/components/GoBack/types';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';

const { PAYMENTS } = TransactionsEntityRoute;

const GoBack = ({
  onClickCb,
  history,
  location: { pathname, state: { prevPath } = {} },
}: GoBackProps) => {
  const goBack = () => {
    const { init_page } = qs.parse(location.search);
    const section = TransactionsPagesMap[pathname] || init_page;
    track({
      objectName: 'Go Back Button',
      properties: { section },
    });

    if (!prevPath) {
      history.push(PAYMENTS);
    } else {
      history.goBack();
    }
    onClickCb?.();
  };

  return (
    <Box marginBottom="spacing.4">
      <Link icon={ChevronLeftIcon} iconPosition="left" variant="button" onClick={goBack}>
        Go Back
      </Link>
    </Box>
  );
};

export default withRouter(GoBack);
