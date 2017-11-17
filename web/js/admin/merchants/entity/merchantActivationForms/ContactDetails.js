import React, { Component } from 'react';

import Form from 'ui/Form';
import Field from 'ui/Field';

export default ({ merchant_details: merchantDetails, title }) => (
  <div class="container">
    <header class="m-b">{title}</header>
    {!merchantDetails ? (
      <div class="spinner center m-t" />
    ) : (
      <Form class="full-span">
        <Field
          label="Contact Name"
          name="contact_name"
          defaultValue={merchantDetails.contact_name}
          required
        />
        <Field
          label="Contact Email"
          name="contact_email"
          type="email"
          defaultValue={merchantDetails.contact_email}
          required
        />
        <Field
          label="Mobile"
          name="mobile"
          defaultValue={merchantDetails.contact_mobile}
          required
        />
        <Field
          label="Landline"
          name="email"
          defaultValue={merchantDetails.contact_landline}
        />
      </Form>
    )}
  </div>
);
