import React from 'react';

import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import DataTable from 'rzp/ui/Table/DataTable';
import LoaderDots from 'rzp/ui/LoaderDots';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import { transferId, amount } from 'rzp/ui/item/pair';

/*
 * Design:
 * https://projects.invisionapp.com/share/5ED7Z2RSM#/screens/249521201
 *
 * Inputs:
 * @param {Object} transfer
 * @param {Object} reversal
 * @param {Function} openTransferReversalModal
 *
 * Descrition:
 * Given `payment` and `reversal` parameters exactly the same as their
 * corresponding apis , and `openTransferReversalModal` which will open
 * a modal to create new reversal, this component will display
 * reversal amounts, status and actions according to the design
 */

const NumReversals = ({ reversals }) => {
  if (reversals.loading) {
    return <LoaderDots />;
  }

  const numReversals = reversals.items.length,
    reversalSuffix = numReversals === 0 || numReversals > 1 ? 's' : '';

  return (
    <span>
      {numReversals} reversal{reversalSuffix}
    </span>
  );
};

const ReversalsList = ({ reversals }) => {
  return (
    <ContentToggler>
      <span>Reversal Details</span>
      <div className="reversals-list full-width-item">
        <div className="panel-heading">
          <span className="label--primary">Reversal Details</span>
          <span className="label--secondary">
            <NumReversals reversals={reversals} />
          </span>
        </div>
        <div className="panel-content">
          <DataTable
            customClass="reversals-table"
            progressLoader={true}
            columns={[transferId, amount]}
            items={reversals.items}
            loading={reversals.loading}
            showHeaders={false}
            noStripe={true}
          />
        </div>
      </div>
    </ContentToggler>
  );
};

export default ({ transfer, reversals, openTransferReversalModal }) => {
  const reversedAmount = transfer.amount_reversed,
    reversalStatus =
      reversedAmount === 0
        ? null
        : transfer.amount === reversedAmount ? 'full' : 'partial';

  if (!reversalStatus) {
    return (
      <div>
        <p>No reversals created</p>
        <button
          className="btn btn-default"
          onClick={openTransferReversalModal(transfer)}
        >
          Create reversal
        </button>
      </div>
    );
  } else if (reversalStatus === 'full') {
    return (
      <div>
        <p>Fully Reversed</p>
        <ReversalsList reversals={reversals} />
      </div>
    );
  }

  return (
    <div>
      <Definition>
        <span>
          <Amount value={reversedAmount} /> Reversed
        </span>
        <span>
          Partially Reversed in <NumReversals reversals={reversals} />
        </span>
      </Definition>
      <p />
      <p>
        <button
          className="btn btn-default"
          onClick={() => openTransferReversalModal(transfer)}
        >
          Create Another Reversal
        </button>
      </p>
      <ReversalsList reversals={reversals} />
    </div>
  );
};
