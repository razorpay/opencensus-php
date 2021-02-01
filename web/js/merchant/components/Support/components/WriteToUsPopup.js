import react from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';

function WriteToUsPopup({ businessName, supportFlags, closeModal, rzpTicketSystem, id }) {
  const msg = `Your account is currently not activated. Our team is working hard to fast track your activation and it can take ${supportFlags.no_of_days_for_activation} business days. If you have any other concerns, please feel free to raise a ticket.`;
  const handleContinueWithTicketClick = () => {
    closeModal();
    rzpTicketSystem.openModal(`#${id}`);
  };
  return (
    <div className="write-to-us">
      <div className="write-to-us-heading-container">
        <h3 className="write-to-us-heading-container write-to-us-heading">Hey {businessName}</h3>
      </div>
      <p className="write-to-us-content">{msg}</p>
      <div className="write-to-us-button-container">
        <Button
          className="btn btn-secondary write-to-us-button-container continue-button"
          type="button"
          onClick={handleContinueWithTicketClick}
        >
          Continue with Ticket
        </Button>

        <Button
          className="btn btn-primary write-to-us-button-container thanks-button"
          type="button"
          onClick={closeModal}
        >
          Thanks
        </Button>
      </div>
    </div>
  );
}

export default WriteToUsPopup;
