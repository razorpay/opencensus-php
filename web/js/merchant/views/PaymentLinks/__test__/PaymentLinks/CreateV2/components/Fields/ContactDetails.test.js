import React from 'react';
import { render } from 'test-utils';
import ContactDetails from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/ContactDetails';

describe('ContactDetails Component Unit Test', () => {
  test('renders the email input with the correct placeholder and default value', () => {
    const defaultEmailAddress = 'john@example.com';
    const { getByPlaceholderText } = render(
      <ContactDetails defaultEmailAddress={defaultEmailAddress} />,
    );
    const emailInput = getByPlaceholderText(/john@example\.com/i);
    expect(emailInput).toBeInTheDocument();
    expect(emailInput.value).toBe(defaultEmailAddress);
  });

  test('renders the contact input with the correct placeholder and default value', () => {
    const defaultContactNumber = '+91 9876543210';
    const { getByPlaceholderText } = render(
      <ContactDetails
        defaultContactNumber={defaultContactNumber}
        contactPlaceholder="+91 9876543210"
      />,
    );
    const contactInput = getByPlaceholderText(/\+91 9876543210/i);
    expect(contactInput).toBeInTheDocument();
    expect(contactInput.value).toBe(defaultContactNumber);
  });
});
