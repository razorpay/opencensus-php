import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

export default ({ isLoading, creditNote, statusMsg }) => {
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            Credit Note ID: <strong>{creditNote.id}</strong>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />

              <EntityDetailRow label="Name">{creditNote.name}</EntityDetailRow>

              <EntityDetailRow label="Description">
                {creditNote.description}
              </EntityDetailRow>

              <EntityDetailRow label="Amount">
                <Amount
                  currency={creditNote.currency}
                  value={creditNote.amount}
                />
              </EntityDetailRow>

              <EntityDetailRow label="Invoice ID">
                <Link to={`/invoice/${creditNote.id}`}>{creditNote.id}</Link>
              </EntityDetailRow>

              <EntityDetailRow label="Status">
                <InvoiceStatusLabel status={creditNote.status} />
              </EntityDetailRow>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
