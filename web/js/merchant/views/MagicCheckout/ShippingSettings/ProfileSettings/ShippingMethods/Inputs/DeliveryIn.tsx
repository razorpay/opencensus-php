import React from 'react';
import Input from 'common/new-ui/Input';
import Label from './Label';
import { Box } from '@razorpay/blade/components';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';

const DeliveryIn = (): JSX.Element => {
  const { values, setValue } = useFormContext();

  return (
    <>
      <Label
        required={false}
        value="Delivery in"
        error={values.etd.error}
        showTooltip={true}
        tooltipContent="Indicate the typical number of days it takes for a customer to receive the product once shipped using this delivery method. This will be shown on Magic to help the customer choose the shipping method."
      />
      <Box width="40%">
        <Input
          placeholder="Enter estimated timeline"
          value={values.etd.value}
          onChange={(e) => setValue('etd', e.target.value)}
        />
      </Box>
    </>
  );
};

export default DeliveryIn;
