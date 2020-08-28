import React from 'react';
import Button from 'common/new-ui/Button';

function Note({
  message,
  showRazorpaySupportInstruction = true,
  applicationId,
  _trackSupportClick,
  extraMessage,
}) {
  const raiseTicket = () => {
    if (_trackSupportClick && _trackSupportClick.constructor === Function) {
      _trackSupportClick();
    }
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        if (
          rzpTicketSystem.setEnvironment &&
          rzpTicketSystem.setEnvironment.constructor === Function
        ) {
          rzpTicketSystem.setEnvironment('capital');
        }
        rzpTicketSystem.modal.next();
      }, 0);
      setTimeout(() => {
        document.getElementsByName('request-description')[0].value = `${
          applicationId === 'new' ? '' : `[Loan Application ID:${applicationId}]`
        }I have a loan application related query`;
      }, 1000);
    }
  };
  console.log('showRazorpaySupportInstruction', showRazorpaySupportInstruction);
  return (
    <div class="process-note">
      {message}
      {extraMessage && (
        <div className="instructions-wrapper">
          <div className="instruction">
            <span className="description">{extraMessage}</span>
          </div>
        </div>
      )}
      {showRazorpaySupportInstruction && (
        <div class="instructions-wrapper">
          <div class="instruction">
            <div>
              <Button.Transparent onClick={raiseTicket}>Have questions?</Button.Transparent>
            </div>
            <span class="description">Write to us!</span>
          </div>
        </div>
      )}
    </div>
  );
}

export default Note;
