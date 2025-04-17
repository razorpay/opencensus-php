import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AccordionSection from '../CollectPaymentsAccordion';

describe('Tests for CollectPaymentsAccordion component', () => {
  test('Should render all accordion items with correct titles and content', () => {
    const mockData = [
      { title: 'Step 1', content: 'Content for Step 1', isIncomplete: false },
      { title: 'Step 2', content: 'Content for Step 2', isIncomplete: true },
      { title: 'Step 3', content: 'Content for Step 3', isIncomplete: false },
    ];

    renderWithWrappers(<AccordionSection data={mockData} activeStep={1} />);

    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Content for Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Content for Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();
    expect(screen.getByText('Content for Step 3')).toBeInTheDocument();
  });

  test('Should handle empty data gracefully', () => {
    renderWithWrappers(<AccordionSection data={[]} activeStep={0} />);
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  test('Should expand the correct accordion item based on activeStep', () => {
    const mockData = [
      { title: 'Step 1', content: 'Content for Step 1', isIncomplete: false },
      { title: 'Step 2', content: 'Content for Step 2', isIncomplete: true },
      { title: 'Step 3', content: 'Content for Step 3', isIncomplete: false },
    ];

    renderWithWrappers(<AccordionSection data={mockData} activeStep={2} />);

    const expandedItem = screen.getByText('Content for Step 3');
    expect(expandedItem).toBeVisible();
  });
});
