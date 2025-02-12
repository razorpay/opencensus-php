import React from 'react';
import Clipboard from 'common/ui/Clipboard';
import { titleCase } from 'common/utils/rzp-utils';
import Table from 'common/ui/Table/Index';
import { paymentId, amount, createdAt } from 'common/ui/item/pair';
import { SelfServeActionPages } from 'common/constant/enums';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

const _paymentId = (trackUpdateInvoice) => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        SelfServeActionPages.InvoicesInvoices,
        'invoice-details',
      );
      return (
        <div onClick={() => trackUpdateInvoice('payment_id')}>
          {intermediateElement}
          <div>{createdAt.value(item)}</div>
        </div>
      );
    },
  };
};

export default ({ invoice, trackUpdateInvoice }) => {
  const status = invoice.status;
  const isNew = !invoice.id;
  const isDraft = status === 'draft';
  const isPaid = status === 'paid';
  const isPartiallyPaid = status === 'partially_paid';

  if (isNew || isDraft) {
    return null;
  }

  let payments = [];
  if (Array.isArray(invoice.payments)) {
    payments = invoice.payments;
  } else if (invoice.payments && invoice.payments.items) {
    payments = invoice.payments.items;
  }

  const cols = [_paymentId(trackUpdateInvoice), amount];

  const renderPaymentSection = () => {
    if (isPartiallyPaid || isPaid) {
      return (
        <div>
          <dt>{payments.length > 1 ? 'Payments' : 'Payment Id'}</dt>
          <Table
            className="table-noborder table-inv_payments"
            rows={payments}
            columns={cols}
            showHeaders={false}
          />
        </div>
      );
    }
    return (
      <div>
        <dt>Payment Link</dt>
        <dd>
          <Clipboard value={invoice.short_url} />
        </dd>
      </div>
    );
  };

  return (
    <div className="inv__info">
      <h4>Invoice - {titleCase(invoice.status)}</h4>
      <dl>
        {renderPaymentSection()}
        {invoice.email_status && (
          <div>
            <dt>Email Sent to</dt>
            <dd className="text-ellipsis">
              {invoice.customer_details.customer_email}
              <span
                style={{ marginLeft: '10px' }}
                className={`${notificationClassMap[invoice.email_status]}`}
              >
                {invoice.email_status ? `(${invoice.email_status})` : ''}
              </span>
            </dd>
          </div>
        )}
        {invoice.sms_status && (
          <div>
            <dt>SMS Sent to</dt>
            <dd>
              {invoice.customer_details.customer_contact}
              <span
                style={{ marginLeft: '10px' }}
                className={`${notificationClassMap[invoice.sms_status]}`}
              >
                {invoice.sms_status ? `(${invoice.sms_status})` : ''}
              </span>
            </dd>
          </div>
        )}
        {(isPaid || isPartiallyPaid) && (
          <div>
            <dt>Payment Link</dt>
            <dd>
              <Clipboard
                value={invoice.short_url}
                onCopyToClipboard={() => trackUpdateInvoice('copylink')}
              />
            </dd>
          </div>
        )}
      </dl>
    </div>
  );
};
