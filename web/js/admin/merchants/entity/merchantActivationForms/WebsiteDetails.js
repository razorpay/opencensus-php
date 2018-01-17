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
          label="Website Address"
          name="business_website"
          defaultValue={merchantDetails.business_website}
          placeholder="http://www.website.com"
        />

        <Field
          label="About Us URL"
          name="website_about"
          defaultValue={merchantDetails.website_about}
          placeholder="http://www.website.com/aboutus.html"
        />

        <Field
          label="Contact Us URL"
          name="website_contact"
          defaultValue={merchantDetails.website_contact}
          placeholder="http://www.website.com/contact.html"
          infoMsg="Must contain the operational address"
        />

        <Field
          label="Privacy Policy URL"
          name="website_privacy"
          defaultValue={merchantDetails.website_privacy}
          placeholder="http://www.website.com/privacy.html"
        />

        <Field
          label="Terms & Conditions URL"
          name="website_terms"
          defaultValue={merchantDetails.website_terms}
          placeholder="http://www.example.com/terms.html"
        />

        <Field
          label="Refund/Cancellation Policy URL"
          name="website_refund"
          defaultValue={merchantDetails.website_refund}
          placeholder="http://www.example.com/refund.html"
        />

        <Field
          label="URL displaying Product Pricing"
          name="website_pricing"
          defaultValue={merchantDetails.website_pricing}
          placeholder="http://www.example.com/pricing.html"
          infoMsg="Any page with product prices. May display a range of prices if not actual price"
        />

        <Field
          label="Login Window URL"
          name="website_login"
          defaultValue={merchantDetails.website_login}
          infoMsg="If no login required, give us a page where you ask for customer details"
          placeholder="http://www.website.com/login.html"
        />
      </Form>
    )}
  </div>
);
