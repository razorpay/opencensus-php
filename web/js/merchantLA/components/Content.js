import React, { Component } from 'react';
import { NavLink, Switch, Route, withRouter, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';

import { classList } from 'common/util';
import { matchDetail, matchModal } from 'merchantLA/routes';
import Slider from 'rzp/ui/Slider';
import { ModalMask } from 'component/Modal';

import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import Home from 'merchant/containers/Home/Index';
import Transfers from 'merchantLA/containers/Marketplace/Transfers/List';
import Reversals from 'merchantLA/containers/Marketplace/Reversals/List';
import Settlements from 'merchantLA/containers/Settlements/List';
import Reports from 'merchant/containers/Reports';
import MyAccount from 'merchantLA/containers/MyAccount';

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

  getBaseView = () => {
    return (
      <ErrorBoundary resetOnProps location={this.baseLocation}>
        <Switch location={this.baseLocation}>
          <Route path="/dashboard" component={Home} />
          <Redirect from="/" exact to="/dashboard" />

          <Route path="/transfers" component={Transfers} />
          <Route path="/reversals" component={Reversals} />
          <Route path="/settlements" component={Settlements} />

          <Route path="/reports" component={Reports} />

          <Route path="/profile" component={MyAccount} />
          <Route path="/team" component={MyAccount} />

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

const ShowWhenRoute = ({ component: Component, ...rest }) => (
  <Route
    {...rest}
    render={props =>
      showWhenUtil(rest) ? (
        <Component {...rest} />
      ) : (
        <Redirect
          to={{
            pathname: '/dashboard',
            state: { from: rest.location },
          }}
        />
      )
    }
  />
);
