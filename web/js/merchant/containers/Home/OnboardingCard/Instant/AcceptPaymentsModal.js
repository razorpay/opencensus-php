/* eslint-disable */
import { Component } from 'react';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import TransactionsModal from 'merchant/components/Home/TransactionsHelperModal';
import { showProductsModal, hideProductsModal } from 'merchant/reducers/home';

import { trackTransactionsHelper, trackProductsModal } from './ga';
import { compose } from 'redux';

const showProductsModalOnLoad = window.location.href.indexOf('products') > 0;

class AcceptPayments extends Component {
  constructor(props) {
    super(props);
    this.state = {
      showProducts: showProductsModalOnLoad,
      showTransactionsHelper: this.props.shouldShow,
    };

    this.onCloseProductsModal = null;
    this.showProductsModal = this.showProductsModal.bind(this);
    this.showTransactionsModal = this.showTransactionsModal.bind(this);
    this.hideTransactionsModal = this.hideTransactionsModal.bind(this);
    this.handleProductsModalBack = this.handleProductsModalBack.bind(this);

    const hideProductsModal = (this.hideProductsModal = this.hideProductsModal.bind(this));

    if (showProductsModalOnLoad) {
      this.hideProductsModal = () => {
        hideProductsModal(() => {
          this.hideProductsModal = hideProductsModal;
        });
      };
    }
  }

  UNSAFE_componentWillReceiveProps({ shouldShow, showProducts }) {
    if (shouldShow !== this.state.showTransactionsHelper) {
      this.setState({ showTransactionsHelper: shouldShow });
    }

    if (showProducts !== this.state.showProducts) {
      this.setState({ showProducts });
    }
  }

  showProductsModal(onCloseCb) {
    this.props.showProductsModal();
    if (typeof onCloseCb === 'function') {
      this.onCloseProductsModal = onCloseCb;
    }

    this.setState({
      showProducts: true,
    });
  }

  hideProductsModal(onHide) {
    trackProductsModal.trackClose();
    this.props.hideProductsModal();
    this.setState(
      {
        showProducts: false,
      },
      typeof onHide === 'function' ? onHide : void 0,
    );
  }

  handleProductsModalBack() {
    trackProductsModal.trackProductsBack();
    this.props.onClose();
    this.hideProductsModal(
      () => (
        this.onCloseProductsModal && this.onCloseProductsModal(), (this.onCloseProductsModal = null)
      ),
    );
  }

  showTransactionsModal() {
    this.setState({
      showTransactionsHelper: true,
    });
  }

  hideTransactionsModal() {
    trackTransactionsHelper.trackClose();
    this.props.onClose();

    this.setState({
      showTransactionsHelper: false,
    });
  }

  render() {
    const { showProducts, showTransactionsHelper } = this.state;
    const { isKLA } = this.props;

    return (
      <div className="accept-payments-modal">
        {showProducts && (
          <ProductsModal
            onClose={this.hideProductsModal}
            onBack={this.handleProductsModalBack}
            track={trackProductsModal}
          />
        )}
        {showTransactionsHelper && (
          <TransactionsModal
            onClose={this.hideTransactionsModal}
            isKLA={isKLA}
            showProductsModal={this.showProductsModal}
            showTransactionsModal={this.showTransactionsModal}
            track={trackTransactionsHelper}
          />
        )}
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      showProducts: state.home.instantActivations.showProductsModal,
    }),
    { showProductsModal, hideProductsModal },
  ),
  withRouter,
)(AcceptPayments);
