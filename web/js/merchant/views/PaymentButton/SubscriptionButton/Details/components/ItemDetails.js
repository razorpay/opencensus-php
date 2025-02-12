import React from 'react';

import TabsContainer from 'common/ui/Tabs';
import { titleCase } from 'common/utils/rzp-utils';
import { getPeriodLabel } from '../../Create/constants/billingCycle';
import Amount from 'common/ui/Amount';

export default class ItemDetails extends React.Component {
  getSeparateItems() {
    const { subscriptionButtonEntity } = this.props;

    const oneTimePaymentsItems = [];
    const recurringPaymentsItems = [];

    subscriptionButtonEntity.payment_page_items.forEach((item) => {
      if (item.plan_id) {
        recurringPaymentsItems.push(item);
      } else {
        oneTimePaymentsItems.push(item);
      }
    });

    return {
      oneTimePaymentsItems,
      recurringPaymentsItems,
    };
  }

  render() {
    const { subscriptionButtonEntity } = this.props;

    const { recurringPaymentsItems, oneTimePaymentsItems } = this.getSeparateItems();

    return (
      <TabsContainer
        className="item-details"
        tabNames={[
          <b key="1">{`Subscription Plans (${recurringPaymentsItems.length})`}</b>,
          <b key="2">{`One-Time Payments (${oneTimePaymentsItems.length})`}</b>,
        ]}
      >
        <div>
          <div className="table-container">
            {recurringPaymentsItems.map((pi, ix) => (
              <div key={ix} className="table">
                <div>
                  <b>{pi.item.name}</b>
                </div>
                <div>
                  <div className="title">Plan Amount</div>
                  <Amount
                    value={pi.total_amount_paid}
                    currency={subscriptionButtonEntity.currency}
                  />
                </div>
                <div>
                  <div className="title">Billing Frequency</div>
                  <div>
                    {titleCase(
                      getPeriodLabel(
                        pi.product_config.plan_details.period,
                        pi.product_config.plan_details.interval,
                      ),
                    )}
                  </div>
                </div>
                <div>
                  <div className="title">Total Billing Cycles</div>
                  <div>{pi.product_config.subscription_details.total_count}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div>
          <div className="table-container">
            {oneTimePaymentsItems.map((pi, ix) => (
              <div key={ix} className="table">
                <div>
                  <b>{pi.item.name}</b>
                </div>
                <div>
                  <div className="title">Amount</div>
                  <Amount value={pi.item.amount} currency={subscriptionButtonEntity.currency} />
                </div>
                <div>
                  <div className="title">Units sold</div>
                  <div>{pi.quantity_sold}</div>
                </div>
                <div>
                  <div className="title">Total Revenue</div>
                  <Amount
                    value={pi.total_amount_paid}
                    currency={subscriptionButtonEntity.currency}
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
      </TabsContainer>
    );
  }
}
