import React from 'react';
import Table from 'ui/Table';

export default function DetailsModal({ model }) {
  let {
    contact_email,
    contact_name,
    dba_name,
    merchant_name,
  } = model.form_data;

  let items = [
    ['Contact Email', contact_email],
    ['Contact Name', contact_name],
    ['DBA Name', dba_name],
    ['Merchant Name', 'merchant_name'],
  ];

  let fields = [['Field', item => item[0]], ['Value', item => item[1]]];

  return (
    <div>
      <header>Invitations Details</header>
      <Table items={items} fields={fields} />
    </div>
  );
}
