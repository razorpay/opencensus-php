import React, { Component } from 'react';

import Form from 'ui/Form';
import Field from 'ui/Field';

export default ({ merchant_details: merchantDetails }) => (
  <div class="container">
    <header class="m-b">Website Details</header>
    {!merchantDetails ? (
      <div class="spinner center m-t" />
    ) : (
      <Form class="full-span full-elements limited">
        <Field
          label="About Us URL"
          name="website_about"
          defaultValue={merchantDetails.website_about}
          disabled
        />

        <Field
          label="Contact Us URL"
          name="website_contact"
          defaultValue={merchantDetails.website_contact}
          helpMsg="Must contain the operational address"
          disabled
        />

        <Field
          label="Privacy Policy URL"
          name="website_privacy"
          defaultValue={merchantDetails.website_privacy}
          disabled
        />

        <Field
          label="Terms & Conditions URL"
          name="website_terms"
          defaultValue={merchantDetails.website_terms}
          disabled
        />

        <Field
          label="Refund/Cancellation Policy URL"
          name="website_refund"
          defaultValue={merchantDetails.website_refund}
          disabled
        />

        <Field
          label="URL displaying Product Pricing"
          name="website_pricing"
          defaultValue={merchantDetails.website_pricing}
          helpMsg="Any page with product prices. May display a range of prices if not actual price"
          disabled
        />

        <Field
          label="Login Window URL"
          name="website_login"
          defaultValue={merchantDetails.website_login}
          helpMsg="If no login required, give us a page where you ask for customer details"
          disabled
        />
      </Form>
    )}
  </div>
);
