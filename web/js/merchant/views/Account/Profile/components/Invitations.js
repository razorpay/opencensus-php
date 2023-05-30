import AsyncButton from 'react-async-button';
import DetailRow from 'merchant/components/DetailRow';

export default ({ invitations, onAcceptClick, onRejectClick }) => {
  return (
    <div class="panel panel-default">
      <div class="panel-heading">Invitations</div>

      <div class="list-group details-row-container">
        {invitations.map((invite, index) => (
          <DetailRow
            key={index}
            label={() => (
              <span data-testid="invitation-merchant-name">
                Invitation to join <strong>{invite.merchant_name}</strong>
              </span>
            )}
            value={() => (
              <div class="btn-toolbar">
                <AsyncButton
                  text="Accept"
                  pendingText="Accepting..."
                  class="btn btn-xs btn-success"
                  onClick={() => onAcceptClick(invite)}
                />
                <AsyncButton
                  text="Reject"
                  pendingText="Rejecting..."
                  class="btn btn-xs btn-danger"
                  onClick={() => onRejectClick(invite)}
                />
              </div>
            )}
          />
        ))}
      </div>
    </div>
  );
};
