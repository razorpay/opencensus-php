import React from 'react';
import { act } from 'react-dom/test-utils';

import * as devices from 'merchant/components/Home/data';
import AvailableEmiOption from 'merchant/views/Affordability/AssistedFinancing/availableEmiOptions';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { MockPaymentsMethods } from './mocks/api';
import {
  cardlessEmiPaymentLinkData,
  emiPaymentLinkData,
  inEligibleEmiPaymentLinkData,
} from './mocks/constants';

const variantOn = { assisted_financing: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('AssistedFinancing: AvailableEmiOptions', () => {
  it('should show the available emi option component when eligibility check is completed', () => {
    render(
      <AvailableEmiOption
        shouldShowEmiMethods={true}
        merchantPaymentMethods={MockPaymentsMethods}
        paymentLinkData={cardlessEmiPaymentLinkData}
        setPaymentLinkData={jest.fn()}
        showPaymentLinkModal={jest.fn()}
      />,
    );

    expect(screen.getByText('Available EMI Options')).toBeInTheDocument();
    expect(
      screen.getByText('Share the payment link with the customer to initiate the payment.'),
    ).toBeInTheDocument();
  });

  it('should open cardless emi option and open a payment link modal', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => true);

    render(
      <AvailableEmiOption
        shouldShowEmiMethods={true}
        merchantPaymentMethods={MockPaymentsMethods}
        paymentLinkData={cardlessEmiPaymentLinkData}
        setPaymentLinkData={jest.fn()}
        showPaymentLinkModal={jest.fn()}
      />,
    );

    expect(screen.getByText('Available EMI Options')).toBeInTheDocument();

    act(async () => {
      await userEvent.click(screen.getByText('Liquiloans Cardless EMI'));
    });

    await waitFor(() => {
      expect(screen.getByText('Send payment link')).toBeInTheDocument();
    });
  });

  it('should open credit card emi option on mobile', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => true);

    render(
      <AvailableEmiOption
        shouldShowEmiMethods={true}
        merchantPaymentMethods={MockPaymentsMethods.slice(0, 3)}
        paymentLinkData={emiPaymentLinkData}
        setPaymentLinkData={jest.fn()}
        showPaymentLinkModal={jest.fn()}
      />,
    );

    expect(screen.getByText('Available EMI Options')).toBeInTheDocument();

    act(async () => {
      await userEvent.click(screen.getByText('UTIB Credit Card EMI'));
    });

    await waitFor(() => {
      expect(screen.getByText('Send payment link')).toBeInTheDocument();
    });
  });

  it('should display not eligible text if the emi is not eligible', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => false);

    render(
      <AvailableEmiOption
        shouldShowEmiMethods={true}
        merchantPaymentMethods={MockPaymentsMethods.slice(3, 4)}
        paymentLinkData={inEligibleEmiPaymentLinkData}
        setPaymentLinkData={jest.fn()}
        showPaymentLinkModal={jest.fn()}
      />,
    );

    expect(screen.getByText('Available EMI Options')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('Not Eligible')).toBeInTheDocument();
    });
  });

  it('should display emi options available after selecting an eligible credit card emi option', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => false);

    render(
      <AvailableEmiOption
        shouldShowEmiMethods={true}
        merchantPaymentMethods={MockPaymentsMethods.slice(1, 2)}
        paymentLinkData={emiPaymentLinkData}
        setPaymentLinkData={jest.fn()}
        showPaymentLinkModal={jest.fn()}
      />,
    );

    expect(screen.getByText('Available EMI Options')).toBeInTheDocument();

    await waitFor(() => screen.getByText('EMI Plan'));

    expect(screen.getByText('Interest (pa)')).toBeInTheDocument();
    expect(screen.getByText('Total Cost')).toBeInTheDocument();
    expect(screen.getByText(emiPaymentLinkData.emiPlan[0].emiPlan)).toBeInTheDocument();
    expect(screen.getByText('Send payment link')).toBeInTheDocument();
  });
});
