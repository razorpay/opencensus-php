import React from 'react';

// components
import AsyncButton from 'react-async-button';
import Button from 'common/new-ui/Button';

// prop types
type ItemType = { label: string; value: string; description: string };

interface HSCodeConfirmProps {
  selected: { label: string; value: string; description: string } | null;
  onConfirm: () => void;
  onBack: () => void;
}

const HSCodeConfirm = ({ selected, onConfirm, onBack }: HSCodeConfirmProps) => {
  return (
    <div className="purpose-code-container hs-code-container">
      <div className="confirmation-content">
        {selected && (
          <p className="label-text">
            You have selected <b>{selected.label} </b> - <b>{selected.description}</b>
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
        <Button.Transparent className="back-btn m-0" onClick={onBack}>
          Back
        </Button.Transparent>
      </div>
    </div>
  );
};

export default React.memo(HSCodeConfirm);
