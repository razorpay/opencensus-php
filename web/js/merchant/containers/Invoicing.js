import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, NavLink, withRouter } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import Invoices from 'merchant/containers/Invoices/List';
import Customers from 'merchant/containers/Customers/List';
import Items from 'merchant/containers/Items/List';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import OnBoarding, {
  getIsInvoicesEnabled,
  getIsAllowedResetInvoicesOnBoarding,
} from './Invoices/OnBoarding';

import QuickGuide, {
  getInvoicesQuickGuideIsClosed,
} from './Invoices/QuickGuide';

@withRouter
@connect(
  state => ({
    ...state.session,
    invoices: state.invoices,
    invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.INVOICE
    ),
  }),
  {
    handleProductQuickGuide,
  }
)
export default class InvoicingContainer extends Component {
  componentDidMount() {
    this.initPaymentLinksOnboarding();
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.invoices.loading != this.props.invoices.loading) {
      this.initPaymentLinksOnboarding(nextProps);
    }
  }

  componentWillUnmount() {
    const { invoicesProductOnBoarding } = this.props;

    if (invoicesProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...invoicesProductOnBoarding,
        isTour: false,
      });
    }
  }

  initPaymentLinksOnboarding = (props = this.props) => {
    const data = {
      user: props.user,
      merchantId: props.user.current,
      invoices: props.invoices,
    };

    const isPaymentLinksEnabled = getIsInvoicesEnabled(data);

    let showOnboarding = !isPaymentLinksEnabled;

    if (isPaymentLinksEnabled) {
      showOnboarding = getIsAllowedResetInvoicesOnBoarding(data);
    }

    let isQuickGuideClosed = getInvoicesQuickGuideIsClosed(props);

    let invoicesProductOnBoarding = {
      ...props.invoicesProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: props.invoicesProductOnBoarding.isTour
        ? true
        : !isQuickGuideClosed,
    };

    this.props.handleProductQuickGuide(invoicesProductOnBoarding);
  };

  render() {
    const {
      isQuickGuideOpen,
      showOnboarding,
    } = this.props.invoicesProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }

    return (
      <tabbed-container>
        {isQuickGuideOpen && <QuickGuide />}

        <header id="invoicing-header">
          <NavLink to="/invoices">Invoices</NavLink>
          <NavLink to="/items">Items</NavLink>
        </header>

        <TestModeBanner />

        <content>
          <Route path="/invoices" component={Invoices} />
          <Route path="/items" component={Items} />
          <Route path="/customers" component={Customers} />
        </content>
      </tabbed-container>
    );
  }
}
