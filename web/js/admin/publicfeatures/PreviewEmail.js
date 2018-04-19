import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';
import { closeModal, notifyError } from 'common/modal';

/**
 * Email preview with issues listed & comments in them.
 * It's editable
 */

export default class PreviewEmail extends Component {
  state = {
    needs_clarification_text: this.props.needs_clarification_text || '',
  };

  handleChange = e => {
    const needs_clarification_text = e.target.value;
    this.setState({ needs_clarification_text }, () => {
      // update parent component state
      this.props.onClarificationTextChange(needs_clarification_text);
    });
  };

  close = () => {
    if (this.state.needs_clarification_text.length <= 0) {
      notifyError('Please enter clarificaton email text to save.');
    } else {
      closeModal();
    }
  };

  render() {
    const { productName } = this.props;

    return (
      <BaseModal header="Email Preview (Add your comments inside Clarification box)">
        <div class="email-preview">
          <p>
            Hey,
            <br />
            <br />
            Thank you for submitting your request for {productName}. We need a
            few more details from you, before we can enable {productName} on
            your account.
            <br />
            <br />
            {productName === 'marketplace' &&
              'The sample vendor agreement uploaded does not meet our requirements. Please ensure that the agreement contains the below points.'}
          </p>
          Clarifications:
          <textarea
            placeholder="Please enter your clarification text here..."
            value={this.state.needs_clarification_text}
            onChange={this.handleChange}
            style={{ width: '100%' }}
            autoFocus={true}
          />
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
              target="_blank"
            >
              https://razorpay.com/grievances/
            </a>
          </p>
          <button class="btn" onClick={this.close}>
            Save
          </button>
        </div>
      </BaseModal>
    );
  }
}
