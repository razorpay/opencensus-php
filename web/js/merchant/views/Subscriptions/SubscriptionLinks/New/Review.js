import Amount from 'common/ui/Amount';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import UPIBanner from '../components/UPIBanner';

import { getIntervalCycle } from 'common/utils/rzp-utils';

export default function NewSubscriptionLinkReview({ fields, internals, ...props }) {
  const selectedPlan = props.plans.find(({ id }) => id === fields.plan_id);

  const { amount: planAmount, currency } = selectedPlan.item;
  const planQuantity = fields.quantity;

  const addOnAmount = fields.addons
    .filter((addon) => addon.item && !!addon.item.amount) // Filter out empty addon
    .reduce((totalAmount, { item, quantity }) => totalAmount + item.amount * quantity, 0);
  const subscriptionAmount = planAmount * planQuantity;
  const minAuthAmount =
    currency === 'INR' ? 500 : (props.getCurrencyList[currency] || {}).min_auth_value;
  const authorizationAmount = getAuthorizationAmount(
    subscriptionAmount,
    addOnAmount,
    internals._startsImmediately,
    minAuthAmount,
  );

  const intervalCycle = getIntervalCycle(selectedPlan.interval, selectedPlan.period);

  let showUPIUnAvlBanner = authorizationAmount > UPI_AVL_LIMIT;

  if (!showUPIUnAvlBanner) {
    showUPIUnAvlBanner = subscriptionAmount > UPI_AVL_LIMIT;
  }

  const selectedOffer = props.offers.find(({ id }) => id === fields.offer_id) || {};

  return (
    <div class="Subscription--New-review">
      <div class="plan-details">
        <h4>Plan</h4>
        <p>{selectedPlan.item.name}</p>
        <p>
          {intervalCycle} the customer will be charged{' '}
          <Amount
            value={selectedPlan.item.amount}
            currency={selectedPlan.item.currency}
            parentQuerySelector=".Modal-body"
          />
        </p>
      </div>

      <div class="Payments">
        {/* authorization payment */}
        <div class="Payments--item">
          <div class="Payments--item-inner">
            <p class="small">First Payment</p>
            <p>
              <strong>Authorization Payment:</strong>{' '}
              <Amount
                value={authorizationAmount}
                currency={selectedPlan.item.currency}
                parentQuerySelector=".Modal-body"
              />
            </p>
            <div>
              <EntityDetailRow label="Subscription Amount">
                <Amount
                  value={planAmount}
                  currency={selectedPlan.item.currency}
                  parentQuerySelector=".Modal-body"
                />
                &nbsp;x&nbsp;{planQuantity}&nbsp;(quantity)
              </EntityDetailRow>
              {!!addOnAmount && (
                <EntityDetailRow label="Upfront Amount">
                  <Amount
                    value={addOnAmount}
                    currency={selectedPlan.item.currency}
                    parentQuerySelector=".Modal-body"
                  />
                </EntityDetailRow>
              )}
            </div>
          </div>
        </div>

        <div class="Payments--item">
          <div class="Payments--item-inner">
            <p class="small">{intervalCycle} after the first payment</p>
            <p>
              <strong>Recurring Payments:</strong>{' '}
              <Amount
                value={subscriptionAmount}
                currency={selectedPlan.item.currency}
                parentQuerySelector=".Modal-body"
              />
            </p>
            <div>
              <EntityDetailRow label="No. of cycles" value={fields.total_count} />
            </div>
          </div>
        </div>

        {selectedOffer.id && (
          <div class="Payments--item offer-details">
            <div class="Payments--item-inner">
              <div class="heading">Offer Applied</div>

              <div class="display_text">{selectedOffer.display_text}</div>

              <div class="terms">{selectedOffer.terms}</div>
            </div>
          </div>
        )}

        {showUPIUnAvlBanner && <UPIBanner />}
      </div>
    </div>
  );
}

/*
 * refer https://razorpay.com/docs/subscriptions/create/#possible-scenarios to understand logic
 */
function getAuthorizationAmount(subAmt, addonAmt, immediate, minAuthAmount) {
  if (immediate) {
    // in case of addons not present addonAmt will be zero. so no effect on subscription Amount
    return subAmt + addonAmt;
  } else {
    // in case of future subscriptions it is either addon amount (if addons present) else Current currency min auth ammount
    return addonAmt || minAuthAmount;
  }
}
