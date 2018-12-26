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
    this.props.onClose();
    this.hideProductsModal(
      () => (
        this.onCloseProductsModal && this.onCloseProductsModal(),
        (this.onCloseProductsModal = null)
      )
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
      <div class="accept-payments-modal">
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
