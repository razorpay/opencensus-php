import React from 'react';
import { screen, act, cleanup } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AccordionSection from '../index';
import useAccordionSectionData from '@FTUX/hooks/useAccordionSectionData';
import { useMerchantContext } from '@FTUX/context/MerchantContext';

// Mock dependencies
jest.mock('@FTUX/hooks/useAccordionSectionData', () => ({
  __esModule: true,
  default: jest.fn(),
}));

jest.mock('@FTUX/context/MerchantContext', () => ({
  useMerchantContext: jest.fn(),
}));

// Mock CollectPaymentsAccordion
jest.mock('../CollectPaymentsAccordion', () => ({
  __esModule: true,
  default: jest
    .fn()
    .mockImplementation(({ data, completedSteps, expandedStep, setExpandedStep }) => (
      <div data-testid="collect-payments-accordion">
        <span data-testid="accordion-completed">{completedSteps}</span>
        <span data-testid="accordion-total">{data.length}</span>
        <span data-testid="accordion-expanded">{expandedStep}</span>
        <button onClick={() => setExpandedStep(2)} data-testid="expand-step-2">
          Expand Step 2
        </button>
      </div>
    )),
}));

describe('AccordionSection Component', () => {
  const mockSetExpandedStep = jest.fn();

  const defaultAccordionData = {
    completedSteps: 1,
    expandedStep: 1,
    setExpandedStep: mockSetExpandedStep,
    accordionData: [
      { title: 'Step 1', content: <div>Content 1</div> },
      { title: 'Step 2', content: <div>Content 2</div> },
    ],
    isAppOnlyMerchant: false,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useAccordionSectionData as jest.Mock).mockReturnValue(defaultAccordionData);
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          activation: {
            isActivated: false,
          },
        },
      },
    });
  });

  afterEach(() => {
    cleanup();
  });

  test('should render AccordionSection with test setup heading when merchant not activated', async () => {
    await act(async () => {
      renderWithWrappers(<AccordionSection />);
    });

    expect(screen.getByText('Test collecting payments in 2 easy steps')).toBeInTheDocument();
    expect(screen.getByText('1/2 COMPLETED')).toBeInTheDocument();
  });

  test('should render AccordionSection with website setup heading when merchant is activated', async () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          activation: {
            isActivated: true,
          },
        },
      },
    });

    await act(async () => {
      renderWithWrappers(<AccordionSection />);
    });

    expect(screen.getByText('Set up your website')).toBeInTheDocument();
    expect(screen.getByText('1/2 COMPLETED')).toBeInTheDocument();
  });

  test('should render AccordionSection with app setup heading for app-only merchants', async () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          activation: {
            isActivated: true,
          },
        },
      },
    });

    (useAccordionSectionData as jest.Mock).mockReturnValue({
      ...defaultAccordionData,
      isAppOnlyMerchant: true,
    });

    await act(async () => {
      renderWithWrappers(<AccordionSection />);
    });

    expect(screen.getByText('Set up your apps')).toBeInTheDocument();
  });

  test('should handle empty accordionData gracefully', async () => {
    (useAccordionSectionData as jest.Mock).mockReturnValue({
      ...defaultAccordionData,
      completedSteps: 0,
      accordionData: [],
    });

    await act(async () => {
      renderWithWrappers(<AccordionSection />);
    });

    expect(screen.getByText('0/0 COMPLETED')).toBeInTheDocument();
  });

  test('should correctly call setExpandedStep when step is expanded', async () => {
    await act(async () => {
      renderWithWrappers(<AccordionSection />);
    });

    const expandButton = screen.getByTestId('expand-step-2');

    await act(async () => {
      expandButton.click();
    });

    expect(mockSetExpandedStep).toHaveBeenCalledWith(2);
  });
});
