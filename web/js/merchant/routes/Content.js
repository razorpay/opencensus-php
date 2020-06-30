import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';

import { matchDetail, matchModal, supportHashMapping } from 'merchant/routes';
import Slider from 'common/ui/Slider';
import { ModalMask } from 'common/new-ui/Modal';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import Home from 'merchant/containers/Home/Index';
import PartnerDashboard from 'merchant/views/PartnerDashboard';
import Transactions from 'merchant/views/Transactions';
import Settlements from 'merchant/views/Settlements/List';
import PaymentLinks from 'merchant/views/PaymentLinks/Index';
import PaymentPages from 'merchant/views/PaymentPages';
import PaymentPagesDetails from 'merchant/views/PaymentPages/PaymentPages/Details';
import InvoicesContainer from 'merchant/views/Invoices';
import InvoicesNew from 'merchant/views/Invoices/Invoices/New';
import Subscriptions from 'merchant/views/Subscriptions';
import Customers from 'merchant/views/Customers/List';
import Marketplace from 'merchant/views/Marketplace/Index';
import PaymentButton from 'merchant/views/PaymentButton';
import PaymentButtonsDetails from 'merchant/views/PaymentButton/PaymentButton/Details';

import Reports from 'merchant/views/Reports';
import ReportsAsync from 'merchant/views/ReportsAsync/Home';

import MyAccount from 'merchant/views/Account';
import Settings from 'merchant/views/Settings';
import SmartCollect from 'merchant/views/SmartCollect/Index';
import Support from 'merchant/components/Support';
import Offers from 'merchant/views/Offers';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import PaypalOnboardRedirect from 'merchant/views/Settings/Configuration/PaypalOnboardRedirect';
import LoanDetails from 'merchant/views/Capital/index';
import qs from 'query-string';

import {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
} from 'merchant/reducers/app';
import { openSlider } from 'merchant_common/reducers/slider';

import store from 'merchant/store';

import HandleIndex from './HandleIndex';

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

      let query = qs.parse(location.search);
      if (query.basePath) {
        let _location = {
          ...location,
          pathname: query.basePath,
        };
        this.baseLocation = _location;
        setBaseLocation(_location);
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

    // For handling where url is encoded, so hash becomes part of pathname instead of hash (In gmail redirection).
    const urlWithHash = decodeURIComponent(location.pathname);
    const hashInUrl = urlWithHash.substring(urlWithHash.indexOf('#') + 1);

    const hash = location.hash || '#' + hashInUrl;
    if (window.rzpTicketSystem) {
      const actionHash = supportHashMapping[hash];
      if (actionHash && !!location.pathname && location.pathname !== '/') {
        window.rzpTicketSystem.addEventListener('modal-close', onModalClose);
        window.rzpTicketSystem.openModal(actionHash, {
          chat: false,
          call: false,
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

          <ShowWhenRoute
            path="/partners"
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
            component={InvoicesContainer}
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
            component={InvoicesContainer}
            additionalCondition={user => user.isAllowedView('invoices')}
          />

          <ShowWhenRoute
            path="/paymentlinks"
            component={PaymentLinks}
            additionalCondition={user => user.isAllowedView('payment_links')}
          />

          <ShowWhenRoute
            path="/paymentpages/:id(pl_.+)/:entity_name(payments)"
            component={PaymentPagesDetails}
            additionalCondition={user => user.isAllowedView('payment_pages')}
          />

          <ShowWhenRoute
            path="/paymentpages"
            component={PaymentPages}
            additionalCondition={user => user.isAllowedView('payment_pages')}
          />

          <ShowWhenRoute
            path="/paymentbuttons/:id(pl_.+)/:entity_name(payments)"
            component={PaymentButtonsDetails}
            additionalCondition={user =>
              user.isAllowedView('payment_buttons') &&
              user.isPaymentButtonEnabledByRazorX
            }
          />

          <ShowWhenRoute
            path="/paymentbuttons"
            component={PaymentButton}
            additionalCondition={user =>
              user.isAllowedView('payment_buttons') &&
              user.isPaymentButtonEnabledByRazorX
            }
          />

          <ShowWhenRoute
            path="/subscriptions"
            component={Subscriptions}
            additionalCondition={user => user.isAllowedView('subscriptions')}
          />
          <ShowWhenRoute
            path="/plans"
            component={Subscriptions}
            additionalCondition={user =>
              user.isAllowedView('subscriptions') && !user.isChargeAtWillEnabled
            }
          />

          <ShowWhenRoute
            path="/recurring_payments"
            component={Subscriptions}
            additionalCondition={user =>
              user.isAllowedView('subscriptions') &&
              user.isChargeAtWillEnabled &&
              user.isRegistrationLinkTokenAndPaymentsEnabled
            }
          />

          <ShowWhenRoute
            path="/tokens"
            component={Subscriptions}
            additionalCondition={user =>
              user.isAllowedView('subscriptions') &&
              user.isChargeAtWillEnabled &&
              user.isRegistrationLinkTokenAndPaymentsEnabled
            }
          />
          <ShowWhenRoute
            path="/registration_links"
            component={Subscriptions}
            additionalCondition={user =>
              user.isAllowedView('subscriptions') && user.isChargeAtWillEnabled
            }
          />

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

          <ShowWhenRoute
            path="/route"
            component={Marketplace}
            additionalCondition={user => user.isAllowedView('marketplace')}
          />

          <ShowWhenRoute
            path={['/smartcollect', '/virtualaccounts']}
            component={SmartCollect}
            additionalCondition={user => user.isAllowedView('virtual_accounts')}
          />

          <ShowWhenRoute
            path="/reports"
            component={user.isAsyncReportsEnabled ? ReportsAsync : Reports}
            additionalCondition={user => user.isAllowedView('reports')}
          />

          <ShowWhenRoute
            path="/reports-async"
            component={ReportsAsync}
            additionalCondition={user => user.isAllowedView('reports')}
          />

          <ShowWhenRoute
            path="/profile"
            component={MyAccount}
            additionalCondition={user =>
              user.isAllowedView('profile') || !user.userRole
            }
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
          <ShowWhenRoute path="/reminders" component={Settings} />
          <ShowWhenRoute
            path="/applications"
            component={Settings}
            additionalCondition={user => user.isAllowedView('applications')}
          />
          <ShowWhenRoute
            path="/offers"
            component={Offers}
            additionalCondition={user => user.isAllowedView('offers')}
          />
          <ShowWhenRoute
            path="/paypal_onboard_redirect"
            component={PaypalOnboardRedirect}
          />
          <ShowWhenRoute path="/capital/loans" component={LoanDetails} />
          <Route exact path="/" component={HandleIndex} />
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
    const { user } = this.props;

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
        <Support user={user} />
      </main>
    );
  }
}
