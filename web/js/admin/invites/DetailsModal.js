import React from 'react';

export default function DetailsModal({ model }) {
  let {
    contact_email,
    contact_name,
    dba_name,
    merchant_name,
  } = model.form_data;
  return (
    <div>
      <header>Invitations Details</header>
      <div class="table table-striped">
        <div class="tr thead">
          <div class="th">Field</div>
          <div class="th">Value</div>
        </div>
        <div class="tr">
          <div class="td">Contact Email</div>
          <div class="td">contact_email</div>
        </div>
        <div class="tr">
          <div class="td">Contact Name</div>
          <div class="td">contact_name</div>
        </div>
        <div class="tr">
          <div class="td">DBA Name</div>
          <div class="td">dba_name</div>
        </div>
        <div class="tr">
          <div class="td">Merchant Name</div>
          <div class="td">merchant_name</div>
        </div>
      </div>
    </div>
  );
}
