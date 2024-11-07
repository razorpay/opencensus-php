import React from 'react';
import { useNavigate } from 'react-router-dom';
import styled from 'styled-components';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import MagicDashboardAnalytics from 'merchant/views/MagicCheckout/MagicDashboard/Container';
import { Link } from '@razorpay/blade/components';

import { addFPVSupportToPath } from 'merchant/views/MagicCheckout/utils/Configuration';
import { OrderAnalyticsProvider } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';

import { REPORTS_AND_ANALYTICS_ROUTE } from 'merchant/views/MagicCheckout/MagicDashboard/routes';

const LinkWrapper = styled.p`
  margin: auto;
  width: fit-content;
`;

const Wrapper: React.FC = () => {
  const navigate = useNavigate();
  return (
    <SuspenseWithLoader type="full">
      <OrderAnalyticsProvider>
        <MagicDashboardAnalytics />
      </OrderAnalyticsProvider>
      <LinkWrapper>
        <Link onClick={() => navigate(addFPVSupportToPath(REPORTS_AND_ANALYTICS_ROUTE))}>
          View more
        </Link>
      </LinkWrapper>
    </SuspenseWithLoader>
  );
};

export default Wrapper;
