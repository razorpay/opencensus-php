import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AccordionSection from '../index';
import useAccordionSectionData from '@FTUX/hooks/useAccordionSectionData';

jest.mock('@FTUX/hooks/useAccordionSectionData', () => ({
  __esModule: true,
  default: jest.fn(),
}));

describe('Tests for AccordionSection component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('Should render CollectPaymentsAccordion with correct props', () => {
    const mockData = {
      activeStep: 1,
      accordionData: [
        { title: 'Step 1', content: <div>Content 1</div> },
        { title: 'Step 2', content: <div>Content 2</div> },
      ],
    };
    (useAccordionSectionData as jest.Mock).mockReturnValue(mockData);

    renderWithWrappers(<AccordionSection />);
    expect(screen.getByText('1/2 COMPLETED')).toBeInTheDocument();
    expect(screen.getByText('Start collecting payments in 3 easy steps')).toBeInTheDocument();
  });

  test('Should handle empty accordionData gracefully', () => {
    (useAccordionSectionData as jest.Mock).mockReturnValue({
      activeStep: 0,
      accordionData: [],
    });

    renderWithWrappers(<AccordionSection />);
    expect(screen.getByText('0/0 COMPLETED')).toBeInTheDocument();
  });

  test('Should handle multiple steps in accordionData', () => {
    (useAccordionSectionData as jest.Mock).mockReturnValue({
      activeStep: 3,
      accordionData: [
        { title: 'Step 1', content: <div>Content 1</div> },
        { title: 'Step 2', content: <div>Content 2</div> },
        { title: 'Step 3', content: <div>Content 3</div> },
      ],
    });

    renderWithWrappers(<AccordionSection />);
    expect(screen.getByText('3/3 COMPLETED')).toBeInTheDocument();
  });
});
