import { Link } from 'react-router-dom';

import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

/*
{
  "id": "crnt_CdJ6bpQFE0Kgfi",
  "customer_id": "CSgSa6pi6wvAfa",
  "merchant_id": "10000000000000",
  "name": "Test credit note",
  "description": null,
  "amount": 5000,
  "amount_available": 5000,
  "amount_refunded": 0,
  "amount_allocated": 0,
  "currency": "INR",
  "created_at": 1559561988,
  "updated_at": 1559561988
}
*/
export default ({ isLoading, creditNote }) => {
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
