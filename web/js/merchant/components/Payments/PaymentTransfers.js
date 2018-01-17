import React from 'react';
import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import DataTable from 'rzp/ui/Table/DataTable';
import LoaderDots from 'rzp/ui/LoaderDots';
import { transferId, amount, createdAt } from 'rzp/ui/item/pair';

/*
 * Design:
 * https://projects.invisionapp.com/d/main#/console/11823589/249423138/preview
 *
 * Inputs:
 * @params {Object} payment
 * @params {Object} transfers
 *
 * Description
 * Given `payment` and `transfers` parameters exactly the same as their correspondind
 * apis, the component will render as per the design
 */

const createdAtWithStyle = { columnClass: 'text-right', ...createdAt };

const NumTransfers = ({ transfers, titleCase = false }) => {
  const transferItems = transfers.items || [];

  const numTransfers = transferItems.length,
    transfersSuffix = numTransfers === 0 || numTransfers > 1 ? 's' : '';

  return (
    <span>
      {transfers.loading ? <LoaderDots /> : numTransfers}{' '}
      {titleCase ? 'T' : 't'}ransfer{transfersSuffix}
    </span>
  );
};

const TransfersList = ({ transfers, payment }) => {
  const transfersHeading = {
    title: 'Transfer Details',
    subTitle: <NumTransfers transfers={transfers} titleCase={true} />,
  };

  var paymentTransferId = {
    ...transferId,
    value: item => (
      <Link to={`/payments/${payment.id}/` + `${item.id}`}>
        <code>{item.id}</code>
      </Link>
    ),
  };

  return (
    <ContentToggler>
      <span>Transfer Details</span>
      <div className="full-width-item sub-entity-list">
        <DataTable
          customClass="transfers-table"
          progressLoader={true}
          columns={[paymentTransferId, amount, createdAtWithStyle]}
          items={transfers.items}
          loading={transfers.loading}
          showHeaders={false}
          noStripe={true}
          panelHeading={transfersHeading}
        />
      </div>
    </ContentToggler>
  );
};

const CreateTransferBtn = ({ onClick, text = 'Create Transfer' }) => (
  <button className="btn btn-default" onClick={onClick}>
    {text}
  </button>
);

export default ({ payment, transfers, onCreateTransfer }) => {
  const amountTransferred = payment.amount_transferred,
    paymentStatus = payment.status;

  if (['created', 'authorized', 'failed'].indexOf(paymentStatus) >= 0) {
    return (
      <Definition>
        <span>Not Applicable</span>
        <span>Only captured payments can be transferred.</span>
      </Definition>
    );
  }

  if (!transfers.items.length && !transfers.loading) {
    return (
      <div>
        <p>
          No transfers created{`${payment.status === 'captured' ? ' yet' : ''}`}
        </p>
        {payment.status === 'captured' && (
          <CreateTransferBtn onClick={onCreateTransfer} />
        )}
      </div>
    );
  }

  return (
    <div>
      <div className="m-b">
        <Definition>
          <span>
            <NumTransfers transfers={transfers} /> created
          </span>
          <span>
            {transfers.loading ? (
              <LoaderDots />
            ) : (
              <Amount value={amountTransferred} currency={payment.currency} />
            )}
            <span style={{ marginLeft: '4px' }}>Net Transferred</span>
          </span>
        </Definition>
      </div>
      {payment.status === 'captured' &&
        payment.amount !== amountTransferred && (
          <div className="m-b">
            <CreateTransferBtn
              text="Create another transfer"
              onClick={onCreateTransfer}
            />
          </div>
        )}
      <TransfersList transfers={transfers} payment={payment} />
    </div>
  );
};
