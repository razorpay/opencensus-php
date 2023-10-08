import React from 'react';
import { Box } from '@razorpay/blade/components';
import Input from 'common/new-ui/Input';
import Label from './Label';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';

const DeliveryDescription = (): JSX.Element => {
  const { values, setValue } = useFormContext();
  return (
    <>
      <Label
        value="Description"
        error={values.description.error}
        showTooltip={true}
        tooltipContent="Provide details about this shipping method to help customers understand its features and benefits."
      />
      <Box width="40%">
        <Input
          placeholder="Enter description"
          value={values.description.value}
          onChange={(e) => setValue('description', e.target.value)}
        />
      </Box>
    </>
  );
};

export default DeliveryDescription;
