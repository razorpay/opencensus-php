import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import DataTable from 'common/ui/Table/DataTable';
import ListGroupToggler from 'common/ui/Toggler/ListGroupToggler';
import { OrderStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { paymentId, amount, status, createdAt } from 'common/ui/item/pair';
import Definition from 'common/ui/Definition';
import React, { useEffect } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default (props) => {
  const { order, payments, isLoading, statusMsg } = props;

  useEffect(() => {
    const properties = {
      orderId: order.id,
      orderAmount: order.amount,
      orderCurrency: order.currency,
      orderStatus: order.status,
      createdAt: order.created_at,
      ...getCommonAnalyticsProperties(window.rzp_user),
    };

    analyticsTrack({
      objectName: 'order details',
      actionName: 'fetched',
      screen: 'transactions',
      properties,
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
            Order Id: <b>{order.id}</b>
          </div>

          <div class="SliderPanel__Body">
            <Alert type={statusMsg.type} message={statusMsg.message} />
            <div class="panel-body">
              <EntityDetailRow
                label="Amount"
                value={() => <Amount value={order.amount} currency={order.currency} />}
              />

              <EntityDetailRow label="Currency" value={order.currency} />
              <EntityDetailRow label="Attempts" value={order.attempts} />

              <EntityDetailRow
                label="Status"
                value={() => <OrderStatusLabel status={order.status} />}
              />

              <EntityDetailRow
                label="Created At"
                value={() => <Time value={order.created_at} format="DD MMM YYYY, hh:mm:ss a" />}
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

              <EntityDetailRow label="Notes">
                {order.notes && !Array.isArray(order.notes)
                  ? Object.keys(order.notes).map((key, index) => (
                      <Definition key={index} customClass="notes">
                        {key}
                        {String(order.notes[key] || '--')}
                      </Definition>
                    ))
                  : '--'}
              </EntityDetailRow>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
