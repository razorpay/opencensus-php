import React, { useState } from 'react';
import { Box, CloseIcon, Link, Text } from '@razorpay/blade/components';

import Input from 'common/new-ui/Input';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import { IconButton } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/styles';
import { ShippingFeeRule } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/types';
import {
  AMOUNT_OPTIONS,
  RATE_OPTIONS,
  RATE_TYPES,
} from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import { Separator, SettingsWrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';

import Label from './Label';

const AmountCondition = ({ lt, gte, handleChange }) => {
  return (
    <>
      <Text size="small">is</Text>
      <Input.Select
        value="range"
        size="small"
        name="amount"
        options={AMOUNT_OPTIONS}
        className="shipping-slabs-select"
      />
      <Text size="small">Min.</Text>
      <Input
        value={gte}
        addonBefore="₹"
        type="number"
        name="amount.gte"
        onChange={(e) => handleChange(RATE_TYPES.AMOUNT, 'gte', +e.target.value)}
      />
      <Text size="small">Max.</Text>
      <Input
        value={lt}
        addonBefore="₹"
        type="number"
        name="amount.lt"
        onChange={(e) => handleChange(RATE_TYPES.AMOUNT, 'lt', +e.target.value)}
      />
    </>
  );
};

const WeigthCondition = ({ lt, gte, handleChange }) => {
  return (
    <>
      <Text size="small">is</Text>
      <Input.Select
        value="range"
        size="small"
        name="weight"
        options={AMOUNT_OPTIONS}
        className="shipping-slabs-select"
      />
      <Text size="small">Min.</Text>
      <Input
        value={gte}
        addonAfter="kg"
        type="number"
        name="weight.gte"
        onChange={(e) => handleChange(RATE_TYPES.WEIGHT, 'gte', +e.target.value)}
      />
      <Text size="small">Max.</Text>
      <Input
        value={lt}
        addonAfter="kg"
        type="number"
        name="weight.lt"
        onChange={(e) => handleChange(RATE_TYPES.WEIGHT, 'lt', +e.target.value)}
      />
    </>
  );
};

const ShippingSlab = (): JSX.Element => {
  const { values, setValue } = useFormContext();
  const defaultAddedConditions = Object.keys(values.fee_rules.value);
  values.fee_rules.value = values.fee_rules.value as ShippingFeeRule;
  const [addedConditions, setAddedConditions] = useState<string[]>(defaultAddedConditions);

  const onConditionTypeChange = (e, index) => {
    const val = e.target.value;

    addedConditions[index] = val;
    if (addedConditions.length > 1) {
      if (index === 0) {
        addedConditions[1] = val === RATE_TYPES.AMOUNT ? RATE_TYPES.WEIGHT : RATE_TYPES.AMOUNT;
      } else addedConditions[0] = val === RATE_TYPES.AMOUNT ? RATE_TYPES.WEIGHT : RATE_TYPES.AMOUNT;
    }
    const value = {
      [val]: {
        gte: 0,
        lt: 100,
      },
    };
    setValue('fee_rules', value);
    setAddedConditions([...addedConditions]);
  };

  const handleAddCondition = () => {
    let newCondition = RATE_TYPES.AMOUNT;
    if (addedConditions.includes(RATE_TYPES.AMOUNT)) {
      newCondition = RATE_TYPES.WEIGHT;
    }
    const existingValue = values.fee_rules.value;
    const val = {
      ...existingValue,
      [newCondition]: {
        gte: 0,
        lt: 1000,
      },
    };
    setValue('fee_rules', val);
    setAddedConditions([...addedConditions, newCondition]);
  };

  const handleDeleteCondition = () => {
    const existingValue = values.fee_rules.value;
    if (addedConditions[1] === RATE_TYPES.AMOUNT) {
      delete existingValue.amount;
    } else {
      delete existingValue.weight;
    }
    setValue('fee_rules', existingValue);
    setAddedConditions(addedConditions.splice(0, 1));
  };

  const handleChange = (type, key, value) => {
    const existingValue = values.fee_rules.value;
    if (type === RATE_TYPES.AMOUNT) {
      const existingAmount = existingValue.amount || {};
      const val = {
        ...existingValue,
        amount: {
          ...existingAmount,
          [key]: value,
        },
      };
      setValue('fee_rules', val);
    } else {
      const existingWeight = existingValue.weight || { gte: 0, lt: 10 };
      const val = {
        ...existingValue,
        weight: {
          ...existingWeight,
          [key]: value,
        },
      };
      setValue('fee_rules', val);
    }
  };

  return (
    <>
      <Label value="Shipping Slab" error={values.fee_rules.error} />
      <Box flex="1">
        <SettingsWrapper>
          {addedConditions.map((condition, index) => (
            <Box key={condition}>
              <Box width="50%" display="flex" alignItems="center" gap="spacing.5">
                <Text size="small">If</Text>
                <Input.Select
                  value={condition}
                  size="small"
                  name="engine"
                  options={RATE_OPTIONS}
                  className="shipping-slabs-select"
                  onChange={(e) => onConditionTypeChange(e, index)}
                />
                {index === 1 && (
                  <IconButton onClick={handleDeleteCondition} data-testid="deleteIcon">
                    <CloseIcon size="medium" color="feedback.icon.negative.intense" />
                  </IconButton>
                )}
              </Box>
              <Box marginY="spacing.4" display="flex" alignItems="center" gap="spacing.5">
                {condition === RATE_TYPES.AMOUNT ? (
                  <AmountCondition
                    handleChange={handleChange}
                    gte={values.fee_rules.value?.amount?.gte}
                    lt={values.fee_rules.value?.amount?.lt}
                  />
                ) : (
                  <WeigthCondition
                    gte={values.fee_rules.value?.weight?.gte}
                    lt={values.fee_rules.value?.weight?.lt}
                    handleChange={handleChange}
                  />
                )}
              </Box>
            </Box>
          ))}
        </SettingsWrapper>
        <Separator margin={0} />

        {addedConditions.length < 2 ? (
          <Link marginTop="spacing.4" variant="button" onClick={handleAddCondition}>
            + Add condition
          </Link>
        ) : null}
      </Box>
    </>
  );
};

export default ShippingSlab;
