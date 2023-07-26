import React, { useContext } from 'react';
import { Button, Box } from '@razorpay/blade/components';
import FIRCFormContext from './FIRCFormContext';

const Confirm = ({ onConfirm }) => {
  const { formState, handlePrev, isSubmitting } = useContext(FIRCFormContext);
  const { purpose_code, purpose_code_desc, iec_code } = formState;

  return (
    <div className="purpose-code-container">
      <div className="confirmation-content">
        <p className="label-text">
          You have selected <b>{purpose_code} </b> - <b>{purpose_code_desc}</b>
        </p>
        <p className="label-text">
          Make sure you select the correct purpose code for the nature of foreign transactions.
        </p>
        {iec_code && (
          <p className="label-text">
            Entered IEC Code - <b>{iec_code}</b>
          </p>
        )}
      </div>

      <Box
        display="flex"
        gap="1rem"
        paddingX="1.5rem"
        paddingBottom="spacing.6"
        className="footer-section"
      >
        <Button onClick={handlePrev} variant="secondary" isFullWidth>
          Select Another
        </Button>
        <Button onClick={onConfirm} isLoading={isSubmitting} isFullWidth>
          Confirm
        </Button>
      </Box>
    </div>
  );
};

export default React.memo(Confirm);
