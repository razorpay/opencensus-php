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
        class="item-details"
        tabNames={[
          <b>{`Subscription Plans (${recurringPaymentsItems.length})`}</b>,
          <b>{`One-Time Payments (${oneTimePaymentsItems.length})`}</b>,
        ]}
      >
        <div>
          <table>
            <tbody>
              {recurringPaymentsItems.map((pi, ix) => (
                <tr key={ix}>
                  <td>
                    <div>
                      <b>{pi.item.name}</b>
                    </div>
                  </td>
                  <td>
                    <div>
                      <div class="title">Plan Amount</div>
                      <Amount
                        value={pi.total_amount_paid}
                        currency={subscriptionButtonEntity.currency}
                      />
                    </div>
                  </td>
                  <td>
                    <div>
                      <div class="title">Billing Frequency</div>
                      <div>
                        {titleCase(
                          getPeriodLabel(
                            pi.product_config.plan_details.period,
                            pi.product_config.plan_details.interval,
                          ),
                        )}
                      </div>
                    </div>
                  </td>
                  <td>
                    <div>
                      <div class="title">Total Billing Cycles</div>
                      <div>{pi.product_config.subscription_details.total_count}</div>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div>
          <table>
            <tbody>
              {oneTimePaymentsItems.map((pi, ix) => (
                <tr key={ix}>
                  <td>
                    <div>
                      <b>{pi.item.name}</b>
                    </div>
                  </td>
                  <td>
                    <div>
                      <div class="title">Amount</div>
                      <Amount value={pi.item.amount} currency={subscriptionButtonEntity.currency} />
                    </div>
                  </td>
                  <td>
                    <div>
                      <div class="title">Units sold</div>
                      <div>{pi.quantity_sold}</div>
                    </div>
                  </td>
                  <td>
                    <div>
                      <div class="title">Total Revenue</div>
                      <Amount
                        value={pi.total_amount_paid}
                        currency={subscriptionButtonEntity.currency}
                      />
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </TabsContainer>
    );
  }
}
