import React from 'react';
import { useQuery } from '@tanstack/react-query';

import EntityOverview from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview';
import { OVERVIEW_TABS } from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import { trackEvent } from 'merchant/views/RiskAndFraud/common/trackEvents';
import { fireEvent, render, screen } from 'test-utils';

import { FRAUD, DISPUTES } from '../../constants';

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

jest.mock('merchant/views/RiskAndFraud/common/trackEvents', () => ({
  trackEvent: jest.fn(),
}));

const sectionRefMock = {
  current: { [FRAUD]: { getBoundingClientRect: jest.fn(() => ({ top: 10 })) } },
};

const setRatioMock = jest.fn();

const renderApp = (props = {}) => {
  render(<EntityOverview setRatio={setRatioMock} sectionRef={{ current: {} }} {...props} />);
};

describe('EntityOverview component - Risk Visibility', () => {
  beforeAll(() => {
    window.scrollTo = jest.fn();
    (useQuery as jest.Mock).mockReturnValue({
      data: undefined,
      isFetching: true,
      status: 'loading',
    });
  });

  afterAll(() => {
    jest.clearAllMocks();
    (window.scrollTo as jest.Mock).mockClear();
  });

  describe('Rendering', () => {
    test('Should show appropriate title', () => {
      renderApp();
      expect(
        screen.getByText('For International card payments', { exact: false }),
      ).toBeInTheDocument();
    });

    test('Should render all the cards', () => {
      renderApp();
      const tabs = Object.keys(OVERVIEW_TABS);
      tabs.forEach((entity) => {
        expect(screen.getByTestId(`${entity}-card`)).toBeInTheDocument();
      });
    });
  });

  describe('Event Handling', () => {
    test('Should test onClick handleTabChange', () => {
      renderApp();
      const tabButton = screen.getByRole('button', { name: `${DISPUTES}-button` });
      fireEvent.click(tabButton);
      const selctedTab = screen.getByTestId(`${DISPUTES}-card`);
      expect(selctedTab).toHaveAttribute('aria-selected', 'true');
      expect(trackEvent).toHaveBeenCalledWith({
        objectName: 'Top Level metrics',
        properties: { tabName: DISPUTES },
      });
    });

    test('should scroll to the target section on click and call window.scrollTo with expected arguments', () => {
      renderApp({ sectionRef: sectionRefMock });
      const tabButton = screen.getByRole('button', { name: `${FRAUD}-button` });
      fireEvent.click(tabButton);

      const expectedScrollTop = 10 + window.scrollY - 76;
      expect(window.scrollTo).toHaveBeenCalledWith({ top: expectedScrollTop, behavior: 'smooth' });
    });
  });
});
