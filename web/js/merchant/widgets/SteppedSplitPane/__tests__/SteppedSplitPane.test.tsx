import React from 'react';

import { D2C_WIDGET_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { renderWithSuspense, screen, userEvent, waitFor, within } from 'test-utils';

import SteppedSplitPane from '../SteppedSplitPane';

const renderApp = ({ ...props }) =>
  renderWithSuspense(
    <SteppedSplitPane
      {...D2C_WIDGET_MOCK_RESPONSE}
      isLoading={false}
      queryKey={[]}
      error={null}
      {...props}
    />,
  );

describe('Widgets -> SteppedSplitPane', () => {
  const originalInnerWidth = window.innerWidth;

  // Mock IntersectionObserver
  beforeAll(() => {
    const mockIntersectionObserver = jest.fn();
    mockIntersectionObserver.mockReturnValue({
      observe: jest.fn(),
      unobserve: jest.fn(),
      disconnect: jest.fn(),
    });
    window.IntersectionObserver = mockIntersectionObserver;
  });

  // Clear or reset mocks after each test
  afterEach(() => {
    jest.clearAllMocks(); // or jest.resetAllMocks();
  });

  beforeEach(() => {
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: 1400,
    });
  });

  afterEach(() => {
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: originalInnerWidth,
    });
  });

  it('should render loading skeleton initially', () => {
    renderApp({ isLoading: true });
    expect(screen.getByTestId('content-pane-loader')).toBeInTheDocument();
  });

  it('should select the correct default step initially', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      const defaultStep = screen.getByTestId(
        `step-selector-${D2C_WIDGET_MOCK_RESPONSE.components[0].id}`,
      );
      const stepSelectorHighlight = within(defaultStep).getByTestId('selected-step-highlight');
      expect(stepSelectorHighlight).toBeInTheDocument();
    });
  });

  it('should show the correct number of steps', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      expect(screen.getAllByTestId(/step-selector/)).toHaveLength(
        D2C_WIDGET_MOCK_RESPONSE.components.length,
      );
    });
  });

  it('should render the correct component in the pane section', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      const firstStep = D2C_WIDGET_MOCK_RESPONSE.components[0];
      const firstStepSelector = screen.getByTestId(`step-selector-${firstStep.id}`);
      userEvent.click(firstStepSelector);
      const pitchComponent = screen.getByTestId('d2c-pitch-component');
      expect(pitchComponent).toBeInTheDocument();
    });

    await waitFor(() => {
      const secondStep = D2C_WIDGET_MOCK_RESPONSE.components[1];
      const secondStepSelector = screen.getByTestId(`step-selector-${secondStep.id}`);
      userEvent.click(secondStepSelector);
      const postPitchComponent = screen.getByTestId('d2c-post-pitch-component');
      expect(postPitchComponent).toBeInTheDocument();
    });
  });

  it('should show the correct section heading', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      const sectionTitle = screen.getByText(D2C_WIDGET_MOCK_RESPONSE.title);
      const sectionDescription = screen.getByText(D2C_WIDGET_MOCK_RESPONSE.description);

      expect(sectionTitle).toBeInTheDocument();
      expect(sectionDescription).toBeInTheDocument();
    });
  });

  it('should show step optimied title and text if step is selected and data is present', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      const defaultStep = screen.getByTestId(
        `step-selector-${D2C_WIDGET_MOCK_RESPONSE.components[0].id}`,
      );
      const stepoptimisedSubText = within(defaultStep).getByText('Better');
      const stepoptimisedTitle = within(defaultStep).getByText("Customer's Acquisition");

      expect(stepoptimisedSubText).toBeInTheDocument();
      expect(stepoptimisedTitle).toBeInTheDocument();
    });
  });

  it.skip('should show the correct title and badge value', async () => {
    renderApp({ isLoading: false });
    await waitFor(() => {
      D2C_WIDGET_MOCK_RESPONSE.components.forEach((component) => {
        const step = screen.getByTestId(`step-selector-${component.id}`);
        const stepTitle = within(step).getByText(component.title);
        const stepBadge = within(step).getByText(component.description);
        expect(stepTitle).toBeInTheDocument();
        expect(stepBadge).toBeInTheDocument();
      });
    });
  });
});

// TODO: Add error Scenario tests post internal release
