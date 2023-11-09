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
import { bindActionCreators } from 'redux';

const calculateSettledAmount = (items, isNew) => {
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

class BreakdownModal extends Component {
  UNSAFE_componentWillMount() {
    this.props
      .fetchBreakupDetails({
        id: this.props.settlementId,
      })
      .then(() => {
        const properties = this.props.settlement
          ? { ...this.props.settlement.analyticsPayload(), status: 'success' }
          : {};
        if (this.props.settlement) handleAnalytics('break up', 'status', properties);
      })
      .catch((e) => {
        const properties = this.props.settlement
          ? { status: 'failure', failureReason: e.errors[0] }
          : {};
        if (this.props.settlement) handleAnalytics('break up', 'status', properties);
      });
  }

  componentDidMount() {
    if (this.props.onMount) this.props.onMount(this.props.settlementId);
  }

  componentWillUnmount() {
    if (this.props.onUnmount) this.props.onUnmount(this.props.settlementId);
  }

  render() {
    const { settlementId, breakupDetails, settlementCurrency } = this.props;
    const { loading, error, isBreakupNew, items } = breakupDetails;

    // If items not yet ready, showing spinner
    if (loading) {
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
            columnNames={items.length ? Object.keys(items[0]) : []}
            isNew={isBreakupNew}
            currency={settlementCurrency}
          />

          <div class="Modal__actions text-right settlement-amount-row">
            <span class="settled-amount">
              Total Settled Amount:{' '}
              <Amount
                value={calculateSettledAmount(items, isBreakupNew)}
                currency={settlementCurrency}
              />
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

const mapStateToProps = (state) => {
  return {
    breakupDetails: state.settlement.breakupDetails,
    user: state.session.user,
    settlementCurrency: state?.home?.settlement_amount.data?.settlement_currency,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchBreakupDetails,
      ...ModalActions,
    },
    dispatch,
  );
};

export { calculateSettledAmount };

export default connect(mapStateToProps, mapDispatchToProps)(BreakdownModal);
