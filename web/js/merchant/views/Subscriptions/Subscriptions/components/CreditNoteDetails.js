import React from 'react';
import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import DataTable from 'common/ui/Table/DataTable';
import { refundId, amount, createdAt } from 'common/ui/item/pair';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default class CreditNoteDetails extends React.Component {
  render() {
    const { isLoading, creditNote, statusMsg, onClose } = this.props;

    const refunds = [];

    (creditNote.invoices || []).forEach((invoice) => {
      invoice.refunds.forEach((refund) => refunds.push(refund));
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
                <button type="button" class="close close-secondary" onClick={onClose}>
                  <i class="i i-arrow-back" />
                  <i class="i i-close" />
                </button>
              )}
              Credit Note ID: <strong>{creditNote.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />

                <EntityDetailRow label="Name">{creditNote.name}</EntityDetailRow>

                <EntityDetailRow label="Description">{creditNote.description}</EntityDetailRow>

                <EntityDetailRow label="Amount">
                  <Amount currency={creditNote.currency} value={creditNote.amount} />
                </EntityDetailRow>

                {!!creditNote.invoices?.length && <RefundsList refunds={refunds} />}
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

const createdAtWithStyle = { columnClass: 'text-right', ...createdAt };

const RefundsList = ({ refunds }) => {
  const refundsHeading = {
    title: 'Refund Details',
    subTitle: <NumRefunds refunds={refunds} titleCase={true} />,
  };

  return (
    <div class="full-width-item sub-entity-list">
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

const NumRefunds = ({ refunds, titleCase = false }) => {
  const refundItems = refunds || [];

  const numRefunds = refundItems.length;
  const refundSuffix = numRefunds === 0 || numRefunds > 1 ? 's' : '';

  return (
    <span>
      {numRefunds} {titleCase ? 'R' : 'r'}efund{refundSuffix}
    </span>
  );
};
