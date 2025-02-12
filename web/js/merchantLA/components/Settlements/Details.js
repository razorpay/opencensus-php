import React from 'react';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import ListGroupToggler from 'common/ui/Toggler/ListGroupToggler';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import SettlementBreakupTable from './BreakupTable';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

const SettlementDetails = (props) => {
  let { settlement, breakupDetails, isLoading, statusMsg, user } = props;

  return (
    <div className="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            Settlement Id: <b>{settlement.id}</b>
          </div>

          <div className="SliderPanel__Body">
            <div className="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <EntityDetailRow
                label="Amount"
                value={() => (
                  <Amount value={settlement.amount} currency={user?.merchant?.currency || 'INR'} />
                )}
              />

              <EntityDetailRow
                label="Status"
                value={() => <SettlementStatusLabel status={settlement.status} />}
              />

              <EntityDetailRow
                label="Created At"
                value={() => (
                  <Time value={settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                )}
              />

              <EntityDetailRow
                label="Fees"
                value={() => (
                  <Amount value={settlement.fees} currency={user?.merchant?.currency || 'INR'} />
                )}
              />

              <EntityDetailRow label="UTR" value={settlement.utr} />

              <EntityDetailRow
                label="Tax"
                value={() => (
                  <Amount value={settlement.tax} currency={user?.merchant?.currency || 'INR'} />
                )}
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

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(SettlementDetails);
