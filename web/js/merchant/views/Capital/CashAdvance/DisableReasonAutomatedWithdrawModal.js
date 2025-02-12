import React, { useState } from 'react';
import Input from 'common/new-ui/Input';
import { AUTOMATED_WITHDRAWAL_DISABLE_OPTIONS } from './constants';
import Button from 'common/new-ui/Button';
import trackAutomatedCA from './ga/automated';

const DisableReasonAutomatedWithdrawModal = ({ onClose, openModal }) => {
  const [brief, setBrief] = useState('');
  const [closeReason, setCloseReason] = useState('');
  const handleBriefChange = (e) => {
    setBrief(e.target.value);
    trackAutomatedCA.changeBriefText({});
  };

  const handleReasonChange = (e, index) => {
    setCloseReason(e.target.value);
    trackAutomatedCA.clickSelectDisableReason({
      eventAction: `Select Reason ${index + 1}`,
      eventLabel: `Reason Modal | ${e.target.value}`,
    });
  };

  const handleSubmitCloseReason = () => {
    trackAutomatedCA.clickConfirmAndCloseDisableAutomatedModal({
      eventLabel: `Reason Modal | Click button | ${brief}`,
    });
    onClose();
  };

  const handleCloseModalClick = () => {
    trackAutomatedCA.clickCloseDisableAutomatedModal({});
    onClose();
  };

  return (
    <div className="disable-reason-automated-withdraw-modal">
      <div className="cross-btn" onClick={handleCloseModalClick}>
        <i className="i i-close" />
      </div>
      <div className="disable-reason-automated-withdraw-modal--heading">Reason</div>
      <div className="disable-reason-automated-withdraw-modal--description">
        Tell us why you’re disabling automated withdrawal?
      </div>
      <div>
        {AUTOMATED_WITHDRAWAL_DISABLE_OPTIONS.map((choice, index) => {
          return (
            <div key={'parent-choice-' + choice.value} className="close-choices">
              <label key={'lab-' + choice.value}>
                <input
                  type="radio"
                  name="close-reason"
                  value={choice.value}
                  key={'inp-choice' + choice.value}
                  onChange={(e) => handleReasonChange(e, index)}
                />
                {choice.label}
              </label>
            </div>
          );
        })}
      </div>
      <div className="flex Input-textarea-container">
        <Input.Textarea
          label="Write a brief"
          size="small"
          className="Input-description Input--vTop m-b p-b"
          placeholder="Write a brief description (Optional)"
          value={brief}
          onChange={handleBriefChange}
        />
      </div>
      <div className="disable-reason-automated-withdraw-modal--confirm-btn">
        <Button.Primary
          onClick={handleSubmitCloseReason}
          disabled={!closeReason}
          className="confirm-close"
        >
          Confirm & Close
        </Button.Primary>
      </div>
    </div>
  );
};

export default DisableReasonAutomatedWithdrawModal;
