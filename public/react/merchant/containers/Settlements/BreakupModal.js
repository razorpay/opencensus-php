import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import SettlementBreakupTable from 'merchant/components/Settlements/BreakupTable';
import { fetchBreakupDetails } from 'merchant/modules/settlements/details';
import * as ModalActions from 'rzp/modules/modals';

@connect(state => state.settlement.breakupDetails, {
  fetchBreakupDetails,
  ...ModalActions,
})
export default class BreakdownModal extends Component {
  componentWillMount() {
    this.props.fetchBreakupDetails({
      id: this.props.settlementId,
    });
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.settlementId);
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.settlementId);
  }

  render() {
    const { settlementId, items, loading, error } = this.props;

    return (
      <div>
        <ModalHeader
          title={`Breakup for #${settlementId}`}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          {error && <Alert type="error" message={error} />}

          <SettlementBreakupTable items={items} loading={loading} />

          <div class="Modal__actions text-right">
            <button class="btn btn-default" onClick={this.props.closeModal}>
              Close
            </button>
          </div>
        </div>
      </div>
    );
  }
}
