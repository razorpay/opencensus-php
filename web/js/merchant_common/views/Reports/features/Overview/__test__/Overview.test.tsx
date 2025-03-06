import React from 'react';
import * as overviewApi from 'merchant_common/views/Reports/api/overview';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { OverView } from 'merchant_common/views/Reports/features/Overview';
import {
  mockConfigs,
  mockCustomConfigs,
} from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';
import { withRouter } from 'common/deprecated/withRouter';
import { getOverViewStateWith } from './fixtures';
import { sortCardsByReportType } from 'merchant_common/views/Reports/utils/commonUtils';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import {
  REPORT_OVERVIEW_LOADING_SKELETONS_COUNT,
  REPORT_TEST_DASHBOARD,
} from 'merchant_common/views/Reports/constants';
import { useCreateConfigModal } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';

const sortedConfigs = sortCardsByReportType(mockConfigs);
const sortedCustomConfigs = sortCardsByReportType(mockCustomConfigs);

const standardConfigs = mockConfigs;
const userConfigs = mockCustomConfigs;

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

jest.mock(
  'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/store/createConfigModalStore',
  () => ({
    useCreateConfigModal: jest.fn(),
  }),
);

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

  test('should render all the configs if allConfigs data is loaded in redux state', async () => {
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
    const button = screen.getByRole('button', { name: /Create Custom Report/i });
    expect(button).toBeEnabled();
  });

  test('should render configs wrt its report type if user selects Standard Reports in filter dropdown', async () => {
    (useCreateConfigModal as unknown as jest.Mock).mockReturnValue(standardConfigs);
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
    await handleFilterDropdownSelectionMock('Standard Reports');
    sortedConfigs.forEach(([type, configs]) => {
      const refTypeConfigsContainer = screen.getByLabelText(
        `Standard Configs Ordered By ${type.toUpperCase()}`,
      );
      expect(refTypeConfigsContainer).toBeInTheDocument();
      expect(refTypeConfigsContainer.childNodes.length).toEqual(
        (configs as BaseConfigType[]).length,
      );
    });
  });

  test('should render configs wrt its report type if user selects Custom Reports in filter dropdown', async () => {
    (useCreateConfigModal as unknown as jest.Mock).mockReturnValue(userConfigs);
    const reportsCoreState = getOverViewStateWith({
      allConfigs: {
        loading: false,
        error: false,
        data: mockCustomConfigs,
      },
    });
    render(<OverviewSection />, {
      initialState: {
        ...initialState,
        ...reportsCoreState,
      },
    });
    await handleFilterDropdownSelectionMock('Custom Reports');
    sortedCustomConfigs.forEach(([type, configs]) => {
      const refTypeConfigsContainer = screen.getByLabelText(
        `Custom Configs Ordered By ${type.toUpperCase()}`,
      );
      expect(refTypeConfigsContainer).toBeInTheDocument();
      expect(refTypeConfigsContainer.childNodes.length).toEqual(
        (configs as BaseConfigType[]).length,
      );
    });
  });
});
