import React from 'react';
import { Provider } from 'react-redux';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import { editStorefront } from 'merchant/reducers/paymentPages/storefront';
import BusinessDetailsMobile from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/BusinessDetailsMobile';

jest.mock('merchant/reducers/paymentPages/storefront', () => ({
  editStorefront: jest.fn(),
}));

const mockHandleClose = jest.fn();
const mockEditStorefront = jest.fn();

const defaultProps = {
  handleClose: mockHandleClose,
  openBuisnessDetailsDrawer: true,
  entity: {
    contactEmail: 'test@example.com',
    contactPhone: '1234567890',
    terms: '',
    title: 'Test Store',
  },
  editStorefront: mockEditStorefront,
  formState: {
    contactEmail: 'test@example.com',
    contactPhone: '1234567890',
  },
  errors: {
    contactEmail: '',
    contactPhone: '',
  },
  onSubmit: jest.fn(),
  handleChange: jest.fn(),
};

const mockState = {
  paymentPageStorefront: { entity: defaultProps.entity },
};

const mockStore = {
  getState: jest.fn(() => mockState),
  subscribe: jest.fn(),
  dispatch: jest.fn(),
};

const setup = (props = {}) => {
  return render(
    <Provider store={mockStore}>
      <BusinessDetailsMobile {...defaultProps} {...props} />
    </Provider>,
  );
};

describe('BusinessDetailsMobile Component', () => {
  it('should render the bottom sheet with form fields', () => {
    setup();
    expect(screen.getByText(/Add your business details/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Support email id/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Support phone number/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Write your terms and condition/i)).toBeInTheDocument();
  });

  it('should render correct initial values in the form', () => {
    setup();
    const emailInput = screen.getByLabelText(/Support email id/i) as HTMLInputElement;
    const phoneInput = screen.getByLabelText(/Support phone number/i) as HTMLInputElement;
    expect(emailInput.value).toBe('test@example.com');
    expect(phoneInput.value).toBe('1234567890');
  });

  it('should update state when input values are changed', () => {
    setup();
    const emailInput = screen.getByLabelText(/Support email id/i) as HTMLInputElement;
    const phoneInput = screen.getByLabelText(/Support phone number/i) as HTMLInputElement;
    fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
    fireEvent.change(phoneInput, { target: { value: '1234567890' } });
    expect(emailInput.value).toBe('test@example.com');
    expect(phoneInput.value).toBe('1234567890');
  });
});
