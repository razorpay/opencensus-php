import React, { useContext, ChangeEvent, useEffect, useState } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import AddCollectionProductComponent from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductCollectionComponent';
import {
  FormGroup,
  InputIcon,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import MaxQuantityWidget from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/MaxQuantityWidget';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers and constants imports
import { validateDiscountDetails } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';
import { classList } from 'common/utils/rzp-utils';
import { DiscountTypes } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/constants';

const AccordionBody: React.FC = () => {
  const { widgetsData, setWidgetsData, setErrorStates, errorStates } = useContext(ModalContext);

  const handleInputChange = (e: ChangeEvent<HTMLInputElement>, name: string) => {
    const { value } = e.target;
    setWidgetsData({
      ...widgetsData,
      discountOffered: {
        ...widgetsData.discountOffered,
        [name]: value,
      },
    });

    if (name === 'discountType' || name === 'discountSubType') {
      setErrorStates((prevState) => ({
        ...prevState,
        discountOffered: {
          ...prevState.discountOffered,
          discountValue: null,
        },
      }));
      setWidgetsData((prevState) => ({
        ...prevState,
        discountOffered: {
          ...prevState.discountOffered,
          discountValue: 0,
        },
      }));
    }
  };

  return (
    <div>
      <FormGroup>
        <div className="form-label">Additional Products Offered</div>
        <div className="form-input">
          <Input
            name="productsOfferedQty"
            type="number"
            required
            className="w-200"
            addonAfter={<span> Qty </span>}
            defaultValue={widgetsData.discountOffered.productsOffered}
            value={widgetsData.discountOffered.productsOffered}
            onChange={(e: ChangeEvent<HTMLInputElement>) => {
              setWidgetsData({
                ...widgetsData,
                discountOffered: {
                  ...widgetsData.discountOffered,
                  productsOffered: e.target.value,
                },
              });
            }}
            onWheel={onWheelPreventChange}
          />
        </div>
      </FormGroup>
      <AddCollectionProductComponent stateObject="discountOffered" />
      <FormGroup>
        <div className="form-label"> Discount type</div>
        <div className="form-input">
          <div className="mb-12">
            <Input.Radio
              key={widgetsData.discountOffered.discountType}
              autoRender
              defaultValue={widgetsData.discountOffered.discountType}
              onChange={(e: ChangeEvent<HTMLInputElement>) => handleInputChange(e, 'discountType')}
              name="discountType"
              options={[
                {
                  label: 'Monetary discount',
                  value: 'monetary-discount',
                },
                {
                  label: 'Free',
                  value: 'free',
                },
              ]}
            />
          </div>
          {widgetsData.discountOffered.discountType === 'monetary-discount' ? (
            <div className="form-input" style={{ marginLeft: '16px' }}>
              <div className="mb-12">
                <Input.Radio
                  key={widgetsData.discountOffered.discountSubType}
                  autoRender
                  defaultValue={widgetsData.discountOffered.discountSubType}
                  onChange={(e: ChangeEvent<HTMLInputElement>) => {
                    handleInputChange(e, 'discountSubType');
                  }}
                  name="discountSubType"
                  options={DiscountTypes}
                />
              </div>
              <div className="mb-4">
                <label className="discount-amount-label">
                  {widgetsData.discountOffered.discountSubType === 'fixedAmount'
                    ? 'Discount amount'
                    : 'Discount percentage'}
                </label>
                {widgetsData.discountOffered.discountSubType === 'fixedAmount' ? (
                  <Input
                    name="fixedDiscount"
                    type="number"
                    addonBefore={<InputIcon className="i-rupee" />}
                    className={classList(
                      'w-200',
                      errorStates.discountOffered.discountValue ? 'error-field' : '',
                    )}
                    defaultValue={widgetsData.discountOffered.discountValue}
                    value={widgetsData.discountOffered.discountValue}
                    onChange={(e: ChangeEvent<HTMLInputElement>) =>
                      handleInputChange(e, 'discountValue')
                    }
                    onBlur={(e: ChangeEvent<HTMLInputElement>) => {
                      validateDiscountDetails({
                        amountType: widgetsData.discountOffered.discountSubType,
                        inputValue: e.target.value,
                        setErrorStates,
                        couponName: 'buyx_gety',
                      });
                    }}
                    min="0"
                    onWheel={onWheelPreventChange}
                  />
                ) : (
                  <div>
                    <Input
                      name="percentageDiscount"
                      type="number"
                      className={classList(
                        'w-200',
                        errorStates.discountOffered.discountValue ? 'error-field' : '',
                      )}
                      addonAfter={<InputIcon className="i-offer3" />}
                      defaultValue={widgetsData.discountOffered.discountValue}
                      value={widgetsData.discountOffered.discountValue}
                      onChange={(e: ChangeEvent<HTMLInputElement>) =>
                        handleInputChange(e, 'discountValue')
                      }
                      onBlur={(e: ChangeEvent<HTMLInputElement>) => {
                        validateDiscountDetails({
                          amountType: widgetsData.discountOffered.discountSubType,
                          inputValue: e.target.value,
                          setErrorStates,
                          couponName: 'buyx_gety',
                        });
                      }}
                      onWheel={onWheelPreventChange}
                      min="0"
                      max="100"
                    />
                  </div>
                )}
                <p className="error-message">{errorStates.discountOffered.discountValue}</p>
              </div>
            </div>
          ) : null}
        </div>
      </FormGroup>
    </div>
  );
};

const DiscountOfferedWidget: React.FC = () => {
  const { widgetsData, errorStates } = useContext(ModalContext);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    setIsOpen((prev) => {
      const hasErrors =
        errorStates.discountOffered &&
        Object.values(errorStates.discountOffered).some((value) => value !== null);

      return hasErrors || prev;
    });
  }, [errorStates.discountOffered]);

  return (
    <div>
      <Accordion
        open={isOpen}
        header={<div>Discount Offered</div>}
        body={<AccordionBody />}
        footer={
          widgetsData.productsPurchased.minimumType === 'min_qty' ? (
            <MaxQuantityWidget dataKey="discountOffered" />
          ) : null
        }
      />
    </div>
  );
};

export default DiscountOfferedWidget;
