import React from 'react';
import { Route, Switch, NavLink, Link } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import StoreOnboarding from './Onboarding';
import TestModeBanner from 'merchant/components/TestModeBanner';
import OrdersList from './Orders/List';
import ProductsList from './Products/List';
import ShareModal from './components/Share';
import StoresSettingsModal from './components/StoresSettingsModal';
import Spinner from 'common/ui/Spinner';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchStore } from 'merchant/reducers/storefront';
import track from './Onboarding/track';

@connect(
  (state) => ({
    store: state.storefront,
  }),
  {
    closeModal,
    openModal,
    showNotification,
    fetchStore,
  },
)
@RTracking(() => window.rzpQ.component('StoresContainer'))
export default class StoresContainer extends React.Component {
  state = {
    isSettingsOpen: false,
  };

  toggleShareModal = () => {
    this.props.openModal({
      size: 'medium',
      component: (
        <ShareModal
          closeModal={this.props.closeModal}
          showNotification={this.props.showNotification}
          title={this.props.store.entity.data.title}
          url={this.props.store.entity.data.store_url}
          id={this.props.store.entity.data.id}
        />
      ),
    });
  };

  toggleSettingsModal = () => {
    this.setState((prevState) => ({ isSettingsOpen: !prevState.isSettingsOpen }));
  };

  componentDidMount() {
    this.props.fetchStore();
  }

  componentDidUpdate(prevProps) {
    // update store_id if store created/deleted
    if (this.props.store && this.props.store.entity.data.id !== prevProps.store.entity.data.id) {
      track.init({
        store_id: this.props.store.entity.data.id,
      });
    }
  }

  render() {
    const { isSettingsOpen } = this.state;
    const { store } = this.props;
    let content;

    if (store.entity.loading) {
      content = (
        <div class="loader-container">
          <Spinner />
        </div>
      );
    } else if (store.entity.error && store.entity.error.indexOf('Store does not exists') === -1) {
      content = <div className="error-container">{store.entity.error}</div>;
    } else if (!store.entity.data.id || store.entity.isSuccessModal) {
      content = <StoreOnboarding />;
    } else if (store.entity.data.id) {
      content = (
        <>
          {isSettingsOpen && <StoresSettingsModal onClose={this.toggleSettingsModal} />}
          <tabbed-container>
            <div class="store-header">
              <div>
                <h3>{store.entity.data.title}</h3>
                <span class="tag">LIVE</span>
              </div>
              <div>
                <a
                  class="m-r"
                  target="_blank"
                  href={store.entity.data.store_url}
                  rel="noreferrer noopener"
                  onClick={track.storeLinkClick}
                >
                  <span class="mr-5">
                    stores.razorpay.com/ <strong>{store.entity.data.slug}</strong>
                  </span>
                  <i class="i i-external-link" />
                </a>
                <span class="actions-group">
                  <button class="Button Button--primary--invert" onClick={this.toggleShareModal}>
                    <i class="i i-share-outline mr-5" /> Share
                  </button>
                  <button class="Button Button--primary--invert" onClick={this.toggleSettingsModal}>
                    <i class="i i-settings-outline mr-5" /> Settings
                  </button>
                  <Link
                    class="Button Button--primary"
                    to="/stores/products/new"
                    onClick={track.addProductDashboardBtn}
                  >
                    <span>
                      <i class="i i-plus mr-5" /> Add Product
                    </span>
                  </Link>
                </span>
              </div>
            </div>
            <header id="marketplace-header">
              <NavLink to="/stores/products">Products</NavLink>
              <NavLink to="/stores/orders">Orders</NavLink>
            </header>

            <TestModeBanner />

            <content>
              <Switch>
                <Route path="/stores/products" exact component={ProductsList} />
                <Route path="/stores/orders" exact component={OrdersList} />
              </Switch>
            </content>
          </tabbed-container>
        </>
      );
    }

    return <div className="StorefrontContainer">{content}</div>;
  }
}
