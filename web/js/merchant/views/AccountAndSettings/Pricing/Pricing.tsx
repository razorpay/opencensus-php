import React, { Suspense } from 'react';
import { NavLink, Switch } from 'react-router-dom';
import Breadcrumb from 'common/components/Breadcrumb';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import { StyledDivider } from 'merchant/views/AccountAndSettings/styled';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Loader from 'common/components/Loader';
import lazy from 'merchant/routes/LazyLoader';
import DashboardBanner from 'common/ui/DashboardBanner';
import { Badge, OffersIcon } from '@razorpay/blade/components';
import { StyledHeader } from 'merchant/views/AccountAndSettings/Pricing/Pricing.styles';

const PricingPlans = lazy(
  () =>
    import(
      /* webpackChunkName: "PricingPlans" */ 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans'
    ),
);

const Pricing = ({ location }): JSX.Element => {
  return (
    <ErrorBoundary resetOnProps>
      <div className="tabbed-container">
        <Breadcrumb
          items={[
            accountAndSettingsLink,
            {
              label: ROUTE_MAP[location.pathname],
              link: location.pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <NavLink className="flex-link" to={ROUTES_INFO.PRICING_PLANS} data-testid="flex-link">
            Pricing Plans
            <Badge contrast="low" variant="positive" size="medium" icon={OffersIcon}>
              NEW
            </Badge>
          </NavLink>
        </StyledHeader>
        <Suspense fallback={<Loader />}>
          <StyledDivider>
            <main>
              <Switch>
                <ShowWhenRoute path={ROUTES_INFO.PRICING_PLANS} component={PricingPlans} />
              </Switch>
            </main>
          </StyledDivider>
        </Suspense>
      </div>
      <DashboardBanner />
    </ErrorBoundary>
  );
};

export default Pricing;
