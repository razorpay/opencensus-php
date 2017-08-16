import { Link } from 'react-router-dom';
import Spinner from 'rzp/ui/Spinner';
import ListToggler from 'rzp/ui/Toggler/ListToggler';
import DataTable from 'rzp/ui/Table/DataTable';
import Alert from 'rzp/ui/Forms/Alert';
import Time from 'rzp/ui/Time';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import OtherDetail from 'merchant/components/OtherDetail';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';

import { reversalId, amount, createdAt } from 'rzp/ui/item/pair';

// Below keys are not to be shown through OtherDetail component
const shownByDefault = ['id', 'entity', 'source', 'notes', 'created_at'];

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
      shownByDefault.indexOf(key) < 0 &&
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
                  value={() => (
                    <div>
                      <Link to={`/payments/${transfer.source}`}>
                        {transfer.source}
                      </Link>
                    </div>
                  )}
                />

                {/* All entities of Transfer not present shownByDefault*/}
                {otherKeys.map(key => (
                  <OtherDetail
                    key={key}
                    label={key}
                    value={transfer[key]}
                    entity={transfer}
                  />
                ))}

                {/* Notes */}
                <NestedEntityDetailRow label="Notes" value={transfer.notes} />

                {/* Create At */}
                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={transfer.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                {reversals
                  ? <ListToggler
                      label="Recently created Reversals"
                      subLabel="to this transfer"
                      loading={reversals.loading}
                      totalItems={reversals.items.length}
                    >
                      <DataTable
                        customClass="reversals-table"
                        progressLoader={true}
                        title="Reversals"
                        columns={[reversalId, amount, createdAt]}
                        items={reversals.items}
                        loading={reversals.loading}
                        showHeaders={true}
                      />
                    </ListToggler>
                  : <EntityDetailRow label="Reversals" value="No Reversals" />}

                <hr />

                {transfer.amount_reversed !== transfer.amount &&
                  <div class="col-sm-offset-4 col-sm-8">
                    <button
                      class="btn btn-primary"
                      onClick={() => {
                        openReversalModal(transfer);
                      }}
                    >
                      Reverse
                    </button>
                  </div>}

              </div>
            </div>
          </div>}
    </div>
  );
};
