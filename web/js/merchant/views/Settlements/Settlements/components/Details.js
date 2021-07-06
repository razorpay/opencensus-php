import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AnnouncementBar from 'merchant/components/AnnouncementBar';
import ShowWhen from 'merchant/components/ShowWhen';
import React, { useEffect } from 'react';
import { analyticsTrack } from 'common/utils/analytics';

export default (props) => {
  const { settlement, isLoading, statusMsg } = props;

  useEffect(() => {
    analyticsTrack({
      objectName: 'settlement details',
      actionName: 'fetched',
      screen: 'transactions',
      properties: settlement.analyticsPayload(),
    });
  }, []);

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
                value={() => <Amount value={settlement.amount} currency="INR" />}
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
                value={() => <Amount value={settlement.fees} currency="INR" />}
              />

              <EntityDetailRow label="UTR" value={settlement.utr} />

              <EntityDetailRow
                label="Tax"
                value={() => <Amount value={settlement.tax} currency="INR" />}
              />

              <EntityDetailRow
                label="Breakup"
                value={() => (
                  <button
                    class="btn btn-xs btn-default"
                    onClick={() => props.onToggleBreakupDetails(settlement)}
                  >
                    Show
                  </button>
                )}
              />
              <ShowWhen additionalCondition={(user) => user.isProjectNitroEnabled}>
                <AnnouncementBar
                  fromWhere="settlements"
                  url="https://lp.razorpay.com/razorpayxca-sttlmnts2"
                />
              </ShowWhen>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
