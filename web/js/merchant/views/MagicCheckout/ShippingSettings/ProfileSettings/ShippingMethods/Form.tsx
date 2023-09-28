import React from 'react';

import { Box } from '@razorpay/blade/components';

import { Separator } from 'merchant/views/MagicCheckout/ShippingSettings/styles';
import { FormWrapper } from './styles';
import { StandardDeliveryInputs } from './constants';

const Form = (): JSX.Element => {
  return (
    <FormWrapper>
      <Box display="flex" flexDirection="column" gap="spacing.5">
        {StandardDeliveryInputs.map((Inputs, index) => (
          <React.Fragment key={index}>
            {Inputs.map((Component: any, sindex) => {
              return (
                <Box display="flex" gap="spacing.5" key={`input-${sindex}`}>
                  <Component />
                </Box>
              );
            })}
            {index !== StandardDeliveryInputs.length - 1 && <Separator margin={0} />}
          </React.Fragment>
        ))}
      </Box>
    </FormWrapper>
  );
};

export default Form;
