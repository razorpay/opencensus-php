import React from 'react';
import { Box, Text, Link, ArrowRightIcon } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const EmptyListContainer = (): React.ReactElement => {
  const navigate = useNavigate();

  const onRequestInternational = () => {
    navigate(ROUTES_INFO.INTERNATIONAL_PAYMENTS);
  };

  return (
    <Box display="flex" flexDirection="column" alignItems="center">
      <Box display="flex" overflow="hidden" height="86px" width="86px" marginBottom="spacing.4">
        <img
          src="https://cdn.razorpay.com/static/assets/international/international-check.png"
          alt="international"
        />
      </Box>
      <Box maxWidth="460px" display="flex" flexDirection="column" alignItems="center">
        <Text weight="semibold" marginBottom="spacing.2" size="large">
          Start collecting international payments
        </Text>
        <Text marginBottom="spacing.7" size="small">
          Enable international payments: cards, bank transfers (ACH, SEPA, CHAPS, SWIFT) and local
          payment methods (Trustly, Giropay, SofortPay)
        </Text>
        <Link
          icon={ArrowRightIcon}
          iconPosition="right"
          variant="button"
          onClick={onRequestInternational}
        >
          Request International enablement
        </Link>
      </Box>
    </Box>
  );
};

export default EmptyListContainer;
