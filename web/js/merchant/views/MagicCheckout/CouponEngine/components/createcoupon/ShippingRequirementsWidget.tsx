import React from 'react';

// ui imports
import MinimumRequirementWidget from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponValidityWidegt/MinimumRequirementWidget';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';

const AccordionBody: React.FC = () => {
  return <MinimumRequirementWidget couponName="free-shipping" />;
};

const ShippingRequirementsWidget: React.FC = () => {
  return (
    <div>
      <Accordion header={<div>Purchase Requirements</div>} body={<AccordionBody />} />
    </div>
  );
};

export default ShippingRequirementsWidget;
