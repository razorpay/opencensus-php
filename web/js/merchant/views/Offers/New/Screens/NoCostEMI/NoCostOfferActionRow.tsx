import React, { useState, useEffect, useRef, useCallback } from 'react';
import { TextInput, Checkbox } from '@razorpay/blade/components';

import Input from 'common/new-ui/Input';
import { StyledActionRow } from 'merchant/views/Offers/New/Screens/NoCostEMI/Styled';
import {
  EMI_OFFER_TYPES,
  offerPayloadKeys,
  offerStateKeys,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/constants';
import {
  validateMerchantDiscount,
  isMerchantDiscountValid,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import { EMI_OFFER_TYPES as EmiTypes } from 'merchant/views/Offers/New/Screens/NoCostEMI/types';

const EMITenureAction = ({
  plan,
  onOffersChange,
  offersData,
  values,
  handleChange,
  errors,
}): JSX.Element => {
  const [emiTypeSelected, setEmiTypeSelected] = useState<EmiTypes>(EmiTypes.NONE);
  const [merchantInterest, setMerchantInterest] = useState<number>(0);
  const merchantDiscountRef = useRef<HTMLInputElement>(null);

  const updateOfferState = useCallback(
    ({
      key,
      tenure,
      value,
      isValid = true,
    }: {
      key: string;
      tenure: number;
      value?: string | number;
      isValid?: boolean;
    }) => {
      const offers = offersData || {};
      if (key === offerStateKeys.TENURE && offers[tenure]) {
        delete offers[tenure];
      } else if (key === offerStateKeys.TENURE && !offers[tenure]) {
        offers[tenure] = {
          tenure,
          valid: isValid,
        };
      } else {
        offers[tenure] = {
          ...(offers[tenure] || {}),
          tenure,
          valid: isValid,
          [key]: value,
        };
      }

      onOffersChange(offers);
    },
    [offersData, onOffersChange],
  );

  useEffect(() => {
    if (offersData && offersData[plan.duration]) {
      setEmiTypeSelected(offersData[plan.duration].offer_type || EmiTypes.NONE);
      updateOfferState({
        key: offerStateKeys.MERCHANT_DISCOUNT,
        value: 0,
        tenure: plan.duration,
      });
    } else {
      setEmiTypeSelected(EmiTypes.NONE);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [offersData]);

  useEffect(() => {
    if (emiTypeSelected === EmiTypes.LOW_COST) {
      merchantDiscountRef.current?.focus();
    }
  }, [emiTypeSelected]);

  /**
   * Handle duration checkbox select
   * @param {number} duration
   */
  const handleEmiDurationSelect = useCallback(
    (duration: number, handleChange) => (e) => {
      let emi_durations: number[] = [...(values.emi_durations ? values.emi_durations : [])];

      if (!e.isChecked) {
        emi_durations = emi_durations.filter((ele) => ele != duration);
      }

      if (e.isChecked && !emi_durations.includes(duration)) {
        emi_durations.push(duration);
      }

      handleChange({
        target: {
          name: offerPayloadKeys.emi_durations,
          value: emi_durations,
        },
      });
      updateOfferState({
        key: offerStateKeys.TENURE,
        tenure: duration,
      });
    },
    [values.emi_durations, updateOfferState],
  );

  /**
   * Helper function to calculate discount borne by customer
   * Note: Will be calculated by doung merchant_payback - merchant borne interest
   */
  const getCustomerDiscount = useCallback(() => {
    if (emiTypeSelected === EmiTypes.NO_COST) {
      return '0';
    } else if (emiTypeSelected === EmiTypes.NONE) {
      return plan.merchant_payback;
    }
    return (+plan.merchant_payback - merchantInterest).toFixed(2);
  }, [emiTypeSelected, merchantInterest, plan.merchant_payback]);

  const getPlaceholder = useCallback(() => {
    if (emiTypeSelected === EmiTypes.NO_COST) {
      return plan.merchant_payback;
    } else if (emiTypeSelected === EmiTypes.LOW_COST) {
      return '';
    }
    return '0';
  }, [emiTypeSelected, plan.merchant_payback]);

  /**
   * Handle Emi Offer Type Selection
   */
  const handleOfferEmiTyeSelection = useCallback(
    (duration: number) => (e: React.ChangeEvent<HTMLInputElement>) => {
      const { target } = e;
      setEmiTypeSelected(target.value as EmiTypes);
      let low_cost_tenures = values.low_cost_emi || [];
      console.log('merchant.........Interest', merchantInterest);
      if (target.value === EmiTypes.LOW_COST) {
        low_cost_tenures.push({
          discount_to_avail: {
            discount_percentage: +merchantInterest * 100,
            applicable_on: null,
            applicable_values: null,
          },
          tenure: duration,
          issuer: values.issuer,
        });
      } else {
        // If no cost offer is selected for the tenure, filter out the tenure from low cost payload
        low_cost_tenures = low_cost_tenures.filter((item) => item.tenure !== duration);
      }

      handleChange({
        target: {
          name: offerPayloadKeys.low_cost_emi,
          value: low_cost_tenures,
        },
      });

      updateOfferState({
        key: offerStateKeys.OFFER_TYPE,
        tenure: duration,
        value: target.value,
      });
    },
    [values.low_cost_emi, values.issuer, handleChange, updateOfferState, merchantInterest],
  );

  // If the entered payback is same as merchant payback make the offer type as no cost EMI
  const handleOnBlur = useCallback(
    (duration: number) => (e) => {
      const { value } = e;
      if (+value === +plan.merchant_payback) {
        setEmiTypeSelected(EmiTypes.NO_COST);
        let low_cost_tenures = values.low_cost_emi || [];
        low_cost_tenures = low_cost_tenures.filter((item) => item.tenure !== duration);
        handleChange({
          target: {
            name: offerPayloadKeys.low_cost_emi,
            value: low_cost_tenures,
          },
        });
        updateOfferState({
          key: offerStateKeys.OFFER_TYPE,
          tenure: duration,
          value: EmiTypes.NO_COST,
          isValid: true,
        });
      } else {
        setMerchantInterest(Number(value));
        const low_cost_tenures = values.low_cost_emi;

        if (low_cost_tenures?.length) {
          low_cost_tenures.forEach((offer) => {
            if (offer.tenure === duration) {
              offer.discount_to_avail.discount_percentage = Number(value) * 100;
            }
          });

          // Update Form Data for the offer creation payload
          handleChange({
            target: {
              name: offerPayloadKeys.low_cost_emi,
              value: low_cost_tenures,
            },
          });

          // Update select tenures offer state
          updateOfferState({
            key: offerStateKeys.MERCHANT_DISCOUNT,
            tenure: duration,
            value,
            isValid: isMerchantDiscountValid(+value, +plan.merchant_payback),
          });
        }
      }
    },
    [plan.merchant_payback, values.low_cost_emi, handleChange, updateOfferState],
  );

  const handleOnChange = useCallback(
    (duration: number) => (e) => {
      const { value } = e;

      const low_cost_tenures = values.low_cost_emi;

      if (low_cost_tenures?.length) {
        low_cost_tenures.forEach((offer) => {
          if (offer.tenure === duration) {
            offer.discount_to_avail.discount_percentage = Number(value) * 100;
          }
        });

        // Update Form Data for the offer creation payload
        handleChange({
          target: {
            name: offerPayloadKeys.low_cost_emi,
            value: low_cost_tenures,
          },
        });

        // Update select tenures offer state
        updateOfferState({
          key: offerStateKeys.MERCHANT_DISCOUNT,
          tenure: duration,
          value,
          isValid: isMerchantDiscountValid(+value, +plan.merchant_payback),
        });
      }
    },
    [values.low_cost_emi, handleChange, updateOfferState, plan.merchant_payback],
  );

  const merchantBorneDiscountError =
    emiTypeSelected === EmiTypes.LOW_COST
      ? validateMerchantDiscount(+merchantInterest, plan).validationText
      : '';

  // Then, update the errors object accordingly
  useEffect(() => {
    errors.merchant_borne_discount = merchantBorneDiscountError;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [merchantBorneDiscountError]);
  return (
    <StyledActionRow data-testid="offer-action-row">
      <div className="tenure offer-body-row">
        <Checkbox
          isChecked={values.emi_durations?.includes(plan.duration)}
          onChange={(value) => handleEmiDurationSelect(plan.duration, handleChange)(value)}
        >
          {`${plan.duration} Months`}
        </Checkbox>
      </div>
      <div className="interest offer-body-row">{plan.merchant_payback} %</div>
      <div className="offer-body-row">
        <Input.Select
          data-testid="offer-type-select"
          placeholder="Select"
          options={EMI_OFFER_TYPES}
          disabled={!values.emi_durations?.includes(plan.duration)}
          onChange={handleOfferEmiTyeSelection(plan.duration)}
          value={emiTypeSelected}
        />
      </div>
      <div className="offer-body-row">
        <TextInput
          placeholder={getPlaceholder()}
          name="merchant_borne_discount"
          isDisabled={emiTypeSelected !== EmiTypes.LOW_COST}
          onBlur={handleOnBlur(plan.duration)}
          onChange={handleOnChange(plan.duration)}
          label=""
          errorText={merchantBorneDiscountError}
          validationState={
            emiTypeSelected === EmiTypes.LOW_COST
              ? validateMerchantDiscount(+merchantInterest, plan).validation
              : 'none'
          }
          ref={merchantDiscountRef}
          helpText={emiTypeSelected === EmiTypes.LOW_COST ? 'Please enter interest %' : ''}
        />
      </div>
      <div className="offer-body-row">
        <TextInput
          name="customer_borne_discount"
          placeholder="0.00"
          label=""
          isDisabled={true}
          value={getCustomerDiscount()}
        />
      </div>
    </StyledActionRow>
  );
};

export default EMITenureAction;
