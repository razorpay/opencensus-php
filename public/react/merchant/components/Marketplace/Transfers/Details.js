import { Link } from 'react-router-dom';

import Alert from 'rzp/ui/Forms/Alert';
import CheckIcon from 'rzp/ui/CheckIcon';
import DataTable from 'rzp/ui/Table/DataTable';
import ListToggler from 'rzp/ui/Toggler/ListToggler';
import { reversalId, amount, createdAt } from 'rzp/ui/item/pair';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import OtherDetail from 'merchant/components/OtherDetail';
import TransferReversal from 'merchant/components/Marketplace/Transfers/TransferReversal';

// Below keys are not to be shown through OtherDetail component
const shownByDefault = {
  id: '',
  entity: '',
  source: '',
  notes: '',
  created_at: '',
  on_hold: '',
};

const keysNotShown = entity => {
  var keys = [];

  if (!entity.getPayload) {
    return keys;
  }

  let payload = entity.getPayload();
  for (var key in entity) {
    // If the entity has that key and its not currently shown
    if (
      entity.hasOwnProperty(key) &&
      payload[key] !== undefined &&
      !shownByDefault.hasOwnProperty(key) &&
      entity[key] !== null
    ) {
      keys.push(key);
    }
  }

  return keys;
};

export default ({
  transfer,
  isLoading,
  statusMsg,
  openReversalModal,
  reversals,
  onToggleReversalsList,
}) => {
  let otherKeys = keysNotShown(transfer);

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              Transfer ID: <strong>{transfer.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />

                {/* Payment Link */}
                <EntityDetailRow
                  label="Source ID"
                  value={() =>
                    <div>
                      <Link to={`/payments/${transfer.source}`}>
                        {transfer.source}
                      </Link>
                    </div>}
                />

                {/* All entities of Transfer not present shownByDefault*/}
                {otherKeys.map(key =>
                  <OtherDetail
                    key={key}
                    label={key}
                    value={transfer[key]}
                    entity={transfer}
                  />
                )}

                <EntityDetailRow
                  label="On Hold"
                  value={() => <CheckIcon value={transfer.on_hold} />}
                />

                {/* Create At */}
                <EntityDetailRow
                  label="Created At"
                  value={() =>
                    <Time
                      value={transfer.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />}
                />

                <EntityDetailRow label="Reversal">
                  <TransferReversal
                    transfer={transfer}
                    reversals={reversals}
                    openTransferReversalModal={openReversalModal}
                  />
                </EntityDetailRow>

                {/* Notes */}
                <EntityDetailRow label="Notes">
                  {Object.keys(transfer.notes).length === 0
                    ? '--'
                    : Object.keys(transfer.notes).map((key, index) =>
                        <Definition key={index}>
                          {key}
                          {String(transfer.notes[key])}
                        </Definition>
                      )}
                </EntityDetailRow>
              </div>
            </div>
          </div>}
    </div>
  );
};
