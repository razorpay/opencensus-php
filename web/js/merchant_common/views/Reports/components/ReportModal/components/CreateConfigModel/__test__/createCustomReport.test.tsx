import React from 'react';
import { CreateConfigModel } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/CreateConfigModel';
import { render, screen, userEvent, waitFor } from 'test-utils';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import { OverView } from 'merchant_common/views/Reports/features/Overview';
import { withRouter } from 'common/deprecated/withRouter';
import { REPORT_TEST_DASHBOARD } from 'merchant_common/views/Reports/constants';
import * as posHooks from 'merchant/views/POS/hooks';
import { getOverViewStateWith } from 'merchant_common/views/Reports/features/Overview/__test__/fixtures/index';
import { mockConfigs } from 'merchant_common/views/Reports/redux/__test__/fixtures/configs.fixtures';

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

const reportsCoreState = getOverViewStateWith({
  allConfigs: {
    loading: false,
    error: false,
    data: mockConfigs,
  },
});

describe('CreateConfigModel', () => {
  const useBladeBreakpointsSpy = jest.spyOn(posHooks, 'useBladeBreakpoints');
  useBladeBreakpointsSpy.mockReturnValue({
    isDesktop: true,
    isMobile: false,
    isLargeScreen: true,
    matchedBreakpoint: 'xl',
  });

  const App = withRouter((props) => {
    return <OverView {...props} dashboardType={REPORT_TEST_DASHBOARD} />;
  });
  const OverviewSection = (props) => {
    return <App {...props} />;
  };

  test('Should check whether "Create Custom Report" button is disabled ', async () => {
    render(<OverviewSection />, {
      initialState,
    });
    const button = screen.getByRole('button', { name: /Create Custom Report/i });
    expect(button).toBeDisabled();
  });

  test('Should render and open the modal when isOpen is true', () => {
    render(<CreateConfigModel isOpen={true} setIsOpen={jest.fn()} />);
    expect(screen.getByText('Select Base Report Type')).toBeInTheDocument();
    expect(screen.getByText('Report Name')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Enter the report description')).toBeInTheDocument();
  });

  test('Should not render the modal when isOpen is false', () => {
    render(<CreateConfigModel isOpen={false} setIsOpen={jest.fn()} />);
    expect(screen.queryByText('Select Base Report Type')).not.toBeInTheDocument();
    expect(screen.queryByText('Report Name')).not.toBeInTheDocument();
    expect(screen.queryByText('Report Description')).not.toBeInTheDocument();
  });

  test('Should display error messages when required fields are empty', async () => {
    render(<CreateConfigModel isOpen={true} setIsOpen={jest.fn()} />);
    await userEvent.click(screen.getByTestId('Next Button'));
    expect(screen.getByText('Base Report Type is required')).toBeInTheDocument();
    expect(screen.getByText('Report Name is required')).toBeInTheDocument();
    expect(screen.getByText('Report Description is required')).toBeInTheDocument();
  });

  test('Opens Basic Details form when triggered', async () => {
    render(<OverviewSection />, {
      initialState: {
        ...initialState,
        ...reportsCoreState,
      },
    });

    const button = screen.getByRole('button', { name: /Create Custom Report/i });
    await waitFor(() => expect(button).toBeEnabled());
    await userEvent.click(button);

    expect(screen.getByText('Select Base Report Type')).toBeInTheDocument();
    expect(screen.getByText('Report Name')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Enter the report description')).toBeInTheDocument();
  });

  test('should fill the report name', async () => {
    render(<OverviewSection />, {
      initialState: {
        ...initialState,
        ...reportsCoreState,
      },
    });

    const button = screen.getByRole('button', { name: /Create Custom Report/i });
    await waitFor(() => expect(button).toBeEnabled());
    await userEvent.click(button);
    const ReportNameInput: HTMLInputElement = screen.getByPlaceholderText(
      'Type name of your report here',
    );
    const reportName = 'custom report Name';
    await userEvent.type(ReportNameInput, reportName);
    expect(ReportNameInput.value).toEqual(reportName);
  });

  test('Should check whether the confirm modal opens', async () => {
    render(<OverviewSection />, {
      initialState: {
        ...initialState,
        ...reportsCoreState,
      },
    });

    const button = screen.getByRole('button', { name: /Create Custom Report/i });
    await waitFor(() => expect(button).toBeEnabled());
    await userEvent.click(button);

    const cancelButton = screen.getByRole('button', { name: /Cancel/i });
    await userEvent.click(cancelButton);
    expect(screen.getByText('Discard Changes')).toBeInTheDocument();
  });
});
