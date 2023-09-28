import React from 'react';
import Input from 'common/new-ui/Input';
import Label from './Label';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';

export const OPTIONS = [
  {
    label: 'Standard Delivery',
    name: 'standard_delivery',
  },
];

const DeliveryType = (): JSX.Element => {
  const { values, setValue } = useFormContext();
  const handleChange = (e) => {
    setValue('delivery_type', e.target.value);
  };
  return (
    <>
      <Label value="Delivery Type" error={values.delivery_type.error} />
      <Input.Select
        value={values.delivery_type.value}
        size="small"
        name="engine"
        options={OPTIONS}
        className="settings-select"
        onChange={handleChange}
      />
    </>
  );
};

export default DeliveryType;
