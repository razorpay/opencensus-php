import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, NavLink, withRouter } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { fetchItems } from 'merchant/reducers/items';

import PayPalForInvoice from 'merchant/components/Announcements/PayPalForInvoice';
import TestModeBanner from 'merchant/components/TestModeBanner';
import Invoices from 'merchant/views/Invoices/Invoices/List';
import Items from 'merchant/views/Invoices/Items/List';
import ShowWhen from 'merchant/components/ShowWhen';
import ZapierLaunchBanner from 'merchant/components/Announcements/ZapierBanner/ZapierBanner';
import { getItem } from 'common/utils/localStorage';

import OnBoarding, {
  getIsInvoicesEnabled,
  getIsAllowedResetInvoicesOnBoarding,
} from './OnBoarding';

import QuickGuide, { getInvoicesQuickGuideIsClosed } from './QuickGuide';

const ItemsComponent = (props) => <Items {...props} isInvoiceView />;

@withRouter
@connect(
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
)
export default class InvoicesContainer extends Component {
  componentDidMount() {
    if (this.props.invoices.invoices.length) return;

    if (!this.props.items.items.length) {
      this.props.fetchItems({
        count: 25,
        type: 'invoice',
      });
    }
  }

  componentWillReceiveProps(nextProps) {
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
    const { user } = this.props;

    if (showOnboarding && !user.isOrgAxis) {
      return <OnBoarding />;
    }

    return (
      <React.Fragment>
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
        <PayPalForInvoice />

        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="invoicing-header">
            <NavLink to="/invoices">Invoices</NavLink>
            <NavLink to="/items">Items</NavLink>
          </header>

          <TestModeBanner />

          <content>
            <Route path="/invoices" component={Invoices} />
            <Route path="/items" render={ItemsComponent} />
          </content>
        </tabbed-container>
      </React.Fragment>
    );
  }
}
