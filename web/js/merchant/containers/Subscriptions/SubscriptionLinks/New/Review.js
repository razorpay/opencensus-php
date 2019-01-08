import Amount from 'rzp/ui/Amount';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { getIntervalCycle } from 'rzp/utils/rzp-utils';

export default function NewSubscriptionLinkReview({ fields, ...props }) {
  const selectedPlan = props.plans.find(({ id }) => id === fields.plan_id);

  const { amount: planAmount } = selectedPlan.item;
  const planQuantity = fields.quantity;

  const addOnAmount = fields.addons.reduce(
    (totalAmount, { item, quantity }) => totalAmount + item.amount * quantity,
    0
  );
  const subscriptioAmount = planAmount * planQuantity;
  const authorizationAmount = subscriptioAmount + addOnAmount;

  const intervalCycle = getIntervalCycle(
    selectedPlan.interval,
    selectedPlan.period
  );
  return (
    <div class="Subscription--New-review">
      <div class="plan-details">
        <h4>Plan</h4>
        <p>{selectedPlan.item.name}</p>
        <p>
          {intervalCycle}&nbsp; the customer will be charged&nbsp;
          <Amount
            value={selectedPlan.item.amount}
            currency={selectedPlan.item.currency}
          />
        </p>
      </div>

      <div class="Payments">
        {/* authorization payment */}
        <div className="Payments--item">
          <div className="Payments--item-inner">
            <p className="small">First Payment</p>
            <p>
              <strong>Authorization Payment:</strong>{' '}
              <Amount
                value={authorizationAmount}
                currency={selectedPlan.item.currency}
              />
            </p>
            <div>
              <EntityDetailRow label="Subscription Amount">
                <Amount
                  value={planAmount}
                  currency={selectedPlan.item.currency}
                />&nbsp;x&nbsp;{planQuantity}&nbsp;(quantity)
              </EntityDetailRow>
              {!!addOnAmount && (
                <EntityDetailRow label="Upfront Amount">
                  <Amount value={addOnAmount} />
                </EntityDetailRow>
              )}
            </div>
          </div>
        </div>

        <div className="Payments--item">
          <div className="Payments--item-inner">
            <p className="small">{intervalCycle} after the first payment</p>
            <p>
              <strong>Recurring Payments:</strong>{' '}
              <Amount
                value={subscriptioAmount}
                currency={selectedPlan.item.currency}
              />
            </p>
            <div>
              <EntityDetailRow
                label="No. of cycles"
                value={fields.total_count}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
