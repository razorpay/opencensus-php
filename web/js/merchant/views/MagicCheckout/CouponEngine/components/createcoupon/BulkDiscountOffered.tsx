import React, { useContext, useEffect, useState } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import {
  FormGroup,
  DottedButtonWrapper,
  InputIcon,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import MaxQuantityWidget from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/MaxQuantityWidget';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers and constants imports
import { validateDiscountDetails } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';
import { classList } from 'common/utils/rzp-utils';
import {
  DiscountTypes,
  DiscountCategories,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/constants';

interface AccordionBodyProps {
  couponName: string;
}

const AccordionBody: React.FC<AccordionBodyProps> = ({ couponName }) => {
  const { widgetsData, setWidgetsData, setErrorStates, errorStates } = useContext(ModalContext);
  const handleFormValidations = (value: string) => {
    validateDiscountDetails({
      amountType: widgetsData.bulkDiscountDetails.discountSubType,
      inputValue: value,
      setErrorStates,
      couponName,
    });
  };

  const handleDiscountTypeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setWidgetsData({
      ...widgetsData,
      bulkDiscountDetails: {
        ...widgetsData.bulkDiscountDetails,
        discountType: e.target.value,
        discountSubType: 'fixedAmount',
        discountValue: 0,
      },
    });
    setErrorStates((prev) => ({
      ...prev,
      bulkDiscountDetails: {
        ...prev.bulkDiscountDetails,
        discountValue: null,
      },
    }));
  };

  const handleDiscountSubTypeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setWidgetsData({
      ...widgetsData,
      bulkDiscountDetails: {
        ...widgetsData.bulkDiscountDetails,
        discountSubType: e.target.value,
        discountValue: 0,
      },
    });
    setErrorStates((prev) => ({
      ...prev,
      bulkDiscountDetails: {
        ...prev.bulkDiscountDetails,
        discountValue: null,
      },
    }));
  };

  const handleDiscountValueChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setWidgetsData({
      ...widgetsData,
      bulkDiscountDetails: {
        ...widgetsData.bulkDiscountDetails,
        discountValue: value,
      },
    });
    handleFormValidations(value);
  };

  return (
    <div>
      <FormGroup>
        <div className="form-label">Discount type</div>
        <div className="form-input max-width-100">
          <div className="mb-12">
            <Input.Radio
              key={widgetsData.bulkDiscountDetails.discountType}
              autoRender
              name="couponEligibility"
              options={DiscountCategories}
              defaultValue={widgetsData.bulkDiscountDetails.discountType}
              onChange={handleDiscountTypeChange}
            />
            {widgetsData.bulkDiscountDetails.discountType === 'discountOnAll' ? (
              <FormGroup style={{ marginTop: '12px', marginLeft: '16px' }}>
                <div className="form-input">
                  <div className="mb-12">
                    <Input.Radio
                      key={widgetsData.bulkDiscountDetails.discountSubType}
                      autoRender
                      defaultValue={widgetsData.bulkDiscountDetails.discountSubType}
                      onChange={handleDiscountSubTypeChange}
                      name="discountType"
                      options={DiscountTypes}
                    />
                  </div>
                  <div className="mb-4">
                    <label className="discount-amount-label">
                      {widgetsData.bulkDiscountDetails.discountSubType === 'fixedAmount'
                        ? 'Discount amount'
                        : 'Discount percentage'}
                    </label>
                    {widgetsData.bulkDiscountDetails.discountSubType === 'fixedAmount' ? (
                      <Input
                        name="flatDiscountValue"
                        type="number"
                        onBlur={handleDiscountValueChange}
                        addonBefore={<InputIcon className="i-rupee" />}
                        className={classList(
                          'w-200',
                          errorStates.bulkDiscountDetails.discountValue ? 'error-field' : '',
                        )}
                        defaultValue={widgetsData.bulkDiscountDetails.discountValue}
                        onChange={handleDiscountValueChange}
                        value={widgetsData.bulkDiscountDetails.discountValue}
                        onWheel={onWheelPreventChange}
                      />
                    ) : (
                      <Input
                        name="percentageDiscountValue"
                        type="number"
                        onBlur={handleDiscountValueChange}
                        addonAfter={<InputIcon className="i-offer3" />}
                        className={classList(
                          'w-200',
                          errorStates.bulkDiscountDetails.discountValue ? 'error-field' : '',
                        )}
                        defaultValue={widgetsData.bulkDiscountDetails.discountValue}
                        onChange={handleDiscountValueChange}
                        value={widgetsData.bulkDiscountDetails.discountValue}
                        onWheel={onWheelPreventChange}
                      />
                    )}

                    <p className="error-message">{errorStates.bulkDiscountDetails.discountValue}</p>
                  </div>
                </div>
              </FormGroup>
            ) : (
              <DottedButtonWrapper className="mt-12">
                <label className="discount-amount-label">
                  <strong>Price offered per product</strong>
                </label>
                <Input
                  value={widgetsData.bulkDiscountDetails.discountValue}
                  name="fixedAmountforProducts"
                  type="number"
                  addonBefore={<InputIcon className="i-rupee" />}
                  className={classList(
                    'w-200',
                    errorStates.bulkDiscountDetails.discountValue ? 'error-field' : '',
                  )}
                  defaultValue={widgetsData.bulkDiscountDetails.discountValue}
                  onChange={handleDiscountValueChange}
                  onBlur={handleDiscountValueChange}
                  onWheel={onWheelPreventChange}
                />
                <p className="error-message">{errorStates.bulkDiscountDetails.discountValue}</p>
              </DottedButtonWrapper>
            )}
          </div>
        </div>
      </FormGroup>
    </div>
  );
};

const BulkDiscountOffered: React.FC<AccordionBodyProps> = ({ couponName }) => {
  const { errorStates } = useContext(ModalContext);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    setIsOpen((prev) => {
      const hasErrors =
        errorStates.bulkDiscountDetails &&
        Object.values(errorStates.bulkDiscountDetails).some((value) => value !== null);

      return hasErrors || prev;
    });
  }, [errorStates.bulkDiscountDetails]);

  return (
    <div>
      <Accordion
        open={isOpen}
        header={<div>Discount Offered</div>}
        body={<AccordionBody couponName={couponName} />}
        footer={<MaxQuantityWidget dataKey="bulkDiscountDetails" />}
      />
    </div>
  );
};

export default BulkDiscountOffered;
