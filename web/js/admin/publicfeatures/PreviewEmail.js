import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

/**
 * Email preview with issues listed & comments in them.
 * It's editable
 */

export default function PreviewEmail({
  productName,
  needs_clarification_text,
}) {
  return (
    <BaseModal header="Email Preview (Add your comments inside Clarification box)">
      <div class="email-preview">
        <p>
          Hey,
          <br />
          <br />
          Thank you for submitting your request for {productName}. We need a few
          more details from you, before we can enable {productName} on your
          account.
          <br />
          <br />
          {productName === 'marketplace' &&
            'The sample vendor agreement uploaded does not meet our requirements. Please ensure that the agreement contains the below points.'}
        </p>
        Clarifications:
        <pre>{needs_clarification_text}</pre>
        <p>
          {productName === 'marketplace'
            ? 'Please reply to this email with the updated sample vendor agreement so that we can take further course of action.'
            : 'Please reply to this email, with the necessary details,  so that we can take further course of action.'}
        </p>
        <p>
          Regards,
          <br />
          Team Razorpay
        </p>
        <p>
          <strong>
            P.S: We would need 24-48 working hours to get your responses
            validated with our partner banks. Also, Kindly avoid in-line
            responses. To report a grievance, click here:{' '}
          </strong>
          <a
            href="https://razorpay.com/grievances/"
            class="link grievance-link"
          >
            https://razorpay.com/grievances/
          </a>
        </p>
      </div>
    </BaseModal>
  );
}
