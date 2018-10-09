import React, { Component, Fragment } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';

import { classList } from 'common/util';
import { matchDetail, matchModal } from 'merchant/routes';
import Slider from 'rzp/ui/Slider';
import { ModalMask } from 'component/Modal';

import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import Home from 'merchant/containers/Home/Index';
import PartnerDashboard from 'merchant/containers/PartnerDashboard';
import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import PaymentLinks from 'merchant/containers/PaymentLinks/Index';
import PaymentPages from 'merchant/containers/PaymentPages/Index';
import InvoicingContainer from 'merchant/containers/Invoicing';
import InvoicesNew from 'merchant/containers/Invoices/New';
import Subscriptions from 'merchant/containers/Subscriptions/Index';
import Customers from 'merchant/containers/Customers/List';
import Marketplace from 'merchant/containers/Marketplace/Index';
import Reports from 'merchant/containers/Reports';
import MyAccount from 'merchant/containers/MyAccount';
import Settings from 'merchant/containers/Settings';
import VirtualAccounts from 'merchant/containers/VirtualAccounts/List';

import ErrorBoundary from 'common/ErrorBoundary';

import {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
} from 'merchant/modules/app';
import { openSlider } from 'rzp/modules/slider';

import store from 'merchant/store';

// Can be removed with old navigation removal
const TabbedContent = ({ headerId, navLabel, path, to, component }) => {
  return (
    <tabbed-container>
      <header id={headerId}>
        <NavLink to={to}>{navLabel}</NavLink>
      </header>
      <content>
        <Route path={path || to} component={component} />
      </content>
    </tabbed-container>
  );
};

@withRouter
@connect(
  state => ({
    user: state.session.user,
  }),
  {
    setBaseLocation,
    setActiveEntity,
    setSecActiveEntity,
    openSlider,
  }
)
export default class Content extends Component {
  setBaseLocation = location => {
    let { setBaseLocation, setActiveEntity, setSecActiveEntity } = this.props;
    var matchDetailsRoute = matchDetail(location.pathname);
    var matchModalsRoute = matchModal(location.pathname);

    if (matchDetailsRoute || matchModalsRoute) {
      let resultRoute;

      if (matchModalsRoute && matchModalsRoute.match) {
        resultRoute = matchModalsRoute;

        this.modalView = matchModalsRoute.component;
        this.detailView = null;
      } else if (matchDetailsRoute && matchDetailsRoute.match) {
        resultRoute = matchDetailsRoute;

        this.modalView = null;
        this.detailView = matchDetailsRoute.component;
      }

      const params = resultRoute.match.params;
      setActiveEntity(params.id);

      this.detailProps = params;

      setActiveEntity(resultRoute.match.params.id);
      if (Object.keys(params > 1)) {
        setSecActiveEntity(params[Object.keys(params)[1]]);
      }
    } else {
      this.detailView = null;
      this.modalView = null;
      this.detailProps = null;
      setActiveEntity(null);
      setSecActiveEntity(null);

      this.baseLocation = location;
      setBaseLocation(location);
    }
  };

  toggleRasieTicketModal = ({ location = {} }) => {
    const onModalClose = function() {
      this.props.history.push(location.pathname);
      window.rzpTicketSystem.removeEventListener('modal-close', onModalClose);
    }.bind(this); //so that this.props is available inside onModalClose

    const onModalSelect = function(e) {
      const { supportType } = e.detail;

      try {
        switch (supportType) {
          case 'chat':
            window.rzpTicketSystem.closeModal();
            document
              .querySelector('#web-messenger-container')
              .contentDocument.querySelector('#header')
              .click();
        }
      } catch (err) {}
    };

    // For handling where url is encoded, so hash becomes part of pathname instead of hash (In gmail redirection).
    const urlWithHash = decodeURIComponent(location.pathname);
    const hashInUrl = urlWithHash.substring(urlWithHash.indexOf('#') + 1);

    const hash = location.hash || '#' + hashInUrl;
    if (window.rzpTicketSystem) {
      if (
        hash === '#request' &&
        !!location.pathname &&
        location.pathname !== '/'
      ) {
        window.rzpTicketSystem.addEventListener('modal-close', onModalClose);
        window.rzpTicketSystem.addEventListener('modal-select', onModalSelect);
        window.rzpTicketSystem.openModal('#support', {
          chat: Boolean(window.rzp_user && window.rzp_user.activated),
          call: Boolean(window.rzp_user && window.rzp_user.activated),
        });
      } else if (window.rzpTicketSystem.$el.classList.contains('open')) {
        window.rzpTicketSystem.closeModal();
      }
    }
  };

  getBaseView = () => {
    const { user } = this.props;
    return (
      <ErrorBoundary resetOnProps location={this.baseLocation}>
        <Switch location={this.baseLocation}>
          <Route path="/dashboard" component={Home} />
          <Redirect
            to={user.isPartner() ? 'submerchants' : '/dashboard'}
            from="/"
            exact
          />

          <ShowWhenRoute
            path="/submerchants"
            component={PartnerDashboard}
            additionalCondition={user => user.isPartner()}
          />

          <ShowWhenRoute
            path="/payments"
            component={Transactions}
            additionalCondition={user => user.isAllowedView('payments')}
          />
          <ShowWhenRoute
            path="/refunds"
            component={Transactions}
            additionalCondition={user => user.isAllowedView('refunds')}
          />
          <ShowWhenRoute
            path="/orders"
            component={Transactions}
            additionalCondition={user => user.isAllowedView('orders')}
          />
          <Route path="/disputes" component={Transactions} />

          <ShowWhenRoute
            path="/settlements"
            component={Settlements}
            additionalCondition={user => user.isAllowedView('settlements')}
          />

          <ShowWhenRoute
            path="/invoices"
            exact
            component={InvoicingContainer}
            additionalCondition={user => user.isAllowedView('invoices')}
          />
          <ShowWhenRoute
            path="/invoices/:id(inv_.+)"
            component={InvoicesNew}
            additionalCondition={user => user.isAllowedView('invoices')}
          />
          <ShowWhenRoute
            path="/invoices/new"
            component={InvoicesNew}
            additionalCondition={user => user.isAllowedEdit('invoices')}
          />
          <ShowWhenRoute
            path="/items"
            component={InvoicingContainer}
            additionalCondition={user => user.isAllowedView('invoices')}
          />

          <ShowWhenRoute
            path="/paymentlinks"
            component={PaymentLinks}
            additionalCondition={user => user.isAllowedView('payment_links')}
          />

          <ShowWhenRoute
            path="/paymentpages"
            component={PaymentPages}
            featureEnabled="paymentpages"
            additionalCondition={user => user.isAllowedView('payment_pages')}
          />
          <Route path="/subscriptions" component={Subscriptions} />
          <Route path="/plans" component={Subscriptions} />
          <Route path="/authlinks" component={Subscriptions} />
          {/*<Route path="/addons" component={Subscriptions} />*/}
          <Route
            path="/customers"
            render={() => (
              <TabbedContent
                headerId="invoicing-header"
                to="/customers"
                navLabel="Customers"
                component={Customers}
              />
            )}
          />

          <Route path="/route" component={Marketplace} />
          <Route path="/virtualaccounts" component={VirtualAccounts} />

          <ShowWhenRoute
            path="/reports"
            component={Reports}
            additionalCondition={user => user.isAllowedView('reports')}
          />

          <ShowWhenRoute
            path="/profile"
            component={MyAccount}
            additionalCondition={user => user.isAllowedView('profile')}
          />
          <ShowWhenRoute
            path="/addfunds"
            component={MyAccount}
            additionalCondition={user => user.isAllowedView('add_funds')}
          />
          <ShowWhenRoute
            path="/credits"
            component={MyAccount}
            additionalCondition={user => user.isAllowedView('credits')}
          />
          <ShowWhenRoute
            path="/referrals"
            component={MyAccount}
            additionalCondition={user => user.isAllowedView('referrals')}
          />
          <ShowWhenRoute
            path="/team"
            component={MyAccount}
            additionalCondition={user => user.isAllowedView('team')}
          />

          <ShowWhenRoute
            path="/config"
            component={Settings}
            additionalCondition={user => user.isAllowedView('configuration')}
          />
          <ShowWhenRoute
            path="/keys"
            component={Settings}
            additionalCondition={user => user.isAllowedView('api_keys')}
          />
          <ShowWhenRoute
            path="/webhooks"
            component={Settings}
            additionalCondition={user => user.isAllowedView('webhooks')}
          />
          <ShowWhenRoute
            path="/applications"
            component={Settings}
            additionalCondition={user => user.isAllowedView('applications')}
          />

          <Redirect to="/dashboard" />
        </Switch>
      </ErrorBoundary>
    );
  };

  componentWillMount() {
    this.setBaseLocation(this.props.location);
    this.toggleRasieTicketModal(this.props);
  }

  componentWillReceiveProps(nextProps) {
    this.setBaseLocation(nextProps.location);
    this.showSliderView();

    if (nextProps.location.hash !== this.props.location.hash) {
      this.toggleRasieTicketModal(nextProps);
    }
  }

  showSliderView() {
    if (this.detailView && this.baseLocation) {
      this.props.openSlider();
    }
  }

  closeModalView = e => {
    document.body.classList.remove('noscroll');
    this.props.history.replace(this.baseLocation.pathname);
  };

  render() {
    var DetailView = this.detailView;
    var BaseView = this.baseLocation ? this.getBaseView() : null;

    let ModalFormView = this.modalView;

    if (DetailView) {
      DetailView = BaseView ? (
        <Slider closeUrl={this.baseLocation}>
          <ErrorBoundary resetOnProps location={this.baseLocation}>
            {' '}
            <DetailView
              {...this.detailProps}
              closeUrl={this.baseLocation.pathname}
            />{' '}
          </ErrorBoundary>
        </Slider>
      ) : (
        <ErrorBoundary resetOnProps location={this.baseLocation}>
          <DetailView {...this.detailProps} />
        </ErrorBoundary>
      );
    } else if (ModalFormView) {
      ModalFormView = BaseView ? (
        <ModalMask
          maskClosable={false}
          onClose={this.closeModalView}
          class={ModalFormView.MODAL_MASK_CLASS}
        >
          <ModalFormView
            {...this.detailProps}
            onClose={this.closeModalView}
            closeUrl={BaseView ? this.baseLocation.pathname : undefined}
          />
        </ModalMask>
      ) : (
        <ErrorBoundary>
          <ModalFormView {...this.detailProps} />
        </ErrorBoundary>
      );
    }

    return (
      <main class="main-content">
        {BaseView}
        {DetailView}
        {ModalFormView}
      </main>
    );
  }
}
