import React from 'react';

import PopoverContent from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/PopoverContent';
import { OVERVIEW_TABS } from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import { PopoverContentProps } from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/types';
import * as utils from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/utils';
import { FRAUD } from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { render, screen } from 'test-utils';

describe('Tests for PopoverContent', () => {
  let mockProps: PopoverContentProps;

  beforeEach(() => {
    mockProps = {
      valueKey: OVERVIEW_TABS[FRAUD].valueKey,
      actualValue: 70,
      comparedValue: 50,
    };
  });

  const renderApp = (props = {}) => {
    render(<PopoverContent {...mockProps} {...props} />);
  };

  test('Should show correct description returned by getComparisonData function', () => {
    const mockLabel = 'mock label';
    const getComparisonDataSpy = jest.spyOn(utils, 'getComparisonData');
    getComparisonDataSpy.mockReturnValue(mockLabel);
    renderApp();

    expect(getComparisonDataSpy).toHaveBeenCalledWith(
      mockProps.comparedValue,
      mockProps.actualValue,
    );
    expect(
      screen.getByText(`Your ${mockProps.valueKey.replace(/_/g, ' ')} is ${mockLabel}`, {
        exact: false,
      }),
    ).toBeInTheDocument();
  });
});
