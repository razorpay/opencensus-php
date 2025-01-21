import React from 'react';
import { Box, Link, PlusCircleIcon } from '@razorpay/blade/components';
import { FieldArray, useFormikContext } from 'formik';
import BillingTerminalRow from './BillingTerminalRow';
import { TerminalFormValues } from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';

const BillingTerminals = () => {
  const { values } = useFormikContext<TerminalFormValues>();
  return (
    <Box display="flex" flexDirection="column" gap="spacing.7">
      <FieldArray
        name="billingTerminals"
        render={(arrayHelpers) => (
          <Box display="flex" flexDirection="column" gap="spacing.7">
            {values?.billingTerminals?.map((billingTerminal, index) => (
              <BillingTerminalRow
                key={billingTerminal.id || billingTerminal.tempKey}
                arrayHelpers={arrayHelpers}
                index={index}
                namePrefix={`billingTerminals.${index}`}
                terminalId={billingTerminal.id}
                isActive={billingTerminal.isActive}
              />
            ))}

            <Link
              icon={PlusCircleIcon}
              onClick={() =>
                arrayHelpers.push({
                  name: '',
                  macAddress: '',
                  ipAddress: '',
                  id: '',
                  tempKey: new Date().getTime(),
                })
              }
              isDisabled={values?.billingTerminals?.length >= 30}
            >
              Add Billing Terminal
            </Link>
          </Box>
        )}
      />
    </Box>
  );
};

export default BillingTerminals;
