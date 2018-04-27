import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';

import { classList } from 'common/util';
import { matchDetail, matchModal } from 'merchant/routes';
import Slider from 'rzp/ui/Slider';
import Modal, { ModalContent } from 'component/Modal';

import ShowWhen from 'merchant/components/ShowWhen';
import Home from 'merchant/containers/Home/Index';
import HomeNew from 'merchant/containers/Home/New';
import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import PaymentLinks from 'merchant/containers/PaymentLinks/Index';
import InvoicingContainer from 'merchant/containers/Invoicing';
import InvoicesNew from 'merchant/containers/Invoices/New';
import Subscriptions from 'merchant/containers/Subscriptions/Index';
import Customers from 'merchant/containers/Customers/List';
import Marketplace from 'merchant/containers/Marketplace/Index';
import Reports from 'merchant/containers/Reports';
import MyAccount from 'merchant/containers/MyAccount';
import Settings from 'merchant/containers/Settings';
import VirtualAccounts from 'merchant/containers/VirtualAccounts/List';
import ActivationContainer from 'merchant/containers/Activation/new';

// Below will be removed with old navigation removal
import RefundsList from 'merchant/containers/Refunds/List';
import BatchUpload from 'merchant/containers/Refunds/BatchUpload';
import BatchUploads from 'merchant/containers/Refunds/BatchList';

import ErrorBoundary from 'common/ErrorBoundary';

import {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
} from 'merchant/modules/app';
import { openSlider } from 'rzp/modules/slider';

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

// Can be removed with old navigation removal
const RefundsTabbedContainer = () => {
  return (
    <tabbed-container>
      <header id="transactions-header">
        <NavLink to="/refunds" exact>
          Refunds
        </NavLink>
        <ShowWhen
          featureEnabled="Batchrefunds"
          myRole="owner manager operations admin finance"
        >
          <NavLink
            to="/refunds/batchuploads"
            isActive={(match, { pathname }) =>
              pathname === '/refunds/batchupload' ||
              pathname === '/refunds/batchuploads'
            }
          >
            Batch Refunds
          </NavLink>
        </ShowWhen>
      </header>
      <content>
        <Switch>
          <Route path="/refunds/batchupload" component={BatchUpload} />
          <Route path="/refunds/batchuploads" component={BatchUploads} />
          <Route path="/refunds" component={RefundsList} />
        </Switch>
      </content>
    </tabbed-container>
  );
};

@withRouter
@connect(null, {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
  openSlider,
})
export default class Content extends Component {
  setBaseLocation = location => {
    let { setBaseLocation, setActiveEntity, setSecActiveEntity } = this.props;
    var matchDetailsRoute = matchDetail(location.pathname);
    var matchModalsRoute = matchModal(location.pathname);

    if (matchDetailsRoute || matchModalsRoute) {
      let resultRoute;

      if (matchModalsRoute.match) {
        resultRoute = matchModalsRoute;

        this.activationView = matchModalsRoute.component;
        this.detailView = null;
      } else if (matchDetailsRoute.match) {
        resultRoute = matchDetailsRoute;

        this.activationView = null;
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
      this.activationView = null;
      this.detailProps = null;
      setActiveEntity(null);
      setSecActiveEntity(null);

      this.baseLocation = location;
      setBaseLocation(location);
    }
  };

  getBaseView = () => {
    return (
      <ErrorBoundary resetOnProps location={this.baseLocation}>
        <Switch location={this.baseLocation}>
          <Route path="/dashboard" component={Home} />
          <Redirect from="/" exact to="/dashboard" />

          <Route path="/dashboard_v2" component={HomeNew} />
          <Route path="/payments" component={Transactions} />
          <Route path="/refunds" component={Transactions} />
          <Route path="/orders" component={Transactions} />
          <Route path="/disputes" component={Transactions} />

          <Route path="/settlements" component={Settlements} />

          <Route path="/invoices" exact component={InvoicingContainer} />
          <Route path="/invoices/:id(inv_.+)" component={InvoicesNew} />
          <Route path="/invoices/new" component={InvoicesNew} />
          <Route path="/items" component={InvoicingContainer} />
          <Route path="/paymentlinks" component={PaymentLinks} />
          <Route path="/subscriptions" component={Subscriptions} />
          <Route path="/plans" component={Subscriptions} />
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

          <Route path="/reports" component={Reports} />

          <Route path="/profile" component={MyAccount} />
          <Route path="/activation" component={ActivationContainer} />
          <Route path="/addfunds" component={MyAccount} />
          <Route path="/credits" component={MyAccount} />
          <Route path="/referrals" component={MyAccount} />
          <Route path="/team" component={MyAccount} />

          <Route path="/config" component={Settings} />
          <Route path="/keys" component={Settings} />
          <Route path="/webhooks" component={Settings} />
          <Route path="/applications" component={Settings} />

          <Redirect to="/dashboard" />
        </Switch>
      </ErrorBoundary>
    );
  };

  componentWillMount() {
    this.setBaseLocation(this.props.location);
  }

  componentWillReceiveProps(props) {
    this.setBaseLocation(props.location);
    this.showSliderView();
  }

  showSliderView() {
    if (this.detailView && this.baseLocation) {
      this.props.openSlider();
    }
  }

  closeModalView = e => {
    this.props.history.replace(this.baseLocation.pathname);
  };

  render() {
    var DetailView = this.detailView;
    var BaseView = this.baseLocation ? this.getBaseView() : null;

    let ActivationFormView = this.activationView;

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
    } else if (ActivationFormView) {
      ActivationFormView = BaseView ? (
        <Modal
          maskClosable={true}
          onClose={this.closeModalView}
          class={classList(
            'animate-down',
            ActivationFormView.MODAL_CONTAINER_CLASS
          )}
        >
          <ActivationFormView
            {...this.detailProps}
            closeUrl={BaseView ? this.baseLocation.pathname : undefined}
          />
        </Modal>
      ) : (
        <ActivationFormView {...this.detailProps} />
      );
    }

    return (
      <main class="main-content">
        {BaseView}
        {DetailView}
        {ActivationFormView}
      </main>
    );
  }
}
