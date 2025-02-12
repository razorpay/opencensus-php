import React from 'react';
import Button from 'common/new-ui/Button';
import { CreateTicketEmitter } from '../../TicketSupport/utils';

function Note({
  message,
  showRazorpaySupportInstruction = true,
  applicationId,
  _trackSupportClick,
  extraMessage,
  product,
}) {
  const raiseTicket = () => {
    if (_trackSupportClick && _trackSupportClick.constructor === Function) {
      _trackSupportClick();
    }
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      if (
        rzpTicketSystem.setEnvironment &&
        rzpTicketSystem.setEnvironment.constructor === Function
      ) {
        rzpTicketSystem.setEnvironment('capital');
      }
      CreateTicketEmitter.emit(
        'create-ticket',
        'tickets',
      );

      setTimeout(() => {
        document.getElementsByName('request-description')[0].value = `${
          applicationId === 'new' ? '' : `[${product} Application ID:${applicationId}]`
        }I have a loan application related query`;
      }, 1000);
    }
  };

  return (
    <div className="process-note">
      {message}
      {extraMessage && (
        <div className="instructions-wrapper">
          <div className="instruction">
            <span className="description">{extraMessage}</span>
          </div>
        </div>
      )}
      {showRazorpaySupportInstruction && (
        <div className="instructions-wrapper">
          <div className="instruction">
            <div>
              <Button.Transparent onClick={raiseTicket}>Have questions?</Button.Transparent>
            </div>
            <span className="description">Write to us!</span>
          </div>
        </div>
      )}
    </div>
  );
}

export default Note;
