import React, { useContext, useEffect, useState } from 'react';

// ui imports
import AddCollectionProductComponent from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductCollectionComponent';
import MinimumRequirementWidget from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponValidityWidegt/MinimumRequirementWidget';
import { FormGroup } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

interface AccordionBodyProps {
  couponName: string;
}

const AccordionBody: React.FC<AccordionBodyProps> = ({ couponName }) => {
  return (
    <div>
      <FormGroup>
        <MinimumRequirementWidget couponName={couponName} />
      </FormGroup>

      <AddCollectionProductComponent stateObject="productsPurchased" couponName={couponName} />
    </div>
  );
};

interface ProductsPurchasedWidgetProps {
  couponName: string;
}

const ProductsPurchasedWidget: React.FC<ProductsPurchasedWidgetProps> = ({ couponName }) => {
  const { errorStates } = useContext(ModalContext);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    setIsOpen((prev) => {
      const hasErrors =
        errorStates.productsPurchased &&
        Object.values(errorStates.productsPurchased).some((value) => value !== null);

      return hasErrors || prev;
    });
  }, [errorStates.productsPurchased]);

  return (
    <div>
      <Accordion
        open={isOpen}
        header={<div>Products purchased</div>}
        body={<AccordionBody couponName={couponName} />}
      />
    </div>
  );
};

export default ProductsPurchasedWidget;
