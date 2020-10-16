import { Component } from 'react';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import ModalHeader from 'common/ui/ModalHeader';
import Spinner from 'common/ui/Spinner';
import SettlementBreakupTable from 'merchant/views/Settlements/components/BreakupTable';
import { fetchBreakupDetails } from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';

@connect((state) => state.settlement.breakupDetails, {
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

  calculateSettledAmountPerComponent = (items) => {
    return items.reduce((acc, item) => {
      const component = { ...item };

      if (component.type === 'debit') {
        component.amount = -1 * component.amount;
      }

      let { amount, tax, fee } = component;
      component.settled_amount = amount - tax - fee;

      acc.push(component);

      return acc;
    }, []);
  };

  isResponseNew = (obj) => {
    delete obj.amountInINR;
    delete obj.resourceUrl;
    delete obj.resourceIdField;

    let newResponse = false;

    if ('tax' in obj && 'fee' in obj) newResponse = true;
    else newResponse = false;

    return {
      columnNames: Object.keys(obj),
      newResponse,
    };
  };

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
    const { settlementId, loading, error } = this.props;

    // If items not yet ready, showing spinner
    if (this.props.items.length === 0) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    // check if api response is new or old
    let { newResponse, columnNames } = this.isResponseNew(this.props.items[0]);

    // if api response is new, calculate settled amount per component, otherwise go by old format
    const items = newResponse
      ? this.calculateSettledAmountPerComponent(this.props.items)
      : this.props.items;

    // If new api response, add an extra header - Settled Amount per row
    if (newResponse) columnNames.push('Settled Amount');

    return (
      <div>
        <ModalHeader title={`Breakup for #${settlementId}`} onCloseClick={this.props.closeModal} />

        <div class="modal-body">
          {error && <Alert type="error" message={error} />}

          <SettlementBreakupTable
            items={items}
            loading={loading}
            columnNames={columnNames}
            newResponse={newResponse}
          />

          <div class="Modal__actions text-right settlement-amount-row">
            <span class="settled-amount">
              Total Settled Amount:{' '}
              <Amount value={this.calculateSettledAmount(items, newResponse)} currency="INR" />
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
