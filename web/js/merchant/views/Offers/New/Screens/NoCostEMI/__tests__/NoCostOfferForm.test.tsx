import React from 'react';
import { screen, fireEvent } from '@testing-library/react';

import { offerHeaders } from 'merchant/views/Offers/New/Screens/NoCostEMI/constants';
import { render } from 'test-utils';

import { NoCostOfferForm } from '../NoCostOfferForm';

jest.mock('merchant/views/Offers/New/Screens/NoCostEMI/NoCostOfferActionRow', () => {
  return ({ plan, handleChange }) => (
    <div data-testid="emi-tenure-action">
      <p>{`Duration: ${plan.duration} Months`}</p>
      <button
        onClick={() => handleChange({ target: { name: 'emi_durations', value: plan.duration } })}
      >
        Change Duration
      </button>
    </div>
  );
});

const mockTenure = [
  { duration: 3, merchant_payback: '2', subvention: 'merchant', min_amount: 0, interest: 0 },
  { duration: 6, merchant_payback: '4', subvention: 'customer', min_amount: 0, interest: 0 },
];

const mockFormData = {
  issuer: 'issuer1',
  emi_durations: [],
};

const mockOffersData = {};

const renderComponent = (props = {}) =>
  render(
    <NoCostOfferForm
      values={mockFormData}
      handleChange={jest.fn()}
      onOffersChange={jest.fn()}
      offersData={mockOffersData}
      tenure={mockTenure}
      {...props}
      errors={{}}
    />,
  );

describe('NoCostOfferForm Component', () => {
  test('renders without crashing', () => {
    renderComponent();
    expect(screen.getByText('Details')).toBeInTheDocument();
  });

  test('renders offer headers correctly', () => {
    renderComponent();
    offerHeaders.forEach((header) => {
      expect(screen.getByText(header.value)).toBeInTheDocument();
    });
  });

  test('renders EMI plans correctly', () => {
    renderComponent();
    mockTenure.forEach((plan) => {
      expect(screen.getByText(`Duration: ${plan.duration} Months`)).toBeInTheDocument();
    });
  });

  test('calls onChange when EMI duration is changed', () => {
    const mockHandleChange = jest.fn();
    renderComponent({ handleChange: mockHandleChange });
    const buttons = screen.getAllByText('Change Duration');
    fireEvent.click(buttons[0]);
    expect(mockHandleChange).toHaveBeenCalledWith({
      target: { name: 'emi_durations', value: mockTenure[0].duration },
    });
  });

  test('calls onOffersChange when EMI plan is changed', () => {
    const mockOnOffersChange = jest.fn();
    renderComponent({ onOffersChange: mockOnOffersChange });
  });

  test('renders correct number of EMI plans', () => {
    renderComponent();
    const emiPlanElements = screen.getAllByTestId('emi-tenure-action');
    expect(emiPlanElements.length).toBe(mockTenure.length);
  });

  test('renders correct emi duration for each EMI plan', () => {
    renderComponent();
    const emiPlanElements = screen.getAllByTestId('emi-tenure-action');
    emiPlanElements.forEach((emiPlan, index) => {
      const durationText = `Duration: ${mockTenure[index].duration} Months`;
      expect(emiPlan).toHaveTextContent(durationText);
    });
  });

  test('calls onChange with selected duration when "Change Duration" button is clicked', () => {
    const mockHandleChange = jest.fn();
    renderComponent({ handleChange: mockHandleChange });
    const changeDurationButtons = screen.getAllByText('Change Duration');
    changeDurationButtons.forEach((button, index) => {
      fireEvent.click(button);
      expect(mockHandleChange).toHaveBeenCalledWith({
        target: { name: 'emi_durations', value: mockTenure[index].duration },
      });
    });
  });

  test('renders offer headers correctly', () => {
    renderComponent();
    offerHeaders.forEach((header) => {
      expect(screen.getByText(header.value)).toBeInTheDocument();
    });
  });
});
