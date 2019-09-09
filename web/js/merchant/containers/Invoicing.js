import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, NavLink, withRouter } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';
import { fetchItems } from 'merchant/modules/items';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import Invoices from 'merchant/containers/Invoices/List';
import Customers from 'merchant/containers/Customers/List';
import Items from 'merchant/containers/Items/List';

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
    items: state.items,
    invoicesProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.INVOICE
    ),
  }),
  {
    handleProductQuickGuide,
    fetchItems,
  }
)
export default class InvoicingContainer extends Component {
  componentDidMount() {
    if (this.props.invoices.invoices.length) return;

    if (!this.props.items.items.length) {
      this.props.fetchItems();
    }
  }

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.invoices.loading != this.props.invoices.loading ||
      nextProps.items.loading != this.props.items.loading
    ) {
      this.initInvoicesOnboarding(nextProps);
    }
  }

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

    let invoicesProductOnBoarding = {
      ...props.invoicesProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getInvoicesQuickGuideIsClosed(props),
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
          <Route path="/items" render={ItemsComponent} />
          <Route path="/customers" component={Customers} />
        </content>
      </tabbed-container>
    );
  }
}

const ItemsComponent = props => <Items {...props} isInvoiceView />;
