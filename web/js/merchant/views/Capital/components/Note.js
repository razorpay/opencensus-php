import React from 'react';
import Button from 'common/new-ui/Button';

function Note({
  message,
  showRazorpaySupportInstruction = true,
  applicationId,
}) {
  const raiseTicket = () => {
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
      setTimeout(() => {
        document.getElementsByName('request-description')[0].value = `${
          applicationId === 'new'
            ? ''
            : `[Loan Application ID:${applicationId}]`
        }I have a loan application related query`;
      }, 1000);
    }
  };
  return (
    <div class="process-note">
      {message}
      {showRazorpaySupportInstruction && (
        <div class="instructions-wrapper">
          <div class="instruction">
            <div>
              <Button.Transparent onClick={raiseTicket}>
                Have questions?
              </Button.Transparent>
            </div>
            <span class="description">Write to us!</span>
          </div>
        </div>
      )}
    </div>
  );
}

export default Note;
