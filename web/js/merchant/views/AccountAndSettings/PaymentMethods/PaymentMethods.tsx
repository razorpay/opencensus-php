import React, { Suspense } from 'react';
import { Route, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import { accountAndSettingsLink } from 'merchant/views/AccountAndSettings/constants/constants';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { StyledDivider, StyledHeader } from 'merchant/views/AccountAndSettings/styled';
import Loader from 'common/components/Loader';
import lazy from 'merchant/routes/LazyLoader';

const PaymentMethods = lazy(() =>
  import(/* webpackChunkName: "PaymentMethods" */ 'merchant/views/Settings/PaymentMethods'),
);

const PaymentMethodsV2 = (): JSX.Element => {
  return (
    <>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <div className="tabbed-container">
        <Breadcrumb
          items={[
            accountAndSettingsLink,
            {
              label: 'Payment Methods',
              link: ROUTES_INFO.PAYMENT_METHODS,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <NavLink to={ROUTES_INFO.PAYMENT_METHODS}>Payment Methods</NavLink>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <div className="content">
                <Route path={ROUTES_INFO.PAYMENT_METHODS} component={PaymentMethods} />
              </div>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </>
  );
};

export default PaymentMethodsV2;
