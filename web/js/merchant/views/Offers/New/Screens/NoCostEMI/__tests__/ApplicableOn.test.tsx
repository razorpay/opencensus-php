import React from 'react';
import { screen } from '@testing-library/react';

import '@testing-library/jest-dom/extend-expect';
import { render } from 'test-utils';

import ApplicableOn from '../ApplicableOn';

const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
  withSplitzService: (Component) => (props) =>
    <Component {...props} splitz={{ abExperiments: { Low_cost_offer: {} } }} />,
}));

jest.mock('common/ui/Amount', () => (props) => <span>{props.value}</span>);
jest.mock('common/new-ui/Input', () => ({
  Select: (props) => (
    <select {...props}>
      <option value="">{props.placeholder}</option>
    </select>
  ),
  Check: (props) => <input type="checkbox" {...props} />,
  Group: ({ label, children }) => (
    <div>
      <label>{label}</label>
      {children}
    </div>
  ),
}));
jest.mock('merchant/components/DocsLink', () => ({ DocLink: (props) => <a {...props} /> }));
jest.mock('merchant/views/Offers/utils', () => ({ getIssuerLabel: (issuer) => issuer }));

const mockEmiData = {
  emi_plans: {
    issuer1: { min_amount: 1000 },
    issuer2: { min_amount: 5000 },
  },
  emi_options: {
    issuer1: [
      { duration: 3, merchant_payback: 5 },
      { duration: 6, merchant_payback: 10 },
    ],
    issuer2: [{ duration: 6, merchant_payback: 7 }],
  },
};

const mockProps = {
  emiData: mockEmiData,
  values: { issuer: 'issuer1', emi_durations: [3] },
  errors: {},
  touched: {},
  minAmount: 3000,
  handleChange: jest.fn(),
  onOffersChange: jest.fn(),
  setFieldTouched: jest.fn(),
  setFieldValue: jest.fn(),
  setErrors: jest.fn(),
  offersData: {},
  isFormLocked: false,
};

const renderComponent = (props = {}) => render(<ApplicableOn {...mockProps} {...props} />);

describe('ApplicableOn Component', () => {
  test('renders correctly with initial data', () => {
    renderComponent();
    expect(screen.getByText('Discount borne by merchant')).toBeInTheDocument();
    expect(screen.getByText('EMI tenure')).toBeInTheDocument();
    expect(screen.getByText('5 %')).toBeInTheDocument();
    expect(screen.getByText('10 %')).toBeInTheDocument();
  });

  test('renders FootNote correctly', () => {
    renderComponent();
    expect(screen.getByText(/Only banks with minimum EMI order amount of/)).toBeInTheDocument();
    expect(
      screen.getByText(
        /In No-Cost-EMI, the interest charged by bank is given as a discount to the customer./,
      ),
    ).toBeInTheDocument();
  });

  test('displays correct options based on minAmount', () => {
    const props = {
      minAmount: 10000,
    };
    renderComponent(props);
    expect(screen.queryAllByText('issuer1')).toHaveLength(2);
  });
});
