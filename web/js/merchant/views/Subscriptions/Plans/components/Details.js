import { NavLink } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import DataTable from 'common/ui/Table/DataTable';
import ListToggler from 'common/ui/Toggler/ListToggler';
import { getIntervalCycle } from 'common/utils/rzp-utils';
import { subscriptionId, createdAt, status } from 'common/ui/item/pair';
import Tooltip from 'common/ui/Tooltip';
import { trackClickDuplicatePlan } from 'merchant/views/Subscriptions/Plans/ga';

export default ({ plan, isLoading, statusMsg, subscriptions }) => {
  const tableLimit = 5; // Set limit to total rows displayed in table
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-plan text-main icon--formal" />{' '}
            <strong>{plan.id}</strong>
            <div class="btn-toolbar pull-right">
              <NavLink
                onClick={trackClickDuplicatePlan}
                class="btn Button--primary--invert"
                to={`/plans/new?duplicate_id=${plan.id}`}
              >
                <i class="i i-copy" />
                <Tooltip theme="dark">Duplicate Plan</Tooltip>
              </NavLink>
            </div>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <EntityDetailRow label="Plan Name" value={plan.item.name} />

              <EntityDetailRow
                label="Plan Description"
                value={
                  plan.item.description
                    ? () => {
                        return <span class="pre">{plan.item.description}</span>;
                      }
                    : null
                }
              />

              <EntityDetailRow
                label="Billing Amount"
                value={() => (
                  <Amount
                    currency={plan.item.currency}
                    value={plan.item.amount}
                  />
                )}
              />

              <EntityDetailRow
                label="Billing Frequency"
                value={() => (
                  <span>{getIntervalCycle(plan.interval, plan.period)}</span>
                )}
              />

              <EntityDetailRow
                label="Created At"
                value={() => (
                  <Time
                    value={plan.created_at}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                )}
              />

              <NestedEntityDetailRow label="Notes" value={plan.notes} />

              {subscriptions.items ? (
                <ListToggler
                  label="Recently created Subscriptions"
                  subLabel="to this plan"
                  limit={tableLimit}
                  limitUrl={`/subscriptions?plan_id=${plan.id}`}
                  loading={subscriptions.loading}
                  totalItems={subscriptions.items.length}
                >
                  <DataTable
                    columns={[subscriptionId, createdAt, status]}
                    customClass="subscriptions-table"
                    limit={tableLimit}
                    progressLoader={true}
                    title="Subscriptions"
                    error={subscriptions.error}
                    items={subscriptions.items}
                    loading={subscriptions.loading}
                    showHeaders={false}
                  />
                </ListToggler>
              ) : (
                <EntityDetailRow
                  label="Subscriptions"
                  value="No Subscriptions"
                />
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
