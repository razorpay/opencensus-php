import React from 'react';
import * as overviewApi from 'merchant_common/views/Reports/api/overview';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { OverView } from 'merchant_common/views/Reports/features/Overview';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';
import { withRouter } from 'react-router-dom';
import { getOverViewStateWith } from './fixtures';
import { sortCardsByReportType } from 'merchant_common/views/Reports/utils/commonUtils';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import {
  REPORT_OVERVIEW_LOADING_SKELETONS_COUNT,
  REPORT_TEST_DASHBOARD,
} from 'merchant_common/views/Reports/constants';

const sortedConfigs = sortCardsByReportType(mockConfigs);

const getRecentConfigsSpy = jest.spyOn(overviewApi, 'getRecentConfigs');
getRecentConfigsSpy.mockResolvedValue({
  data: {
    items: mockConfigs,
  },
});

const initialState = {
  session: {
    user: {
      isOrgAllowedFunctionality: () => true,
      findTag: () => true,
      isRevampedReportsEnabled: {
        overviewRecents: true,
      },
    },
  },
};

describe('Overview Section', () => {
  const App = withRouter((props) => {
    return <OverView {...props} dashboardType={REPORT_TEST_DASHBOARD} />;
  });
  const OverviewSection = (props) => {
    return <App {...props} />;
  };

  const handleFilterDropdownSelectionMock = async (optionLabel) => {
    const filterDropdown = screen.getByPlaceholderText('Choose A Filter');
    await userEvent.click(filterDropdown);
    const refOption = screen.getByTestId(optionLabel);
    await userEvent.click(refOption);
  };

  const checkLoadingState = () => {
    const refConfigsContainer = screen.getByLabelText('Configs Loading Skeleton Container');
    expect(refConfigsContainer).toBeInTheDocument();
    expect(refConfigsContainer.childNodes.length).toEqual(REPORT_OVERVIEW_LOADING_SKELETONS_COUNT);
  };

  test('should render configs skeletons when no state is passed without any error', () => {
    render(<OverviewSection />, { initialState });
    checkLoadingState();
    const filterDropdown = screen.getByPlaceholderText('Choose A Filter');
    expect(filterDropdown).toBeInTheDocument();
  });

  test('should call fetchRecentConfigs only when user select recents from dropdown, multiple calls possible', async () => {
    render(<OverviewSection />, {
      initialState,
    });
    await handleFilterDropdownSelectionMock('Recents');
    await handleFilterDropdownSelectionMock('Report Type');
    await handleFilterDropdownSelectionMock('All Reports');
    await handleFilterDropdownSelectionMock('Recents');
    await waitFor(() => expect(getRecentConfigsSpy).toHaveBeenCalledTimes(2));
  });

  test('should render all the configs if allConfigs data is loaded in redux state', () => {
    const reportsCoreState = getOverViewStateWith({
      allConfigs: {
        loading: false,
        error: false,
        data: mockConfigs,
      },
    });
    render(<OverviewSection />, {
      initialState: {
        ...initialState,
        ...reportsCoreState,
      },
    });
    const refConfigsContainer = screen.getByLabelText('All Configs Container');
    expect(refConfigsContainer).toBeInTheDocument();
    expect(refConfigsContainer.childNodes.length).toEqual(mockConfigs.length);
  });

  test('should render configs wrt its report type if user selects report type in filter dropdown', async () => {
    const reportsCoreState = getOverViewStateWith({
      allConfigs: {
        loading: false,
        error: false,
        data: mockConfigs,
      },
    });
    render(<OverviewSection />, {
      initialState: {
        ...initialState,
        ...reportsCoreState,
      },
    });
    await handleFilterDropdownSelectionMock('Report Type');

    sortedConfigs.forEach(([type, configs]) => {
      const refTypeConfigsContainer = screen.getByLabelText(
        `Configs Ordered By ${type.toUpperCase()}`,
      );
      expect(refTypeConfigsContainer).toBeInTheDocument();
      expect(refTypeConfigsContainer.childNodes.length).toEqual(
        (configs as BaseConfigType[]).length,
      );
    });
  });

  test('should render recent configs when api is resolved', async () => {
    render(<OverviewSection />, {
      initialState,
    });
    await handleFilterDropdownSelectionMock('Recents');
    await expect(getRecentConfigsSpy).toHaveBeenCalled();
    expect(screen.getByLabelText('Recently Used Configs Container')).toBeInTheDocument();
  });

  test('should render loading skeletons when getRecentsConfig api returns rejection', async () => {
    render(<OverviewSection />, {
      initialState,
    });
    getRecentConfigsSpy.mockReset().mockImplementation(() => new Promise((_, rej) => rej()));
    await handleFilterDropdownSelectionMock('Recents');
    checkLoadingState();
  });
});
