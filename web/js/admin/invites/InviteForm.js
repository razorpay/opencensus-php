import React from 'react';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

export default function InviteForm({ fields, onInvite }) {
  return (
    <div>
      <header>
        Invitation details
        <p>
          Smarthub: Education, Society &amp; Government merchants should NOT be
          onboarded through this solution
        </p>
      </header>
      <Form onSubmit={onInvite}>
        {fields.indexOf('channel_code') > -1 && (
          <div>
            <SelectField label="Channel Code" name="channel_code" required>
              <option value="">Please select a channel code</option>
              <option value="mrm">MRM</option>
              <option value="branch">Branch/CANI</option>
              <option value="db">Database</option>
              <option value="dsa">DSA</option>
              <option value="others">Others</option>
            </SelectField>
            <Field
              label="Channel Code"
              placeholder="Please mention a Channel_code"
              name="channel_code_others"
              required
            />
          </div>
        )}

        {fields.indexOf('crm_next_no') > -1 && (
          <Field label="CRM Next No." name="crm_next_no" required />
        )}

        {fields.indexOf('db_token_no') > -1 && (
          <Field label="Database Token No." name="db_token_no" required />
        )}

        {fields.indexOf('branch_lts_no') > -1 && (
          <Field label="Branch LTS No." name="branch_lts_no" required />
        )}

        {fields.indexOf('branch_code') > -1 && (
          <Field label="Branch Code" name="branch_code" required />
        )}

        {fields.indexOf('source_code') > -1 && (
          <Field label="Source Code" name="source_code" required />
        )}

        {fields.indexOf('promo_code') > -1 && (
          <Field label="Promo Code" name="promo_code" required />
        )}

        {fields.indexOf('lg_code') > -1 && (
          <Field label="LG Code" name="lg_code" required />
        )}

        {fields.indexOf('lc_ro_code') > -1 && (
          <Field label="LG/RO Code" name="lc_ro_code" required />
        )}

        {fields.indexOf('mrm_code') > -1 && (
          <Field label="MRM Code" name="mrm_code" required />
        )}

        {fields.indexOf('merchant_type') > -1 && (
          <SelectField label="Type of Merchant" name="merchant_type" required>
            <option value="stp">STP</option>
            <option value="nstp">NSTP</option>
          </SelectField>
        )}

        {fields.indexOf('mcc_category') > -1 && (
          <Field label="MCC Category" name="mcc_category" required />
        )}

        <header>Merchant Details:</header>
        {fields.indexOf('merchant_name') > -1 && (
          <Field label="Merchant Name" name="merchant_name" required />
        )}

        {fields.indexOf('contact_name') > -1 && (
          <Field label="Contact Name" name="contact_name" required />
        )}

        {fields.indexOf('contact_email') > -1 && (
          <Field
            label="Contact Email"
            name="contact_email"
            required
            type="email"
          />
        )}

        {fields.indexOf('dba_name') > -1 && (
          <Field label="DBA Name" name="dba_name" required />
        )}
        <AsyncButton
          text="Save"
          class="btn"
          pendingClass="small spinner"
          onSubmit={onInvite}
        />
      </Form>
    </div>
  );
}
