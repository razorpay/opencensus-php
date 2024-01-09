import React, { useState } from 'react';
import Amount from 'common/ui/Amount';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { truncateString } from 'common/utils/rzp-utils';

export default ({ order }) => {
  const [isBreakupVisible, setBreakupVisible] = useState(false);

  const {
    offer,
    currency,
    line_items_total,
    status,
    shipping_fee,
    promotions,
    amount,
    tax_details,
    cod_fee,
  } = order || {};
  const giftCardType = 'gift_card';
  const magicPrepayCODDiscountType = 'prepay_discount';

  const { id: offerId, name: offerName, discount } = offer || {};
  const giftCardList = [];
  let coupon, magicPrepayDiscount;

  if (Array.isArray(promotions)) {
    promotions.forEach((promotion) => {
      if (promotion.type === giftCardType) {
        giftCardList.push(promotion);
      } else if (promotion.type === magicPrepayCODDiscountType) {
        magicPrepayDiscount = promotion;
      } else {
        coupon = promotion;
      }
    });
  }
  return (
    <div className="magic-checkout-amount-value">
      <Amount value={amount} currency={currency} />
      <span>| {currency}</span>
      {status !== 'created' && (
        <button
          type="button"
          className="magic-checkout-collapse-btn"
          onClick={(_) => setBreakupVisible((visible) => !visible)}
        >
          {isBreakupVisible ? (
            <span>
              <i className="i i-chevron-up" />
            </span>
          ) : (
            <span>
              <i className="i i-chevron-down" />
            </span>
          )}
        </button>
      )}
      {status !== 'created' && isBreakupVisible ? (
        <div className="magic-checkout-breakup-table">
          <div className="magic-checkout-row">
            <div>Order Amount</div>
            <div>
              <Amount value={line_items_total} currency={currency} />
            </div>
          </div>
          {/* <div className="super-checkout-row">
            <div>COD Charges</div>
            <div>
              + <Amount value={order.cod_fee || 0} currency={currency} />
            </div>
          </div> */}
          {tax_details?.taxes_included === false ? (
            <div className="magic-checkout-row">
              <div>Taxes</div>
              <div>
                + <Amount value={tax_details.total_tax} currency={currency} />
              </div>
            </div>
          ) : null}
          {coupon?.code ? (
            <div className="magic-checkout-row">
              <div className="magic-checkout-green">{coupon.code} Coupon</div>
              <div>
                - <Amount value={coupon.value} currency={currency} />
              </div>
            </div>
          ) : null}
          {magicPrepayDiscount?.value >= 0 ? (
            <div className="magic-checkout-row">
              <div className="magic-checkout-green">COD to Prepaid discount</div>
              <div className="magic-prepay-discount-value">
                - <Amount value={magicPrepayDiscount.value} currency={currency} />
              </div>
            </div>
          ) : null}
          <div className="magic-checkout-row">
            <div>Shipping Charges</div>
            <div>
              + <Amount value={shipping_fee || 0} currency={currency} />
            </div>
          </div>
          <div className="magic-checkout-row">
            <div>COD Charges</div>
            <div>
              + <Amount value={cod_fee || 0} currency={currency} />
            </div>
          </div>
          {giftCardList.map(({ code, value }) => (
            <div className="magic-checkout-row" key={code}>
              <div className="magic-checkout-green">{`Gift card (**** ${code.slice(
                code.length - 4,
              )})`}</div>
              <div>
                - <Amount value={value || 0} currency={currency} />
              </div>
            </div>
          ))}
          {offerId && (
            <div className="magic-checkout-row">
              <div>
                {`Offer (${truncateString(offerName, 10)})`}
                <i className="i i-info-circle">
                  <Popover align="bottom" theme="dark">
                    <PopoverBody>
                      <div>Offer ID: {offerId}</div>
                    </PopoverBody>
                  </Popover>
                </i>
              </div>
              <div>
                - <Amount value={discount} currency={currency} />
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
