import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AccordionSection from '../index';
import useAccordionSectionData from '@FTUX/hooks/useAccordionSectionData';

jest.mock('@FTUX/hooks/useAccordionSectionData');

describe('Tests for AccordionSection component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('Should render CollectPaymentsAccordion with correct props', () => {
    const mockData = { activeStep: 1, accordionData: [{ id: 1 }, { id: 2 }] };
    (useAccordionSectionData as jest.Mock).mockReturnValue(mockData);

    renderWithWrappers(<AccordionSection />);
    expect(screen.getByText(/2\/2 COMPLETED/)).toBeInTheDocument(); // Updated values from mock
    expect(screen.getByText('Start collecting payments in 3 easy steps')).toBeInTheDocument();
  });

  test('Should handle empty accordionData gracefully', () => {
    (useAccordionSectionData as jest.Mock).mockReturnValue({
      activeStep: 0,
      accordionData: [],
    });

    renderWithWrappers(<AccordionSection />);
    expect(screen.getByText(/1\/0 COMPLETED/)).toBeInTheDocument();
  });

  test('Should handle multiple steps in accordionData', () => {
    (useAccordionSectionData as jest.Mock).mockReturnValue({
      activeStep: 2,
      accordionData: [{ id: 1 }, { id: 2 }, { id: 3 }],
    });

    renderWithWrappers(<AccordionSection />);
    expect(screen.getByText(/3\/3 COMPLETED/)).toBeInTheDocument();
  });
});
