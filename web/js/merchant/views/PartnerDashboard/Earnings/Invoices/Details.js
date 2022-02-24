import React, { Component } from 'react';
import { connect } from 'react-redux';

import { merchantFetch } from 'merchant/utils/ajax';
import { isPresent } from 'common/utils/rzp-utils';
import Tooltip from 'common/ui/Tooltip';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Definition from 'common/ui/Definition';
import Alert from 'common/ui/Forms/Alert';
import { CommissionInvoiceStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ProcessInvoice from './ProcessInvoice';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchCommissionInvoiceDetails } from 'merchant/reducers/commissionInvoices/details';

@connect(
  state => ({
    ...state.commissionInvoice,
  }),
  {
    showNotification,
    fetchCommissionInvoiceDetails,
  }
)
class InvoiceDetails extends Component {
  UNSAFE_componentWillMount() {
    this.props.fetchCommissionInvoiceDetails(this.props.id);
  }

  componentDidUpdate(prevProps) {
    if (this.props.id !== prevProps.id) {
      this.props.fetchCommissionInvoiceDetails(this.props.id);
    }
  }

  downloadInvoice = () => {
    const { commissionInvoice } = this.props;
    const fileId = commissionInvoice.id;
    merchantFetch(`commission_invoice/${fileId}/signed-url`)
      .then(res => {
        if (res && res.data) {
          window.open(res.data.signed_url, '_blank');
        }
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: 'Could not download invoice.',
        });
      });
  };

  renderPanelBody = () => {
    const { commissionInvoice, error } = this.props;

    if (error) {
      return (
        <Alert
          type="error"
          message={isPresent(error[0]) ? error[0] : 'Something went wrong.'}
          showDismiss={false}
        />
      );
    }

    const commissionRange = moment(
      `${commissionInvoice.year}-${commissionInvoice.month}-01`
    );
    const commissionFrom = commissionRange
      .startOf('month')
      .format('DD MMM YYYY');
    const commissionTo = commissionRange.endOf('month').format('DD MMM YYYY');

    return (
      <div class="panel-body">
        <div class="list-group pair-row-container">
          <EntityDetailRow
            label="Amount"
            value={() => (
              <div>
                <Amount value={commissionInvoice.gross_amount} currency="INR" />
              </div>
            )}
          />
          <EntityDetailRow
            label="Status"
            value={() => (
              <div>
                <CommissionInvoiceStatusLabel
                  status={commissionInvoice.status}
                />
              </div>
            )}
          />
          <EntityDetailRow
            label="Invoice Date"
            value={() => (
              <div>
                <Time value={commissionInvoice.created_at} />
              </div>
            )}
          />
          <EntityDetailRow
            label="Commission Period"
            value={() => (
              <div>
                {commissionFrom} to {commissionTo}
              </div>
            )}
          />
          <EntityDetailRow label="Amount Breakup">
            {renderAmountBreakup(commissionInvoice)}
          </EntityDetailRow>
        </div>
      </div>
    );
  };

  render() {
    const { loading, commissionInvoice } = this.props;

    if (loading) {
      return (
        <div class="content-wrapper content-sm txn-details">
          <div class="page-spinner-container">
            <Spinner />
          </div>
        </div>
      );
    }

    return (
      <div class="content-wrapper content-sm txn-details">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            Invoice Id: <strong>{this.props.id}</strong>
            {isPresent(commissionInvoice) && (
              <div class="btn-toolbar pull-right">
                <button
                  class="btn Button--primary--invert"
                  onClick={this.downloadInvoice}
                >
                  <i class="i i-download" />
                  <Tooltip theme="dark">Download Invoice</Tooltip>
                </button>
              </div>
            )}
          </div>

          <div class="SliderPanel__Body">
            {commissionInvoice.status === 'issued' && (
              <div className="alert alert-warning rzp-banner">
                <span className="rzp-banner-text">
                  Commission payment will be initiated once the invoice is
                  processed.
                </span>
                <div className="rzp-banner-cta">
                  <ProcessInvoice
                    className="rzp-banner-cta"
                    commissionInvoice={commissionInvoice}
                  />
                </div>
              </div>
            )}
            {this.renderPanelBody()}
          </div>
        </div>
      </div>
    );
  }
}

function renderAmountBreakup(commissionInvoice) {
  const lineItem = commissionInvoice.line_items[0];
  return (
    <Definition>
      <Amount value={commissionInvoice.gross_amount} currency="INR" />
      <div>
        Gross Amount - <Amount value={lineItem.taxable_amount} currency="INR" />
      </div>
      {lineItem.taxes.map(tax => (
        <div>
          {tax.name} - <Amount value={tax.tax_amount} currency="INR" />
        </div>
      ))}
      <div>
        Total GST - <Amount value={lineItem['tax_amount']} currency="INR" />
      </div>
    </Definition>
  );
}

export default InvoiceDetails;
