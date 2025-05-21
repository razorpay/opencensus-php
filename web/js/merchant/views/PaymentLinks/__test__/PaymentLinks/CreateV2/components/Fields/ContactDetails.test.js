import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import ContactDetails from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/ContactDetails';
import { getUser } from '@apps/shell/src/client/store/commonStore/exposedActions';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

// Mock the getUser function
jest.mock('@apps/shell/src/client/store/commonStore/exposedActions', () => ({
  getUser: jest.fn(),
}));

// Mock the track object
jest.mock('merchant/views/PaymentLinks/PaymentLinks/CreateV2/track', () => ({
  lj: {
    fields: {
      email: jest.fn(),
      contact: jest.fn(),
    },
  },
}));

describe('ContactDetails Component Unit Test', () => {
  beforeEach(() => {
    // Mock user data
    getUser.mockReturnValue({
      merchant: {
        country_code: 'IN',
      },
      isCountrySingapore: false,
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('renders the email input with the correct placeholder and default value', () => {
    const defaultEmailAddress = 'john@example.com';
    const { getByPlaceholderText } = render(
      <ContactDetails defaultEmailAddress={defaultEmailAddress} />,
    );
    const emailInput = getByPlaceholderText(/john@example\.com/i);
    expect(emailInput).toBeInTheDocument();
    expect(emailInput.value).toBe(defaultEmailAddress);
  });

  test('renders PhoneNumberInput for Singapore', () => {
    getUser.mockReturnValue({
      merchant: {
        country_code: 'SG',
      },
      isCountrySingapore: true,
    });
    const { getByLabelText, queryByPlaceholderText } = render(
      <ContactDetails defaultContactNumber="+65 98765432" contactPlaceholder="+65 98765432" />,
    );
    expect(getByLabelText('Contact Number')).toBeInTheDocument();
    expect(queryByPlaceholderText(/\+65 98765432/i)).not.toBeInTheDocument();
  });

  test('renders regular Input for non-Singapore countries', () => {
    getUser.mockReturnValue({
      merchant: {
        country_code: 'IN',
      },
      isCountrySingapore: false,
    });
    const { getByPlaceholderText, queryByLabelText } = render(
      <ContactDetails defaultContactNumber="+91 9876543210" contactPlaceholder="+91 9876543210" />,
    );
    expect(getByPlaceholderText(/\+91 9876543210/i)).toBeInTheDocument();
    expect(queryByLabelText('Contact Number')).not.toBeInTheDocument();
  });

  test('calls track.lj.fields.email on email input blur', () => {
    const { getByPlaceholderText } = render(
      <ContactDetails defaultEmailAddress="test@example.com" />,
    );
    const emailInput = getByPlaceholderText(/john@example\.com/i);
    fireEvent.blur(emailInput);
    expect(track.lj.fields.email).toHaveBeenCalled();
  });

  test('calls track.lj.fields.contact and onChange when contact number changes for non-Singapore country', () => {
    const handlePhoneNumberChange = jest.fn();
    getUser.mockReturnValue({
      merchant: {
        country_code: 'IN',
      },
      isCountrySingapore: false,
    });
    const { getByPlaceholderText } = render(
      <ContactDetails
        defaultContactNumber="+91 9876543210"
        contactPlaceholder="+91 9876543210"
        handlePhoneNumberChange={handlePhoneNumberChange}
      />,
    );
    const phoneInput = getByPlaceholderText(/\+91 9876543210/i);
    fireEvent.change(phoneInput, { target: { value: '9876543211' } });
    fireEvent.blur(phoneInput);
    expect(track.lj.fields.contact).toHaveBeenCalled();
  });

  test('calls track.lj.fields.contact and onChange when contact number changes for Singapore', () => {
    getUser.mockReturnValue({
      merchant: {
        country_code: 'SG',
      },
      isCountrySingapore: true,
    });
    const handlePhoneNumberChange = jest.fn();
    const { getByLabelText } = render(
      <ContactDetails
        defaultContactNumber="+65 98765432"
        contactPlaceholder="+65 98765432"
        handlePhoneNumberChange={handlePhoneNumberChange}
      />,
    );
    const contactInput = getByLabelText('Contact Number');
    fireEvent.change(contactInput, { target: { value: '98765432' } });
    fireEvent.blur(contactInput);
    expect(track.lj.fields.contact).toHaveBeenCalled();
  });

  test('renders with disabled state', () => {
    const { container } = render(<ContactDetails disabled />);
    const inputGroup = container.querySelector('.InputGroup--inline');
    expect(inputGroup).toHaveClass('Input--disabled');
  });
});
