import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';

import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import Tooltip from 'common/ui/Tooltip';
import { isPresent } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { CommissionInvoiceStatusLabel } from 'merchant/components/StatusLabel';
import { fetchCommissionInvoiceDetails } from 'merchant/reducers/commissionInvoices/details';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';

import ProcessInvoice from './ProcessInvoice';

class InvoiceDetails extends Component {
  componentDidMount() {
    const { id, fetchCommissionInvoiceDetails } = this.props;
    fetchCommissionInvoiceDetails(id);
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
      .then((res) => {
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
    const { commissionInvoice, error, user } = this.props;
    const currency = user.merchant.currency;
    const isOrgRZP = user.isOrgRZP;

    if (error) {
      return (
        <Alert
          type="error"
          message={isPresent(error[0]) ? error[0] : 'Something went wrong.'}
          showDismiss={false}
        />
      );
    }

    const commissionRange = moment(`${commissionInvoice.year}-${commissionInvoice.month}-01`);
    const commissionFrom = commissionRange.startOf('month').format('DD MMM YYYY');
    const commissionTo = commissionRange.endOf('month').format('DD MMM YYYY');

    return (
      <div className="panel-body" data-testid="invoice-details-panel">
        <div className="list-group pair-row-container">
          <EntityDetailRow
            label="Amount"
            value={() => (
              <div>
                <Amount
                  value={commissionInvoice.gross_amount}
                  testId="amount-invoice-details"
                  currency={currency}
                />
              </div>
            )}
          />
          <EntityDetailRow
            label="Status"
            value={() => (
              <div>
                <CommissionInvoiceStatusLabel status={commissionInvoice.status} />
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
            {renderAmountBreakup(commissionInvoice, currency, isOrgRZP)}
          </EntityDetailRow>
        </div>
      </div>
    );
  };

  render() {
    const { loading, commissionInvoice } = this.props;

    if (loading) {
      return (
        <div className="content-wrapper content-sm txn-details">
          <div className="page-spinner-container">
            <Spinner />
          </div>
        </div>
      );
    }

    return (
      <div className="content-wrapper content-sm txn-details">
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            Invoice Id: <strong>{this.props.id}</strong>
            {isPresent(commissionInvoice) && (
              <div className="btn-toolbar pull-right">
                <button className="btn Button--primary--invert" onClick={this.downloadInvoice}>
                  <i className="i i-download" />
                  <Tooltip theme="dark">Download Invoice</Tooltip>
                </button>
              </div>
            )}
          </div>

          <div className="SliderPanel__Body">
            {commissionInvoice.status === 'issued' && (
              <div className="alert alert-warning rzp-banner commission-payment-wrapper">
                <span className="rzp-banner-text">
                  Commission payment will be initiated once the invoice is processed.
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

function renderAmountBreakup(commissionInvoice, currency, isOrgRZP) {
  const lineItem = commissionInvoice.line_items[0];
  return (
    <Definition>
      <Amount value={commissionInvoice.gross_amount} currency={currency} />
      <div>
        Gross Amount - <Amount value={lineItem.taxable_amount} currency={currency} />
      </div>
      {lineItem.taxes.map((tax) => (
        <div key={tax.name}>
          {tax.name} - <Amount value={tax.tax_amount} currency={currency} />
        </div>
      ))}
      <div>
        Total {isOrgRZP ? 'GST' : 'Tax'} -{' '}
        <Amount value={lineItem.tax_amount} currency={currency} />
      </div>
    </Definition>
  );
}

export default connect(
  (state) => ({
    ...state.commissionInvoice,
    user: state.session.user,
  }),
  {
    showNotification,
    fetchCommissionInvoiceDetails,
  },
)(InvoiceDetails);
