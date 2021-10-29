import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import DataTable from 'common/ui/Table/DataTable';
import ListGroupToggler from 'common/ui/Toggler/ListGroupToggler';
import { OrderStatusLabel } from 'merchant/components/StatusLabel';
import SuperCheckoutLabel from 'merchant/components/SuperCheckout/SuperCheckoutLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { paymentId, amount, status, createdAt } from 'common/ui/item/pair';
import Definition from 'common/ui/Definition';
import React, { useEffect } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import AmountBreakup from 'merchant/views/Transactions/Orders/components/AmountBreakup';
import SkuDetails from 'merchant/views/Transactions/Orders/components/SkuDetails';

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
              <EntityDetailRow label="Order Type" value={() => <SuperCheckoutLabel />} />

              {order.line_items && (
                <EntityDetailRow
                  label="SKU ID"
                  value={() => <SkuDetails line_items={order.line_items} />}
                />
              )}

              <EntityDetailRow
                label="Total Amount Paid"
                value={() => <AmountBreakup order={order} />}
              />

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
                {Object.keys(order.notes).length
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
          <div class="SliderPanel__Extra">
            <div class="panel-body">
              <EntityDetailRow label="Customer Details">
                {order.customer_details ? (
                  <Definition customClass="notes">
                    {order.customer_details?.contact}
                    {order.customer_details?.email}
                  </Definition>
                ) : (
                  '--'
                )}
              </EntityDetailRow>
              <EntityDetailRow label="Shipping Address">
                {order.customer_details ? (
                  <Definition customClass="notes">
                    {order.customer_details?.shipping_address?.name}
                    {`${order.customer_details?.shipping_address?.line1}
                          ${order.customer_details?.shipping_address?.line2}
                          ${order.customer_details?.shipping_address?.city},${order.customer_details?.shipping_address?.state}-${order.customer_details?.shipping_address?.zipcode}
                          Phone Number: ${order.customer_details?.shipping_address?.conatct}
                        `}
                  </Definition>
                ) : (
                  '--'
                )}
              </EntityDetailRow>
              <EntityDetailRow label="Billing Address">
                {order.customer_details ? (
                  <Definition customClass="notes">
                    {order.customer_details?.billing_address?.name}
                    {`${order.customer_details?.billing_address?.line1}
                          ${order.customer_details?.billing_address?.line2}
                          ${order.customer_details?.billing_address?.city},${order.customer_details?.billing_address?.state}-${order.customer_details?.billing_address?.zipcode}
                          Phone Number: ${order.customer_details?.billing_address?.conatct}
                        `}
                  </Definition>
                ) : (
                  '--'
                )}
              </EntityDetailRow>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
