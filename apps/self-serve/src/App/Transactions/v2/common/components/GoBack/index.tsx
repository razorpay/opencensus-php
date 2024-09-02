import React from 'react';
import { Link, ChevronLeftIcon, Box } from '@razorpay/blade/components';
import qs from 'query-string';
import { useLocation, useNavigate } from 'react-router-dom';

import { GoBackProps } from 'apps/self-serve/src/App/Transactions/v2/common/components/GoBack/types';
import { TransactionsPagesMap } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

const GoBack = ({ onClickCb }: GoBackProps): JSX.Element => {
  const navigate = useNavigate();
  const { pathname } = useLocation();

  const goBack = (): void => {
    const { init_page } = qs.parse(window.location.search);
    const section = TransactionsPagesMap[pathname] || init_page;
    track({
      objectName: 'Go Back Button',
      properties: { section },
    });

    // If a parent component is passing the callback, it would mean that the
    // parent wants control of the routing, better to let the callback take full control
    // and let it override the default routing
    // otherwise the component does it's default routing
    if (onClickCb) {
      onClickCb();
    } else {
      navigate(-1);
    }
  };

  return (
    <Box marginBottom="spacing.4">
      <Link icon={ChevronLeftIcon} iconPosition="left" variant="button" onClick={goBack}>
        Go Back
      </Link>
    </Box>
  );
};

export default GoBack;
