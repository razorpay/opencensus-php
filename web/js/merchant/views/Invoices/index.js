import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import DashboardBanner from 'common/ui/DashboardBanner';
import { getItem } from 'common/utils/localStorage';
import ZapierLaunchBanner from 'merchant/components/Announcements/ZapierBanner/ZapierBanner';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { RZPFeatures } from 'merchant/helpers/data';
import { fetchItems } from 'merchant/reducers/items';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import lazy from 'merchant/routes/LazyLoader';

import OnBoarding, {
  getIsInvoicesEnabled,
  getIsAllowedResetInvoicesOnBoarding,
} from './OnBoarding';
import QuickGuide, { getInvoicesQuickGuideIsClosed } from './QuickGuide';

const MonetizationChargesBanner = lazy(() =>
  import(
    /* webpackChunkName: 'InvoicesMonetizationChargesBanner' */ 'merchant/components/Announcements/MonetizationCharges'
  ),
);

// eslint-disable-next-line react/no-unsafe

class InvoicesContainer extends Component {
  componentDidMount() {
    if (this.props.invoices.invoices.length) return;

    if (!this.props.items.items.length) {
      this.props.fetchItems({
        count: 25,
        type: 'invoice',
      });
    }
  }

  componentWillUnmount() {
    const { invoicesProductOnBoarding, handleProductQuickGuide } = this.props;
    const { isInvoiceView } = this.state || {
      isInvoiceView: false,
    };

    if (invoicesProductOnBoarding.isTour) {
      handleProductQuickGuide({
        ...invoicesProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: isInvoiceView,
        isTour: isInvoiceView,
        lastItemId: null,
      });
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (
      nextProps.invoices.loading !== this.props.invoices.loading ||
      nextProps.items.loading !== this.props.items.loading
    ) {
      this.initInvoicesOnboarding(nextProps);
    }
  }

  // eslint-disable-next-line consistent-return
  initInvoicesOnboarding = (props = this.props) => {
    if (props.invoicesProductOnBoarding.isTour) {
      return false;
    }

    const data = {
      user: props.user,
      invoices: props.invoices,
      items: props.items,
    };

    const isInvoicesEnabled = getIsInvoicesEnabled(data);

    let showOnboarding = !isInvoicesEnabled;

    if (isInvoicesEnabled) {
      showOnboarding = getIsAllowedResetInvoicesOnBoarding(data);
    }

    const invoicesProductOnBoarding = {
      ...props.invoicesProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getInvoicesQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(invoicesProductOnBoarding);
  };

  render() {
    const { isQuickGuideOpen, showOnboarding } = this.props.invoicesProductOnBoarding;
    const { user, children } = this.props;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <React.Fragment>
        <div className="banner-container">
          <SuspenseWithLoader>
            <MonetizationChargesBanner userId={user?.current} screen="invoices" user={user} />
          </SuspenseWithLoader>
          <DashboardBanner />
          <ShowWhen
            additionalCondition={(currentUser) =>
              currentUser.isPartOfZapierIntegrationExperiment &&
              !getItem(`zapier-integration-banner-${user.current}`)
            }
          >
            <ZapierLaunchBanner
              fromWhere="invoices"
              bannerKey={`zapier-integration-banner-${user.current}`}
            />
          </ShowWhen>
        </div>

        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="invoicing-header">
            <NavLink to="/invoices">Invoices</NavLink>
            <NavLink to="/items">Items</NavLink>
          </header>

          <TestModeBanner />

          <content>{children}</content>
        </tabbed-container>
      </React.Fragment>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      ...state.session,
      user: state.session.user,
      invoices: state.invoices,
      items: state.items,
      invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.INVOICE),
    }),
    {
      handleProductQuickGuide,
      fetchItems,
    },
  ),
  withRouter,
)(InvoicesContainer);
