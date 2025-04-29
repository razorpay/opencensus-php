import React, { lazy } from 'react';
import { useLocation, Navigate, matchPath } from 'react-router-dom';
import { isPaymentsPath } from '@libs/shared-utils';
import { PRODUCT_PATH_MAP, PRODUCT_ALIAS_MAP } from '../Navigation/constants';
import withNavigationType from '../Navigation/withNavigationType';
import { useStore } from '@federated/apps/shell/commonStore';

const PaymentsDashboard = lazy(() => import('@federated/dashboards/payments/entry'));
const WrappedPaymentsDashboard = withNavigationType(PaymentsDashboard);

const HandleIndexAndPaymentsRoute = () => {
  const location = useLocation();
  const currentPath = location.pathname;
  const partnerType = useStore((state) => state?.session?.user?.partner_type);
  const isPartner = Boolean(partnerType);

  const isOneHomeEnabled = Boolean(window?.IS_ONE_HOME_ENABLED);

  if (currentPath === '/') {
    if (isOneHomeEnabled) {
      return <Navigate to="/home" replace />;
    } else if (isPartner) {
      return <Navigate to="/partners" replace />;
    } else {
      return <Navigate to="/dashboard" replace />;
    }
  }

  return <WrappedPaymentsDashboard />;
};

export default HandleIndexAndPaymentsRoute;
