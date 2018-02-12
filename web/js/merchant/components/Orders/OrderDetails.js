import AsyncButton from 'react-async-button';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import DataTable from 'rzp/ui/Table/DataTable';
import ListGroupToggler from 'rzp/ui/Toggler/ListGroupToggler';
import { OrderStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { paymentId, amount, status, createdAt } from 'rzp/ui/item/pair';

export default props => {
  let { order, payments, isLoading, statusMsg } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            Order Id: <b>{order.id}</b>
          </div>

          <div class="SliderPanel__Body">
            <Alert type={statusMsg.type} message={statusMsg.message} />
            <div class="panel-body">
              <EntityDetailRow
                label="Amount"
                value={() => (
                  <Amount value={order.amount} currency={order.currency} />
                )}
              />

              <EntityDetailRow label="Currency" value={order.currency} />
              <EntityDetailRow label="Attempts" value={order.attempts} />

              <EntityDetailRow
                label="Status"
                value={() => <OrderStatusLabel status={order.status} />}
              />

              <EntityDetailRow
                label="Created At"
                value={() => (
                  <Time
                    value={order.created_at}
                    format="DD MMM YYYY, hh:mm:ss a"
                  />
                )}
              />

              {order.attempts > 0 ? (
                <ListGroupToggler
                  label="Payments"
                  onToggleClick={() => props.onTogglePayments(order)}
                >
                  <DataTable
                    columns={[paymentId, amount, status, createdAt]}
                    title="Payments"
                    items={payments.items}
                    loading={payments.loading}
                    showHeaders={false}
                  />
                </ListGroupToggler>
              ) : (
                <EntityDetailRow label="Payments" value="No Payments" />
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
