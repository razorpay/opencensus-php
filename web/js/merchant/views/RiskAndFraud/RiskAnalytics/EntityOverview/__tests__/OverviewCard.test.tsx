import React from 'react';

import OverviewCard from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/OverviewCard';
import {
  DEFAULT_OVERVIEW_TAB,
  OVERVIEW_TABS,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import { OverviewCardProps } from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/types';
import { mockRatios } from 'merchant/views/RiskAndFraud/RiskAnalytics/__tests__/mocks';
import { FRAUD } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { AnalyticsEntity } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';
import { render, screen, fireEvent, waitFor, userEvent } from 'test-utils';

const handleTabChange = jest.fn();

describe('Tests for Overview card', () => {
  let mockProps: OverviewCardProps;

  beforeEach(() => {
    mockProps = {
      entity: FRAUD as AnalyticsEntity,
      ratios: mockRatios,
      selectedTab: DEFAULT_OVERVIEW_TAB,
      handleTabChange,
    };
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const renderApp = (props = {}) => {
    render(<OverviewCard {...mockProps} {...props} />);
  };

  test('Should show correct title and popover content corresponding to the entity passed', () => {
    renderApp();
    const expectedTitle = OVERVIEW_TABS[FRAUD].title;
    expect(screen.getByText(expectedTitle)).toBeInTheDocument();
  });

  test('Should show correct tooltip content', async () => {
    renderApp();
    const expectedPopoverContent = OVERVIEW_TABS[FRAUD].popoverContent;
    expect(screen.getByTestId('tooltip-interactive-wrapper')).toBeInTheDocument();
    fireEvent.mouseEnter(screen.getByTestId('tooltip-interactive-wrapper'));
    await waitFor(() => {
      expect(screen.getByText(expectedPopoverContent)).toBeInTheDocument();
    });
  });

  test('Should show ratio for the entity', () => {
    renderApp();
    expect(screen.getByText(`${mockRatios.fraud_to_sales_ratio}%`)).toBeInTheDocument();
  });

  test.each([
    [{ fraud_to_sales_ratio: 10, industry_fraud_to_sales_ratio: 12 }, 'Lower'],
    [{ fraud_to_sales_ratio: 12, industry_fraud_to_sales_ratio: 10 }, 'Higher'],
    [{ fraud_to_sales_ratio: 10, industry_fraud_to_sales_ratio: 10 }, 'At par'],
  ])('Should show appropiate label based on the industry average', (ratios, result) => {
    const overrideProps = { ratios };
    renderApp(overrideProps);
    expect(screen.getByText(result, { exact: false })).toBeInTheDocument();
  });

  test('Should call handleTabChange on tab click', async () => {
    renderApp();
    const tabButton = screen.getByRole('button', { name: `${FRAUD}-button` });
    await userEvent.click(tabButton);
    expect(handleTabChange).toHaveBeenCalled();
  });
});
