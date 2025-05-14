import React from 'react';
import {
  screen,
  waitFor,
  fireEvent,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import CollectPaymentsAccordion from '../CollectPaymentsAccordion';
import { AccordionDataType } from '@FTUX/types/homepage';

describe('Tests for CollectPaymentsAccordion component', () => {
  const mockAccordionData: AccordionDataType[] = [
    {
      title: 'Step 1',
      content: <div data-testid="content-1">Content 1</div>,
    },
    {
      title: 'Step 2',
      content: <div data-testid="content-2">Content 2</div>,
    },
    {
      title: 'Step 3',
      content: <div data-testid="content-3">Content 3</div>,
      isIncomplete: true,
    },
  ];

  const mockAccordionDataWithTitleSuffix: AccordionDataType[] = [
    {
      title: 'Step 1',
      content: <div data-testid="content-1">Content 1</div>,
      // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
      getTitleSuffix: (isExpanded) =>
        isExpanded ? (
          <span data-testid="expanded-suffix">Expanded</span>
        ) : (
          <span data-testid="collapsed-suffix">Collapsed</span>
        ),
    },
    {
      title: 'Step 2',
      content: <div data-testid="content-2">Content 2</div>,
    },
  ];

  const mockCompletedAccordionData: AccordionDataType[] = [
    {
      title: 'Step 1',
      content: <div data-testid="content-1">Content 1</div>,
    },
    {
      title: 'Step 2',
      content: <div data-testid="content-2">Content 2</div>,
    },
    {
      title: 'Step 3',
      content: <div data-testid="content-3">Content 3</div>,
    },
  ];

  test('Should render all accordion items correctly', () => {
    renderWithWrappers(<CollectPaymentsAccordion data={mockAccordionData} />);

    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();
  });

  test('Should render with default activeStep=0 when not provided', () => {
    renderWithWrappers(<CollectPaymentsAccordion data={mockAccordionData} />);

    // First accordion item should be expanded by default
    expect(screen.getByTestId('content-1')).toBeInTheDocument();
  });

  test('Should render with the specified activeStep', () => {
    renderWithWrappers(<CollectPaymentsAccordion data={mockAccordionData} activeStep={1} />);

    // Second accordion item should be expanded
    expect(screen.getByTestId('content-2')).toBeInTheDocument();
  });

  test('Should handle empty data array gracefully', () => {
    renderWithWrappers(<CollectPaymentsAccordion data={[]} />);

    // Component should render without errors
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  test('Should handle clicks to expand/collapse accordion items', async () => {
    renderWithWrappers(<CollectPaymentsAccordion data={mockAccordionData} activeStep={0} />);

    // First item should be expanded by default
    expect(screen.getByTestId('content-1')).toBeInTheDocument();

    // Click on second item to expand it
    fireEvent.click(screen.getByText('Step 2'));

    // Second item should be expanded now
    await waitFor(() => {
      expect(screen.getByTestId('content-2')).toBeInTheDocument();
    });
  });

  test('Should render the title suffix when provided and expanded', async () => {
    renderWithWrappers(
      <CollectPaymentsAccordion data={mockAccordionDataWithTitleSuffix} activeStep={0} />,
    );

    // First item should be expanded by default, so the expanded suffix should be visible
    expect(screen.getByTestId('expanded-suffix')).toBeInTheDocument();

    // Click on second item to expand it
    fireEvent.click(screen.getByText('Step 2'));

    // First item should be collapsed now, so the collapsed suffix should be visible
    await waitFor(() => {
      expect(screen.getByTestId('collapsed-suffix')).toBeInTheDocument();
    });
  });

  test('Should render incomplete steps correctly', () => {
    renderWithWrappers(<CollectPaymentsAccordion data={mockAccordionData} activeStep={2} />);

    // Third item (which is marked as incomplete) should be expanded
    expect(screen.getByTestId('content-3')).toBeInTheDocument();
  });

  test('Should handle all completed steps correctly', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion data={mockCompletedAccordionData} activeStep={3} />,
    );

    // When activeStep is equal to the number of items, all steps are completed
    const accordionItems = screen.getAllByRole('button');
    expect(accordionItems).toHaveLength(3);

    // First item should be expanded by default when all steps are completed
    expect(screen.getByTestId('content-1')).toBeInTheDocument();
  });

  test('Should handle getTitleSuffix returning null', () => {
    const dataWithNullSuffix: AccordionDataType[] = [
      {
        title: 'Step 1',
        content: <div data-testid="content-1">Content 1</div>,
        // nosemgrep : ssc-adb055b9-fed0-4d70-a57d-eb9825b09449
        getTitleSuffix: () => null,
      },
    ];

    renderWithWrappers(<CollectPaymentsAccordion data={dataWithNullSuffix} />);

    // Component should render without errors when getTitleSuffix returns null
    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByTestId('content-1')).toBeInTheDocument();
  });
});
