import React, { useState } from 'react';
import Amount from 'common/ui/Amount';

export default ({ order }) => {
  const [isBreakupVisible, setBreakupVisible] = useState(false);

  return (
    <div class="magic-checkout-amount-value">
      <Amount value={order.amount} currency={order.currency} />
      <span>| {order.currency}</span>
      {order.status !== 'created' && (
        <button
          type="button"
          class="magic-checkout-collapse-btn"
          onClick={(_) => setBreakupVisible((visible) => !visible)}
        >
          {isBreakupVisible ? (
            <span>
              <i class="i i-chevron-up" />
            </span>
          ) : (
            <span>
              <i class="i i-chevron-down" />
            </span>
          )}
        </button>
      )}
      {order.status !== 'created' && isBreakupVisible ? (
        <div class="magic-checkout-breakup-table">
          <div class="magic-checkout-row">
            <div>Order Amount</div>
            <div>
              <Amount value={order.line_items_total} currency={order.currency} />
            </div>
          </div>
          {/* <div class="super-checkout-row">
            <div>COD Charges</div>
            <div>
              + <Amount value={order.cod_fee || 0} currency={order.currency} />
            </div>
          </div> */}
          <div class="magic-checkout-row">
            <div>Shipping Charges</div>
            <div>
              + <Amount value={order.shipping_fee || 0} currency={order.currency} />
            </div>
          </div>
          {order.promotions?.length > 0 && (
            <div class="magic-checkout-row">
              <div class="magic-checkout-green">{order.promotions[0].code} Coupon</div>
              <div>
                - <Amount value={order.promotions[0].value} currency={order.currency} />
              </div>
            </div>
          )}
        </div>
      ) : (
        <></>
      )}
    </div>
  );
};
