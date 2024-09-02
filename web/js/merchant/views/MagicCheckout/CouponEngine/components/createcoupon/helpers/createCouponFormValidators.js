import moment from 'moment';

import { getCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';

let isFormValid = true;

const setErrorState = ({ prevState, fieldName, subFieldName, errorMessage }) => {
  return {
    ...prevState,
    [fieldName]: {
      ...prevState[fieldName],
      [subFieldName]: errorMessage,
    },
  };
};

const createCouponDetailsFields = {
  code: async (val, flowName = 'created') => {
    if (val === '') {
      return 'This field is required';
    }

    if (['created', 'duplicate'].includes(flowName) && val.length !== 0) {
      try {
        const res = await getCoupon(val);
        if (res.data.coupons?.length > 0 && res.data.coupons[0].status !== 'expired') {
          return 'Coupon code already exists';
        }
      } catch (error) {
        return 'We are facing an issue in validating the coupon name. Please try again later.';
      }
    }

    if (val.length > 100) {
      return 'Coupon code should be less than 100 characters';
    }

    return null;
  },
  description: (val) => {
    if (val === '') {
      return 'This field is required';
    }

    if (val.length > 200) {
      return 'Description should be less than 200 characters';
    }

    return null;
  },
};

// this function is called from the globalValidator function and the coupon details widget. This function validates the code and description field.
export const couponDetailsValidator = async ({ fieldName, value, setErrorStates, flowName }) => {
  const fieldValidator = createCouponDetailsFields[fieldName];
  let errorMessage = null;

  try {
    errorMessage = await fieldValidator(value, flowName);
  } catch (error) {
    console.error('Error during async validation:', error);
  }

  setErrorStates((prevState) =>
    setErrorState({ prevState, fieldName: 'couponDetails', subFieldName: fieldName, errorMessage }),
  );

  if (errorMessage) {
    isFormValid = false;
  }
};

// this function is called from the globalValidator function and the discount details widget. This function validates the discountValue field.
export function validateDiscountDetails({ amountType, inputValue, setErrorStates, couponName }) {
  let errorMessage = null;
  if (amountType === 'fixedAmount' && !Number(inputValue)) {
    errorMessage = 'Fixed amount is required.';
  } else if (amountType === 'percentageDiscount') {
    if (!Number(inputValue) || isNaN(inputValue) || inputValue < 0 || inputValue > 100) {
      errorMessage = 'Percentage must be a number between 0 and 100.';
    }
  }

  const targetState =
    couponName === 'amount_off_order' || couponName === 'amount_off_products'
      ? 'discountDetails'
      : couponName === 'bulk_order'
      ? 'bulkDiscountDetails'
      : 'discountOffered';

  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName: targetState,
      subFieldName: 'discountValue',
      errorMessage,
    }),
  );

  if (errorMessage) {
    isFormValid = false;
  }
}

// this function is called from the globalValidator function and the coupon date time widget. This function validates the couponValidity field.
export function validateCouponDateTime({
  startDateTime,
  isEndDateRequired,
  endDateTime,
  setErrorStates,
  flowName,
  couponStatus,
}) {
  let errorMessage = null;
  if (startDateTime < new Date().toISOString() && ['created', 'duplicate'].includes(flowName)) {
    errorMessage = 'Start date and time should be greater than the current date and time.';
  } else if (isEndDateRequired && !endDateTime) {
    errorMessage = 'End date and time is required.';
  } else if (startDateTime && isEndDateRequired && startDateTime > endDateTime) {
    errorMessage = 'End date and time should be greater than the start date and time.';
  } else if (couponStatus === 'created' && startDateTime < new Date().toISOString()) {
    errorMessage = 'Start date and time should be greater than the current date and time.';
  }

  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName: 'couponValidity',
      subFieldName: 'couponTime',
      errorMessage,
    }),
  );

  if (errorMessage) {
    isFormValid = false;
  }
}

// this function is called from the globalValidator function and all the discountedItemsList componnet. This function validates the discountedItemsList field.
export function validateDiscountItems({ subFieldName, fieldName, value, setErrorStates }) {
  if (!value.length) {
    setErrorStates((prevState) =>
      setErrorState({
        prevState,
        fieldName,
        subFieldName,
        errorMessage:
          'Please enter the products/collections on which the coupon should be applicable',
      }),
    );
    isFormValid = false;
    return;
  }
  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName,
      subFieldName,
      errorMessage: null,
    }),
  );
  isFormValid = true;
}

export const isPositiveWholeNumber = (value) => {
  const positiveWholeNumberRegex = /^[1-9]\d*$/;
  return positiveWholeNumberRegex.test(value);
};

export const validateMaximumBudgetRestriction = (
  value,
  isBudgetRestrictionEnabled,
  setErrorStates,
) => {
  let errorMsg = null;

  if (isNaN(value)) errorMsg = 'Please enter a valid number';
  //Value cannot be empty and also cannot to be < 1
  else if (isBudgetRestrictionEnabled && !isPositiveWholeNumber(value))
    errorMsg = 'Value is required and should be greater than 0 (Decimal Points not allowed)';
  //Value can be empty but cannot be < 1
  else if (value !== '' && !isPositiveWholeNumber(value))
    errorMsg = 'Value is optional but should be greater than 0 (Decimal Points not allowed)';

  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName: 'couponValidity',
      subFieldName: 'maximumBudget',
      errorMessage: errorMsg,
    }),
  );
};

export const validateUsageRestrictionOnCheckbox = ({
  isUsageRestrictionEnabled,
  usageRestriction,
  setErrorStates,
}) => {
  let errorMessage = null;

  if (
    isUsageRestrictionEnabled &&
    !(usageRestriction?.isRestrictedTotalUsage || usageRestriction?.isLimitedUsage)
  ) {
    errorMessage = 'Please set atleast 1 coupon usage restriction';
  }

  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName: 'usageRestriction',
      subFieldName: 'enforceUsageRestriction',
      errorMessage,
    }),
  );

  if (errorMessage) {
    isFormValid = false;
  } else {
    isFormValid = true;
  }
};

export function validateUsageRestrictionOnCount({ usageRestriction, setErrorStates }) {
  let errorMessageTotalUsage = null;
  let errorMessageLimitedUsage = null;

  if (usageRestriction?.isRestrictedTotalUsage && !isPositiveWholeNumber(usageRestriction?.total)) {
    errorMessageTotalUsage = 'Value should be greater than 0 (Decimal Points not allowed)';
  }
  if (usageRestriction?.isLimitedUsage && !isPositiveWholeNumber(usageRestriction?.maxUsage)) {
    errorMessageLimitedUsage = 'Value should be greater than 0 (Decimal Points not allowed)';
  }

  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName: 'usageRestriction',
      subFieldName: 'total',
      errorMessage: errorMessageTotalUsage,
    }),
  );

  setErrorStates((prevState) =>
    setErrorState({
      prevState,
      fieldName: 'usageRestriction',
      subFieldName: 'maxUsage',
      errorMessage: errorMessageLimitedUsage,
    }),
  );

  if (errorMessageTotalUsage || errorMessageLimitedUsage) {
    isFormValid = false;
  } else {
    isFormValid = true;
  }
}

// this is a helper function which generates the fields to validate based on the coupon name. This function is called from the globalValidator function.
const generateFieldsToValidate = (couponName) => {
  let specificValidators = {};
  const commonFields = {
    code: {
      validator: couponDetailsValidator,
      fieldName: 'couponDetails',
    },
    description: {
      validator: couponDetailsValidator,
      fieldName: 'couponDetails',
    },
    discountValue: {
      validator: validateDiscountDetails,
    },
    couponValidity: {
      validator: validateCouponDateTime,
    },
    usageRestriction: {
      validator: validateUsageRestrictionOnCount,
    },
  };

  const fieldMapping = {
    amount_off_order: 'discountDetails',
    amount_off_products: 'discountDetails',
    buyx_gety: 'discountOffered',
    bulk_order: 'bulkDiscountDetails',
  };

  const fieldMappingForDiscountedItemsList = {
    amount_off_products: 'discountDetails',
    buyx_gety: 'productsPurchased',
    bulk_order: 'productsPurchased',
  };

  // adding a validation for discountedItemsList
  const couponsWithDiscountedItemsList = ['amount_off_products', 'bulk_order', 'buyx_gety'];

  if (couponsWithDiscountedItemsList.includes(couponName)) {
    specificValidators = {
      ...specificValidators,
      discountedItemsList: {
        fieldName: fieldMappingForDiscountedItemsList[couponName],
        validator: validateDiscountItems,
      },
    };
  }

  // adding validation for discountOffered in case of buyx_gety
  if (couponName === 'buyx_gety') {
    specificValidators = {
      ...specificValidators,
      discountOfferedList: {
        fieldName: 'discountOffered',
        validator: validateDiscountItems,
      },
    };
  }

  // in case of free_shipping don't need to validate the discountValue field, because its always 100%. And as of now we dont have any specific validations for free_shipping coupon and no discountValue is there - all these things will come post MVP so for now just returning the commonFields.
  if (couponName === 'free_shipping') {
    delete commonFields.discountValue;
    return {
      ...commonFields,
    };
  }

  return {
    ...commonFields,
    ...specificValidators,
    discountValue: {
      ...commonFields.discountValue,
      fieldName: fieldMapping[couponName] || 'discountOffered',
    },
  };
};

// this function is called from the parent component of the create coupon form, i.e. CreateCouponForm. This function is called on form submit and it validates all the fields of the form.
export const globalValidator = async ({
  couponName,
  widgetsData,
  setErrorStates,
  flowName = 'created',
}) => {
  const promises = [];
  const fieldsToValidate = generateFieldsToValidate(couponName);
  isFormValid = true;

  Object.keys(fieldsToValidate).forEach((fieldName) => {
    const { validator, fieldName: value } = fieldsToValidate[fieldName];
    if (value === 'couponDetails') {
      promises.push(
        validator({
          fieldName,
          value: widgetsData[value][fieldName],
          setErrorStates,
          couponName,
          flowName,
        }),
      );
    }

    if (fieldName === 'discountValue') {
      if (couponName === 'buyx_gety') {
        if (widgetsData[value].discountType === 'free') {
          return;
        }
      }

      let amountType = 'percentageDiscount';

      if (value === 'discountDetails') {
        amountType = widgetsData[value].discountType;
      } else {
        amountType = widgetsData[value].discountSubType;
      }

      promises.push(
        validator({
          amountType,
          inputValue: widgetsData[value][fieldName],
          setErrorStates,
          couponName,
        }),
      );
    }

    if (fieldName === 'discountedItemsList') {
      promises.push(
        validator({
          subFieldName: fieldName,
          fieldName: value,
          value: widgetsData[value][fieldName],
          setErrorStates,
        }),
      );
    }

    if (fieldName === 'discountOfferedList') {
      promises.push(
        validator({
          subFieldName: 'discountedItemsList',
          fieldName: 'discountOffered',
          value: widgetsData.discountOffered.discountedItemsList,
          setErrorStates,
        }),
      );
    }

    if (fieldName === 'couponValidity') {
      promises.push(
        validator({
          startDateTime: moment(
            `${widgetsData.couponValidity.startDate} ${widgetsData.couponValidity.startTime}`,
            'YYYY-M-D h:m a',
          ).toISOString(),
          isEndDateRequired: widgetsData.couponValidity.isLimitedUsage,
          endDateTime: moment(
            `${widgetsData.couponValidity.endDate} ${widgetsData.couponValidity.endTime}`,
            'YYYY-M-D h:m a',
          ).toISOString(),
          setErrorStates,
          flowName,
          couponStatus: widgetsData.status,
        }),
      );
    }

    if (fieldName === 'usageRestrictions') {
      promises.push(
        validator({
          usageRestriction: widgetsData?.usageRestriction,
          setErrorStates,
        }),
      );
    }
  });

  // reason for pushing everything in promises is to make sure that all the validations are done before returning the isFormValid flag specially the coupon name validation because it makes a call to the backend and then form is submitted.
  await Promise.all(promises);

  return isFormValid;
};
