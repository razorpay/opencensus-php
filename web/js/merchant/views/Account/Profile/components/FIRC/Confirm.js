import React, { useContext } from 'react';
import AsyncButton from 'react-async-button';
import Button from 'common/new-ui/Button';
import FIRCFormContext from './FIRCFormContext';

const Confirm = ({ onConfirm }) => {
  const { formState, handlePrev } = useContext(FIRCFormContext);
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

      <div className="footer-section">
        <AsyncButton
          type="button"
          className="btn btn-primary next-btn m-0"
          text="Confirm"
          pendingText="Updating..."
          onClick={onConfirm}
        />
        <Button.Transparent className="back-btn m-0" onClick={handlePrev}>
          Back
        </Button.Transparent>
      </div>
    </div>
  );
};

export default React.memo(Confirm);
