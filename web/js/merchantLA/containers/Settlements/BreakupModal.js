import { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import SettlementBreakupTable from 'merchantLA/components/Settlements/BreakupTable';
import { fetchBreakupDetails } from 'merchantLA/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { compose } from 'redux';

class BreakdownModal extends Component {
  UNSAFE_componentWillMount() {
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
        <ModalHeader title={`Breakup for #${settlementId}`} onCloseClick={this.props.closeModal} />

        <div className="modal-body">
          {error && <Alert type="error" message={error} />}

          <SettlementBreakupTable items={items} loading={loading} />

          <div className="Modal__actions text-right">
            <button className="btn btn-default" onClick={this.props.closeModal}>
              Close
            </button>
          </div>
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => state.settlement.breakupDetails, {
    fetchBreakupDetails,
    ...ModalActions,
  }),
)(BreakdownModal);
