import React from 'react';
import Input from 'common/new-ui/Input';
import Label from './Label';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';

const DeliveryName = (): JSX.Element => {
  const { values, setValue } = useFormContext();
  return (
    <>
      <Label
        value="Delivery name"
        error={values.name.error}
        showTooltip={true}
        tooltipContent="Define the shipping method names your customers will see at checkout. Ensure clarity and precision to set clear delivery expectations."
      />
      <Input
        placeholder="Enter name"
        value={values.name.value}
        onChange={(e) => setValue('name', e.target.value)}
      />
    </>
  );
};

export default DeliveryName;
