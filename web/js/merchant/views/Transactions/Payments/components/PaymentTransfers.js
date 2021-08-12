import React from 'react';
import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import DataTable from 'common/ui/Table/DataTable';
import LoaderDots from 'common/ui/LoaderDots';
import { transferId, amount, createdAt } from 'common/ui/item/pair';
import ShowWhen from 'merchant/components/ShowWhen';

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

  const numTransfers = transferItems.length;
  const transfersSuffix = numTransfers === 0 || numTransfers > 1 ? 's' : '';

  return (
    <span>
      {transfers.loading ? <LoaderDots /> : numTransfers} {titleCase ? 'T' : 't'}ransfer
      {transfersSuffix}
    </span>
  );
};

const TransfersList = ({ transfers }) => {
  const transfersHeading = {
    title: 'Transfer Details',
    subTitle: <NumTransfers transfers={transfers} titleCase={true} />,
  };

  const paymentTransferId = {
    ...transferId,
    value: (item) => (
      <Link to={`/route/transfers/${item.id}`}>
        <code>{item.id}</code>
      </Link>
    ),
  };

  return (
    <ContentToggler>
      <span>Transfer Details</span>
      <div class="full-width-item sub-entity-list">
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
  <button class="btn btn-default" onClick={onClick}>
    {text}
  </button>
);

export default ({ payment, transfers, onCreateTransfer }) => {
  const amountTransferred = payment.amount_transferred;
  const paymentStatus = payment.status;

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
        <p>No transfers created{`${payment.status === 'captured' ? ' yet' : ''}`}</p>
        <ShowWhen additionalCondition={(user) => user.isAllowedEdit('marketplace')}>
          {payment.status === 'captured' && <CreateTransferBtn onClick={onCreateTransfer} />}
        </ShowWhen>
      </div>
    );
  }

  return (
    <div>
      <div class="m-b">
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
      <ShowWhen additionalCondition={(user) => user.isAllowedEdit('marketplace')}>
        {!transfers.loading &&
          payment.status === 'captured' &&
          payment.amount !== amountTransferred && (
            <div class="m-b">
              <CreateTransferBtn text="Create another transfer" onClick={onCreateTransfer} />
            </div>
          )}
      </ShowWhen>
      <TransfersList transfers={transfers} payment={payment} />
    </div>
  );
};
