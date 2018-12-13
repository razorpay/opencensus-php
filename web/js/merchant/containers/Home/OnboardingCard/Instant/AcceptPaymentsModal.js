import { Component } from 'react';
import { withRouter } from 'react-router';

import ProductsModal from 'merchant/components/ProducsModal';
import TransactionsModal from 'merchant/components/TransactionsHelperModal';
import { trackTransactionsHelper, trackProductsModal } from './ga';

let showProductsModalOnLoad = window.location.href.indexOf('products') > 0;

@withRouter
export default class AcceptPayments extends Component {
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

    const hideProductsModal = (this.hideProductsModal = this.hideProductsModal.bind(
      this
    ));

    if (showProductsModalOnLoad) {
      this.hideProductsModal = () => {
        hideProductsModal(() => {
          this.props.history.replace('/dashboard');
          this.hideProductsModal = hideProductsModal;
        });
      };
    }
  }

  componentWillReceiveProps({ shouldShow }) {
    if (shouldShow !== this.state.showTransactionsHelper) {
      this.setState({ showTransactionsHelper: shouldShow });
    }
  }

  showProductsModal(onCloseCb) {
    if (typeof onCloseCb === 'function') {
      this.onCloseProductsModal = onCloseCb;
    }

    this.setState({
      showProducts: true,
    });
  }

  hideProductsModal(onHide) {
    trackProductsModal.trackClose();

    this.setState(
      {
        showProducts: false,
      },
      typeof onHide === 'function' ? onHide : void 0
    );
  }

  handleProductsModalBack() {
    trackProductsModal.trackProductsBack();

    this.hideProductsModal(
      () => (
        this.onCloseProductsModal && this.onCloseProductsModal(),
        (this.onCloseProductsModal = null)
      )
    );
  }

  showTransactionsModal(isKLA) {
    this.setState({
      showTransactionsHelper: true,
      isKLA,
    });
  }

  hideTransactionsModal() {
    trackTransactionsHelper.trackClose();

    this.setState({
      showTransactionsHelper: false,
      isKLA: false,
    });
  }

  render() {
    const { showProducts, showTransactionsHelper, isKLA } = this.state;

    return (
      <div class="accept-payments-modal">
        {showProducts && (
          <ProductsModal
            onClose={this.hideProductsModal}
            onBack={this.handleProductsModalBack}
            track={trackProductsModal}
          />
        )}
        {/* TODO: pass isKLA prop from parent component */}
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
