import React from 'react';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import DataTable from 'rzp/ui/Table/DataTable';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

import ShowWhen from 'merchant/components/ShowWhen';

import { refundId, amount, createdAt } from 'rzp/ui/item/pair';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

export default class CreditNoteDetails extends React.Component {
  render() {
    const { isLoading, creditNote, statusMsg, onClose } = this.props;

    const refunds = [];

    (creditNote.invoices || []).forEach(invoice => {
      invoice.refunds.forEach(refund => refunds.push(refund));
    });

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {onClose && (
                <button
                  type="button"
                  class="close close-secondary"
                  onClick={onClose}
                >
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Credit Note ID: <strong>{creditNote.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />

                <EntityDetailRow label="Name">
                  {creditNote.name}
                </EntityDetailRow>

                <EntityDetailRow label="Description">
                  {creditNote.description}
                </EntityDetailRow>

                <EntityDetailRow label="Amount">
                  <Amount
                    currency={creditNote.currency}
                    value={creditNote.amount}
                  />
                </EntityDetailRow>

                <EntityDetailRow label="Invoice ID">
                  <Link to={`/invoice/${creditNote.id}`}>{creditNote.id}</Link>
                </EntityDetailRow>

                {creditNote.invoices.length && (
                  <RefundsList refunds={refunds} />
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

const RefundsList = ({ refunds }) => {
  const refundsHeading = {
    title: 'Refund Details',
    subTitle: <NumRefunds refunds={refunds} titleCase={true} />,
  };

  return (
    <div className="full-width-item sub-entity-list">
      <DataTable
        title="Refunds"
        customClass="refunds-table"
        columns={[refundId, amount, createdAtWithStyle]}
        items={refunds}
        showHeaders={false}
        noStripe={true}
        panelHeading={refundsHeading}
      />
    </div>
  );
};

const createdAtWithStyle = { columnClass: 'text-right', ...createdAt };

const NumRefunds = ({ refunds, titleCase = false }) => {
  const refundItems = refunds || [];

  const numRefunds = refundItems.length,
    refundSuffix = numRefunds === 0 || numRefunds > 1 ? 's' : '';

  return (
    <span>
      {numRefunds} {titleCase ? 'R' : 'r'}efund{refundSuffix}
    </span>
  );
};
