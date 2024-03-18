import React from 'react';
import { useQuery } from '@tanstack/react-query';

import EntityOverview from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview';
import { OVERVIEW_TABS } from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import { render, screen } from 'test-utils';

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

jest.mock('merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/OverviewCard', () => ({
  __esModule: true,
  default: ({ entity }) => <div>{entity}</div>,
}));

const setRatioMock = jest.fn();

const renderApp = (props = {}) => {
  render(<EntityOverview setRatio={setRatioMock} {...props} />);
};

describe('Tests for EntityOverview component - Risk Visibility', () => {
  beforeAll(() => {
    (useQuery as any).mockReturnValue({
      data: undefined,
      isFetching: true,
      status: 'loading',
    });
  });

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
      expect(screen.getByText(entity)).toBeInTheDocument();
    });
  });
});
