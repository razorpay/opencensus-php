import { Component } from 'react';
import { Route } from 'react-router-dom';
import Modal from 'react-modal';
import PaymentDetails from 'merchant/containers/Payments/Details';
import RefundDetails from 'merchant/containers/Refunds/Details';
import OrderDetails from 'merchant/containers/Orders/Details';

const TRANSACTIONS_DETAIL_COMPONENTS = {
  payments: PaymentDetails,
  refunds: RefundDetails,
  orders: OrderDetails,
};

export default class TransactionSlider extends Component {
  state = {
    isOpen: true,
  };

  close = () => {
    let entity = this.props.match.params.entity;
    this.setState({ isOpen: false });
    location.hash = `/app/${entity}`;
  };

  render() {
    let { match } = this.props;
    let { entity, id } = match.params;
    let DetailsComponent = TRANSACTIONS_DETAIL_COMPONENTS[entity];

    return (
      <Modal
        isOpen={this.state.isOpen}
        closeTimeoutMS={300}
        overlayClassName="ModalSlider__Overlay"
        class="ModalSlider__Content"
      >
        <button class="btn btn-sm btn-default pull-right" onClick={this.close}>
          Close
        </button>

        <DetailsComponent id={id} />
      </Modal>
    );
  }
}
