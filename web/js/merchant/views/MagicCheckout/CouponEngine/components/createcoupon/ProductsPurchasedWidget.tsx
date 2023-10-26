import React, { useContext, ChangeEvent, useEffect, useState } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import AddCollectionProductComponent from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductCollectionComponent';
import {
  FormGroup,
  MinimumQuantityWrapper,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

interface AccordionBodyProps {
  couponName: string;
}

const AccordionBody: React.FC<AccordionBodyProps> = ({ couponName }) => {
  const { widgetsData, setWidgetsData } = useContext(ModalContext);

  return (
    <div>
      <FormGroup>
        <div className="form-label">Purchase requirements</div>
        <MinimumQuantityWrapper>
          <Input
            name="minimumQuantity"
            type="text"
            required
            className="w-200"
            addonAfter={<span> Qty </span>}
            defaultValue={widgetsData.productsPurchased.minimumValue}
            value={widgetsData.productsPurchased.minimumValue}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
              setWidgetsData({
                ...widgetsData,
                productsPurchased: {
                  ...widgetsData.productsPurchased,
                  minimumValue: e.target.value,
                },
              });
            }}
          />
        </MinimumQuantityWrapper>
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
