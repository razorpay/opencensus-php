import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import ListGroupToggler from 'rzp/ui/Toggler/ListGroupToggler';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import SettlementBreakupTable from './BreakupTable';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default props => {
  let { settlement, breakupDetails, isLoading, statusMsg } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            Settlement Id: <b>{settlement.id}</b>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <EntityDetailRow
                label="Amount"
                value={() => <Amount value={settlement.amount} />}
              />

              <EntityDetailRow
                label="Status"
                value={() => (
                  <SettlementStatusLabel status={settlement.status} />
                )}
              />

              <EntityDetailRow
                label="Created At"
                value={() => (
                  <Time
                    value={settlement.created_at}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                )}
              />

              <EntityDetailRow
                label="Fees"
                value={() => <Amount value={settlement.fees} />}
              />

              <EntityDetailRow label="UTR" value={settlement.utr} />

              <EntityDetailRow
                label="Tax"
                value={() => <Amount value={settlement.tax} />}
              />

              <ListGroupToggler
                label="Breakup"
                onToggleClick={() => props.onToggleBreakupDetails(settlement)}
              >
                <SettlementBreakupTable
                  items={breakupDetails.items}
                  loading={breakupDetails.loading}
                />
              </ListGroupToggler>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
