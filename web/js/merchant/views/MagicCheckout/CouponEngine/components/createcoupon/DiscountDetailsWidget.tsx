import React, { useContext, ChangeEvent, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { getCurrencySymbol } from '@razorpay/i18nify-js/currency';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import AddCollectionProductComponent from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductCollectionComponent';
import {
  CheckboxLabelWithInfo,
  DiscountDetailsMaxQuantityContainer,
  FormGroup,
  InputIcon,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import MinimumRequirementWidget from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CouponValidityWidegt/MinimumRequirementWidget';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers and constants imports
import { validateDiscountDetails } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/helpers/createCouponFormValidators';
import { onWheelPreventChange } from 'merchant/views/MagicCheckout/helper';
import { classList } from 'common/utils/rzp-utils';
import { DiscountTypes } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/constants';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { CurrencyCodeType } from '@razorpay/i18nify-js';
interface AccordionBodyProps {
  couponName: string;
  merchantCurrency: CurrencyCodeType;
}

const AccordionBody: React.FC<AccordionBodyProps> = ({ couponName, merchantCurrency }) => {
  const { widgetsData, setWidgetsData, setErrorStates, errorStates } = useContext(ModalContext);

  const handleInputChange = (e: ChangeEvent<HTMLInputElement>, name: string) => {
    const { value } = e.target;
    setWidgetsData({
      ...widgetsData,
      discountDetails: {
        ...widgetsData.discountDetails,
        [name]: value,
      },
    });

    if (name === 'discountType') {
      setErrorStates((prevState) => ({
        ...prevState,
        discountDetails: {
          ...prevState.discountDetails,
          discountValue: null,
        },
      }));
      setWidgetsData((prevState) => ({
        ...prevState,
        discountDetails: {
          ...prevState.discountDetails,
          discountValue: 0,
          maxDiscountValue: '',
        },
      }));
    }
  };

  const handleFormValidations = (value: string) => {
    validateDiscountDetails({
      amountType: widgetsData.discountDetails.discountType,
      inputValue: value,
      setErrorStates,
      couponName,
    });
  };

  return (
    <div>
      <FormGroup>
        <div className="form-label"> Discount type</div>
        <div className="form-input">
          <div className="mb-12">
            <Input.Radio
              key={widgetsData.discountDetails.discountType}
              autoRender
              defaultValue={widgetsData.discountDetails.discountType}
              onChange={(e: ChangeEvent<HTMLInputElement>) => {
                handleInputChange(e, 'discountType');
              }}
              name="discountType"
              options={DiscountTypes}
            />
          </div>
          <div className="mb-4">
            <label className="discount-amount-label">
              {widgetsData.discountDetails.discountType === 'fixedAmount'
                ? 'Discount amount'
                : 'Discount percentage'}
            </label>
            {widgetsData.discountDetails.discountType === 'fixedAmount' ? (
              <Input
                name="fixedDiscount"
                type="number"
                addonBefore={<InputIcon className="i-rupee" />}
                className={classList(
                  'w-200',
                  errorStates.discountDetails.discountValue ? 'error-field' : '',
                )}
                defaultValue={widgetsData.discountDetails.discountValue}
                value={widgetsData.discountDetails.discountValue}
                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                  handleInputChange(e, 'discountValue')
                }
                onBlur={(e: ChangeEvent<HTMLInputElement>) => {
                  const value = e.target.value;
                  handleFormValidations(value);
                }}
                onWheel={onWheelPreventChange}
                min="0"
              />
            ) : (
              <div>
                <Input
                  name="percentageDiscount"
                  type="number"
                  className={classList(
                    'w-200',
                    errorStates.discountDetails.discountValue ? 'error-field' : '',
                  )}
                  addonAfter={<InputIcon className="i-offer3" />}
                  defaultValue={widgetsData.discountDetails.discountValue}
                  value={widgetsData.discountDetails.discountValue}
                  onChange={(e: ChangeEvent<HTMLInputElement>) =>
                    handleInputChange(e, 'discountValue')
                  }
                  onBlur={(e: ChangeEvent<HTMLInputElement>) => {
                    const value = e.target.value;
                    handleFormValidations(value);
                  }}
                  onWheel={onWheelPreventChange}
                  min="0"
                  max="100"
                />
              </div>
            )}
            <p className="error-message">{errorStates.discountDetails.discountValue}</p>
          </div>
          {widgetsData.discountDetails.discountType === 'percentageDiscount' && (
            <div className="mt-4">
              <label className="discount-amount-label">Upto (Max Discount)</label>
              <Input
                name="maxDiscount"
                type="number"
                className="w-200"
                defaultValue={widgetsData.discountDetails.maxDiscountValue}
                value={widgetsData.discountDetails.maxDiscountValue}
                onChange={(e: ChangeEvent<HTMLInputElement>) =>
                  handleInputChange(e, 'maxDiscountValue')
                }
                onWheel={onWheelPreventChange}
                addonBefore={<InputIcon className="i-rupee" />}
              />
            </div>
          )}
        </div>
      </FormGroup>
      {couponName !== 'amount_off_order' && (
        <>
          <AddCollectionProductComponent stateObject="discountDetails" couponName={couponName} />
          {widgetsData.discountDetails.discountType !== 'percentageDiscount' && (
            <DiscountDetailsMaxQuantityContainer>
              <div className="form-label">Usage Limit</div>
              <div className="form-input">
                <div className="display-flex">
                  <Input.Check
                    checked={widgetsData.discountDetails.hasLimitedUseagePerOrder}
                    type="checkbox"
                    name="isUnlimitedUsage"
                    onChange={(e: ChangeEvent<HTMLInputElement>) => {
                      setWidgetsData({
                        ...widgetsData,
                        discountDetails: {
                          ...widgetsData.discountDetails,
                          hasLimitedUseagePerOrder: e.target.checked,
                        },
                      });
                    }}
                    autoRender
                  />
                  <CheckboxLabelWithInfo>
                    <span>Only apply discount once per order</span>
                    <i className="i i-info-outline">
                      <Popover theme="dark">
                        <PopoverBody>
                          <div>
                            If not selected, {getCurrencySymbol(merchantCurrency)}
                            {widgetsData.discountDetails.discountValue} will be taken off each
                            eligible item in an order.
                          </div>
                        </PopoverBody>
                      </Popover>
                    </i>
                  </CheckboxLabelWithInfo>
                </div>
              </div>
            </DiscountDetailsMaxQuantityContainer>
          )}
        </>
      )}
    </div>
  );
};

interface AccordianFooterProps {
  couponName: string;
}

const AccordianFooter: React.FC<AccordianFooterProps> = ({ couponName }) => {
  return <MinimumRequirementWidget couponName={couponName} />;
};

interface DiscountDetailsWidgetProps {
  couponName: string;
  merchantCurrency: CurrencyCodeType;
}

const DiscountDetailsWidget: React.FC<DiscountDetailsWidgetProps> = ({
  couponName,
  merchantCurrency,
}) => {
  const { errorStates } = useContext(ModalContext);
  const [isOpen, setIsOpen] = useState(false);

  useEffect(() => {
    setIsOpen((prev) => {
      const hasErrors =
        errorStates.discountDetails &&
        Object.values(errorStates.discountDetails).some((value) => value !== null);

      return hasErrors || prev;
    });
  }, [errorStates.discountDetails]);

  return (
    <div>
      <Accordion
        open={isOpen}
        header={<div>Discount Details</div>}
        body={<AccordionBody couponName={couponName} merchantCurrency={merchantCurrency} />}
        footer={<AccordianFooter couponName={couponName} />}
      />
    </div>
  );
};

export const mapStateToProps = (state) => ({
  merchantCurrency: state.session.user.merchant.currency,
});

export default connect(mapStateToProps)(DiscountDetailsWidget);
