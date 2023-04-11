import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { BILLING_LABEL } from 'merchant/views/Account/Profile/deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';

const BrandNameLabel = (): JSX.Element => {
  return (
    <div className="brand-name-label">
      <TextHighlighter hashedWith={BILLING_LABEL}>Brand Name</TextHighlighter>
      <small className="help-content">
        <i className="i i-info-outline" />
        <Popover align="top" theme="dark">
          <PopoverBody>
            <div>
              <div>Brand Name changes would be reflected in the following places,</div>
              <div>- Transaction Confirmation Email</div>
              <div>- Refund Email</div>
              <div>- Payment Pages</div>
              <div>- Payment link</div>
              <div>- Checkout</div>
              <div>- Smart Collect</div>
              <div>- Route</div>
              <div>- Subscriptions</div>
            </div>
          </PopoverBody>
        </Popover>
      </small>
    </div>
  );
};

export default BrandNameLabel;
