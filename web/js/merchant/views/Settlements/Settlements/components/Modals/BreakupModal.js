import { Component } from 'react';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import Spinner from 'common/ui/Spinner';
import SettlementBreakupTable from 'merchant/views/Settlements/Settlements/components/BreakupTable';
import { fetchBreakupDetails } from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { handleAnalytics } from 'merchant/views/Settlements/Settlements/analytics';
@connect((state) => state.settlement.breakupDetails, {
  fetchBreakupDetails,
  ...ModalActions,
})
export default class BreakdownModal extends Component {
  componentWillMount() {
    this.props
      .fetchBreakupDetails({
        id: this.props.settlementId,
      })
      .then(() => {
        let properties = this.props.settlement
          ? { ...this.props.settlement.analyticsPayload(), status: 'success' }
          : {};
        this.props.settlement && handleAnalytics('break up', 'status', properties);
      })
      .catch((e) => {
        let properties = this.props.settlement
          ? { status: 'failure', failureReason: e.errors[0] }
          : {};
        this.props.settlement && handleAnalytics('break up', 'status', properties);
      });
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount(this.props.settlementId);
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount(this.props.settlementId);
  }

  calculateSettledAmount = (items, isNew) => {
    if (!isNew) {
      // summation of credit - summation of debit
      const creditSum = items.reduce((acc, item) => {
        if (item.type === 'credit') acc = acc + item.amount;
        return acc;
      }, 0);

      const debitSum = items.reduce((acc, item) => {
        if (item.type === 'debit') acc = acc + item.amount;
        return acc;
      }, 0);

      return creditSum - debitSum;
    } else {
      // summation of settled amount per component
      return items.reduce((acc, item) => {
        acc = acc + item.settled_amount;
        return acc;
      }, 0);
    }
  };

  render() {
    const { settlementId, loading, error, isBreakupNew, items } = this.props;

    // If items not yet ready, showing spinner
    if (items.length === 0) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    return (
      <div>
        <ModalHeader title={`Breakup for #${settlementId}`} onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          {error && <Alert type="error" message={error} />}

          <SettlementBreakupTable
            items={items}
            loading={loading}
            columnNames={Object.keys(items[0])}
            isNew={isBreakupNew}
          />

          <div class="Modal__actions text-right settlement-amount-row">
            <span class="settled-amount">
              Total Settled Amount:{' '}
              <Amount value={this.calculateSettledAmount(items, isBreakupNew)} currency="INR" />
            </span>
            <button class="btn btn-default" onClick={this.props.closeModal}>
              Close
            </button>
          </div>
        </div>
      </div>
    );
  }
}
