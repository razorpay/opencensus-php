import React from 'react';
import Label from './Label';
import Input from 'common/new-ui/Input';
import { Box } from '@razorpay/blade/components';

import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';

const Rate = (): JSX.Element => {
  const { values, setValue } = useFormContext();

  return (
    <>
      <Label value="Rate" error={values.fee.error} />
      <Box>
        <Box width="50%">
          <Input
            addonBefore="₹"
            type="number"
            value={values.fee.value}
            placeholder="Enter rate"
            onChange={(e) => setValue('fee', +e.target.value)}
          />
        </Box>
        <Box marginTop="spacing.4">
          <Input.Check
            fieldLabel="COD availability"
            defaultChecked={values.allow_cod.value}
            checked={values.allow_cod.value}
            onChange={(e) => setValue('allow_cod', e.target.checked)}
          />
        </Box>
      </Box>
    </>
  );
};

export default Rate;
