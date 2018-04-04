import React, { Component } from 'react';
import { observer } from 'mobx-react';

import Form from 'ui/Form';
import Field, { CheckField } from 'ui/Field';

export default observer(
  ({
    merchant_details: merchantDetails,
    title,
    onIssueSelection,
    doesIssueExist,
  }) => (
    <div class="container">
      <header class="m-b">{title}</header>
      {!merchantDetails ? (
        <div class="spinner center m-t" />
      ) : (
        <Form class="full-span full-elements limited">
          <div class="mulitple-fields-group">
            <Field
              label="Contact Name"
              name="contact_name"
              defaultValue={merchantDetails.contact_name}
              disabled
            />
            <CheckField
              label="Has Issue"
              data-issuename="contact_name"
              onChange={onIssueSelection}
              checked={doesIssueExist('contact_name')}
            />
          </div>
          <div class="mulitple-fields-group">
            <Field
              label="Contact Email"
              name="contact_email"
              type="email"
              defaultValue={merchantDetails.contact_email}
              disabled
            />
            <CheckField
              label="Has Issue"
              data-issuename="contact_email"
              onChange={onIssueSelection}
              checked={doesIssueExist('contact_email')}
              defaultValue={false}
            />
          </div>
          <div class="mulitple-fields-group">
            <Field
              label="Mobile"
              name="mobile"
              defaultValue={merchantDetails.contact_mobile}
              disabled
            />
            <CheckField
              label="Has Issue"
              data-issuename="contact_mobile"
              onChange={onIssueSelection}
              checked={doesIssueExist('contact_mobile')}
            />
          </div>
        </Form>
      )}
    </div>
  )
);
