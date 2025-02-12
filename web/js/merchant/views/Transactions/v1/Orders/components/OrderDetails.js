import React, { useEffect } from 'react';

import { useSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import DataTable from 'common/ui/Table/DataTable';
import Time from 'common/ui/Time';
import ListGroupToggler from 'common/ui/Toggler/ListGroupToggler';
import { paymentId, amount, status, createdAt } from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { OrderStatusLabel } from 'merchant/components/StatusLabel';
import { selfServerTrack } from 'merchant/views/Transactions/v1/AnalyticsTrack';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';

const _paymentId = (initiatePage = 'Transactions.Payments', splitz) => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        initiatePage,
        'order-details',
        splitz,
      );
      return <div>{intermediateElement}</div>;
    },
  };
};

export default (props) => {
  const splitz = useSplitzService();
  const { order, payments, isLoading, statusMsg, version } = props;

  useEffect(() => {
    const properties = {
      orderId: order.id,
      orderAmount: order.amount,
      orderCurrency: order.currency,
      orderStatus: order.status,
      createdAt: order.created_at,
      version,
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
    <div className="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            Order Id: <b>{order.id}</b>
          </div>

          <div className="SliderPanel__Body">
            <Alert type={statusMsg.type} message={statusMsg.message} />
            <div className="panel-body">
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
                    columns={[_paymentId('Transactions.Orders', splitz), amount, status, createdAt]}
                    title="Payments"
                    items={payments.items}
                    loading={payments.loading}
                    showHeaders={false}
                    onCellClick={selfServerTrack}
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
