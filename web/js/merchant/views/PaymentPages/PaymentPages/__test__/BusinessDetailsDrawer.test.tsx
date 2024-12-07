import React from 'react';
import { Provider } from 'react-redux';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import BusinessDetailsDrawer from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/BusinessDetailsDrawer';

const mockEditStorefront = jest.fn();
const mockHandleClose = jest.fn();

const defaultProps = {
  handleClose: mockHandleClose,
  openBuisnessDetailsDrawer: true,
  entity: {
    contactEmail: 'test@example.com',
    contactPhone: '1234567890',
    terms: '',
    title: 'Test Store',
  },
  isMobile: false,
  editStorefront: mockEditStorefront,
};

const mockState = {
  paymentPageStorefront: { entity: defaultProps.entity },
  app: { isMobileResolution: defaultProps.isMobile },
};

const mockStore = {
  getState: jest.fn(() => mockState),
  subscribe: jest.fn(),
  dispatch: jest.fn(),
};

const setup = (props = {}) => {
  return render(
    <Provider store={mockStore}>
      <BusinessDetailsDrawer {...defaultProps} {...props} />
    </Provider>,
  );
};

describe('BusinessDetailsDrawer Component', () => {
  it('should render the drawer with form fields', () => {
    setup();
    expect(screen.getByText(/Add your business details/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Support email id/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Support phone number/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/Write your terms and condition/i)).toBeInTheDocument();
  });

  it('should update state when input values are changed', () => {
    setup();
    const emailInput = screen.getByLabelText(/Support email id/i) as HTMLInputElement;
    const phoneInput = screen.getByLabelText(/Support phone number/i) as HTMLInputElement;
    fireEvent.change(emailInput, { target: { value: 'new@example.com' } });
    fireEvent.change(phoneInput, { target: { value: '9876543210' } });
    expect(emailInput.value).toBe('new@example.com');
    expect(phoneInput.value).toBe('9876543210');
  });

  it('should render with correct initial values from `entity`', () => {
    setup();
    const emailInput = screen.getByLabelText(/Support email id/i) as HTMLInputElement;
    const phoneInput = screen.getByLabelText(/Support phone number/i) as HTMLInputElement;
    expect(emailInput.value).toBe('test@example.com');
    expect(phoneInput.value).toBe('1234567890');
  });
});
