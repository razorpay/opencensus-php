import React from 'react';

import {
  Box,
  Divider,
  Text,
  Alert,
  Checkbox,
  TextInput,
  RupeeIcon,
} from '@razorpay/blade/components';

import { getFormattedAmountNew } from 'common/utils/rzp-utils';

import { useFormContext } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Context';

import {
  GENERAL_FEE_INFO,
  COD_FEE_INFO,
  GTE,
  LT,
  COD_ORDER_INFO,
  COD_ON_ALL_ORDERS,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/constants';

export const Form: React.FC = () => {
  const { formData, updateFormData, formErrors } = useFormContext();

  const handlePaymentMethodChange = (e) => {
    const { name, checked } = e.event.target;
    updateFormData(name, checked);
  };

  const handleSlabChange = (e) => {
    const { name, value } = e;
    if (isNaN(value)) return;

    const existingFeeRules = formData?.cod_fee_rules ?? {};
    const newFeeRules = {
      ...existingFeeRules,
      amount: {
        ...(existingFeeRules?.amount ?? {}),
        [name]: value === '' ? null : value,
      },
    };
    updateFormData('cod_fee_rules', newFeeRules);
  };

  const getCODOrderRange = (range: string) => {
    const value = formData?.cod_fee_rules?.amount?.[range] ?? null;
    return value === null ? '' : value;
  };

  const getCODRangeInfo = () => {
    const gte = getCODOrderRange(GTE);
    const lt = getCODOrderRange(LT);
    if (!(gte || lt)) return COD_ON_ALL_ORDERS;
    return `COD will be available for carts between ₹${getCODOrderRange(
      GTE,
    )} and ₹${getCODOrderRange(LT)}`;
  };

  return (
    <>
      <Box>
        <Text marginY="spacing.4" weight="semibold">
          Allowed Payment Method
        </Text>
        <Box display="flex" alignItems="center">
          <Checkbox
            value="cod"
            name="allow_cod"
            isChecked={formData?.allow_cod}
            marginRight="spacing.11"
            onChange={handlePaymentMethodChange}
            size="medium"
          >
            COD
          </Checkbox>
          <Checkbox
            value="prepaid"
            name="allow_prepaid"
            isChecked={formData?.allow_prepaid}
            onChange={handlePaymentMethodChange}
          >
            Prepaid
          </Checkbox>
        </Box>
        {formErrors?.paymentMethod && (
          <Alert
            marginY="spacing.6"
            color="negative"
            isDismissible={false}
            description={formErrors.paymentMethod}
            isFullWidth
          />
        )}
      </Box>
      {formData?.allow_cod && (
        <>
          <Divider marginY="spacing.6" />
          <Text marginY="spacing.4" weight="semibold">
            COD Order Range
          </Text>
          <Alert
            marginY="spacing.6"
            color="neutral"
            isDismissible={false}
            description={COD_ORDER_INFO}
            isFullWidth
          />
          <Box display="flex">
            <Box marginRight="spacing.7">
              <TextInput
                label="Min Order Value"
                name={GTE}
                value={getCODOrderRange(GTE)}
                onChange={handleSlabChange}
                icon={RupeeIcon}
                type="number"
                placeholder="Ex:0"
              />
            </Box>
            <Box>
              <TextInput
                label="Max Order Value"
                name={LT}
                value={getCODOrderRange(LT)}
                onChange={handleSlabChange}
                icon={RupeeIcon}
                type="number"
                placeholder="Ex:1000"
              />
            </Box>
          </Box>
          {formErrors?.codSlabs ? (
            <Alert
              marginY="spacing.6"
              color="negative"
              isDismissible={false}
              description={formErrors.codSlabs}
              isFullWidth
            />
          ) : (
            <Alert
              marginY="spacing.6"
              color="positive"
              isDismissible={false}
              description={getCODRangeInfo()}
              isFullWidth
            />
          )}
        </>
      )}
      <Divider marginY="spacing.6" />
      <Box>
        <Text marginY="spacing.4" weight="semibold" size="large">
          Method Rate
        </Text>
        <Text marginY="spacing.4" weight="semibold" size="large">
          {getFormattedAmountNew(formData?.fee, true)}
        </Text>
        <Alert
          marginY="spacing.6"
          color="neutral"
          description={formData?.allow_cod ? COD_FEE_INFO : GENERAL_FEE_INFO}
          emphasis="subtle"
          isDismissible={false}
          isFullWidth
        />
      </Box>
    </>
  );
};
