import React, { Component } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { matchDetail, matchModal } from 'merchantLA/routes';
import Slider from 'common/ui/Slider';
import { ModalMask } from 'common/new-ui/Modal';
import ReversalsTable from 'merchantLA/containers/Marketplace/Reversals/ReversalsTable';
import Credit from 'merchantLA/containers/Marketplace/Reversals/Credit';
import BatchUploadList from 'merchantLA/containers/Marketplace/Reversals/BatchUpload/List';
import Transfers from 'merchantLA/containers/Marketplace/Transfers/List';
import Reversals from 'merchantLA/containers/Marketplace/Reversals/List';
import Settlements from 'merchantLA/containers/Settlements/List';
import MyAccount from 'merchantLA/containers/MyAccount';
import Profile from 'merchantLA/containers/MyAccount/Profile';
import TeamManagement from 'merchantLA/containers/MyAccount/Team';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { RouteGuard } from './ShowWhen';

import { setBaseLocation, setActiveEntity, setSecActiveEntity } from 'merchantLA/reducers/app';
import { openSlider } from 'merchant_common/reducers/slider';
import LinkedAccountReports from 'merchant_common/views/Reports/views/LinkedAccountReports';

@connect(null, {
  setBaseLocation,
  setActiveEntity,
  setSecActiveEntity,
  openSlider,
})
class Content extends Component {
  setBaseLocation = (location) => {
    const { setBaseLocation, setActiveEntity, setSecActiveEntity } = this.props;
    const matchDetailsRoute = matchDetail(location.pathname);
    const matchModalsRoute = matchModal(location.pathname);

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
        <Routes location={this.baseLocation}>
          <Route
            path="transfers/*"
            element={
              <RouteGuard>
                <Transfers />
              </RouteGuard>
            }
          />
          <Route
            path="reversals/*"
            element={
              <RouteGuard>
                <Reversals />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard>
                  <ReversalsTable />
                </RouteGuard>
              }
            />
            <Route
              path="batchreversals/*"
              element={
                <RouteGuard additionalCondition={(user) => user.isAllowedLARefunds}>
                  <BatchUploadList />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="credits/*"
            element={
              <RouteGuard>
                <Reversals />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(user) => {
                    const merchant = user.merchants[user.current] || {};
                    const isBalanceSource = merchant.refund_source === 'balance';
                    return !isBalanceSource;
                  }}
                >
                  <Credit />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="settlements/*"
            element={
              <RouteGuard>
                <Settlements />
              </RouteGuard>
            }
          />

          <Route
            path="reports/*"
            element={
              <RouteGuard>
                <LinkedAccountReports />
              </RouteGuard>
            }
          />

          <Route
            path="profile/*"
            element={
              <RouteGuard>
                <MyAccount>
                  <Profile />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route
            path="team/*"
            element={
              <RouteGuard defaultPath="/dashboard" myRole="linked_account_owner">
                <MyAccount>
                  {' '}
                  <TeamManagement />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route path="*" element={<Navigate to="transfers" replace />} />
        </Routes>
      </ErrorBoundary>
    );
  };

  UNSAFE_componentWillMount() {
    this.setBaseLocation(this.props.location);
  }

  UNSAFE_componentWillReceiveProps(props) {
    this.setBaseLocation(props.location);
    this.showSliderView();
  }

  showSliderView() {
    if (this.detailView && this.baseLocation) {
      this.props.openSlider();
    }
  }

  closeModalView = () => {
    document.body.classList.remove('noscroll');
    this.props.history.replace(this.baseLocation.pathname);
  };

  render() {
    let DetailView = this.detailView;
    const BaseView = this.baseLocation ? this.getBaseView() : null;

    let ModalFormView = this.modalView;

    if (DetailView) {
      DetailView = BaseView ? (
        <Slider closeUrl={this.baseLocation}>
          <ErrorBoundary resetOnProps location={this.baseLocation}>
            {' '}
            <DetailView {...this.detailProps} closeUrl={this.baseLocation.pathname} />{' '}
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

export default withRouter(Content);
