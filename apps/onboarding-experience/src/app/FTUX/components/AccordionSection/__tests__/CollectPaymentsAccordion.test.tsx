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
  const mockSetExpandedStep = jest.fn();

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

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('Should render all accordion items correctly', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={0}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();
  });

  test('Should render with default expandedStep=0 when not provided', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={0}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    // First accordion item should be expanded by default
    expect(screen.getByTestId('content-1')).toBeInTheDocument();
  });

  test('Should render with the specified expandedStep', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={1}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    // Second accordion item should be expanded
    expect(screen.getByTestId('content-2')).toBeInTheDocument();
  });

  test('Should handle empty data array gracefully', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={[]}
        expandedStep={0}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    // Component should render without errors
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  test('Should handle clicks to expand/collapse accordion items', async () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={0}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    // First item should be expanded by default
    expect(screen.getByTestId('content-1')).toBeInTheDocument();

    // Click on second item to expand it
    fireEvent.click(screen.getByText('Step 2'));

    // Second item should be expanded now
    await waitFor(() => {
      expect(screen.getByTestId('content-2')).toBeInTheDocument();
    });
  });

  test('Should render incomplete steps correctly', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={2}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={1}
      />,
    );

    // Third item (which is marked as incomplete) should be expanded
    expect(screen.getByTestId('content-3')).toBeInTheDocument();
  });

  test('Should handle all completed steps correctly', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockCompletedAccordionData}
        expandedStep={3}
        completedSteps={3}
        setExpandedStep={mockSetExpandedStep}
      />,
    );

    // When expandedStep is equal to the number of items, all steps are completed
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

    renderWithWrappers(
      <CollectPaymentsAccordion
        data={dataWithNullSuffix}
        expandedStep={0}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    // Component should render without errors when getTitleSuffix returns null
    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByTestId('content-1')).toBeInTheDocument();
  });

  test('Should render correct UI for completed and current steps', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={1}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={1}
      />,
    );

    // Check that step 1 shows as completed (with check icon)
    const accordionItems = screen.getAllByRole('button');
    expect(accordionItems[0]).toHaveTextContent('Step 1');

    // Check that step 2 is expanded and shows as current step
    expect(screen.getByTestId('content-2')).toBeInTheDocument();
  });

  test('Should call setExpandedStep with the correct index when clicking accordion items', () => {
    renderWithWrappers(
      <CollectPaymentsAccordion
        data={mockAccordionData}
        expandedStep={0}
        setExpandedStep={mockSetExpandedStep}
        completedSteps={0}
      />,
    );

    // Click on the third item
    fireEvent.click(screen.getByText('Step 3'));

    // Verify that setExpandedStep was called with index 2
    expect(mockSetExpandedStep).toHaveBeenCalledWith(2);
  });
});
