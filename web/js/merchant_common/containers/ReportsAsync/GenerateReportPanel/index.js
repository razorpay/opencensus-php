import React from 'react';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { isPresent, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';
import SelectAccount from 'common/ui/AccountsList';

import SelectConfig from './SelectConfig';
import SelectPeriod from './SelectPeriod';
import SelectFormat from './SelectFormat';
import EmailReport from './EmailReport';

const marketplaceConfigTypes = ['transactions', 'payments', 'refunds', 'settlements'];

@RTracking(() => window.rzpQ.component('GenerateReportPanel'))
export default class GenerateReportPanel extends React.PureComponent {
  static defaultProps = {
    customConfigs: [],
  };

  static getDerivedStateFromProps(nextProps, prevState) {
    const { accounts } = nextProps;
    const { selectedAccount } = prevState;
    if (accounts && !selectedAccount && !accounts.loading && isPresent(accounts.accounts)) {
      return {
        selectedAccount: accounts.accounts[0],
      };
    }

    return null;
  }

  state = {};

  onConfigChange = (selectedConfig) => {
    /* 
      added null check for the selectedConfig as the componet allow search ahead 
      and if no match is found for search and its entered by customer
      it will be null so in that case we are not changin the sate as well as firing any analytics 
    */
    if (selectedConfig) {
      analyticsTrack({
        objectName: 'select report type',
        actionName: 'clicked',
        screen: 'reports',
        properties: {
          location: 'generate reports',
          reportType: selectedConfig.name,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      this.setState({ selectedConfig });
    }
  };

  onAccountChange = (selectedAccount) => {
    analyticsTrack({
      objectName: 'account selection',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: selectedAccount,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.setState({ selectedAccount });
  };

  onDateRangeChanges = (startAt, endAt) => {
    this.setState({
      dateRangeError: getDateRangeError(startAt, endAt),
    });
  };

  // eslint-disable-next-line consistent-return
  @RTracking((props, state) => {
    try {
      const { selectedConfig: { id, name, report_type } = {} } = state;
      const { tracking } = props;
      return tracking.trackEvent(
        window.rzpQ.reporting().initiated('reporting.generate_report', {
          config_id: id,
          config_name: name,
          config_report_type: report_type,
        }),
      );
    } catch (err) {
      console.error({ err });
    }
  })
  onGenerateReport = () => {
    const { selectedConfig, selectedAccount = {} } = this.state;
    if (selectedConfig.type === 'custom') {
      return this.generateCustomConfigReport();
    }

    const [startTime, endTime] = this.selectPeriod.getDateRange();
    const emails = this.emailReport.getWrappedInstance().getValue();

    analyticsTrack({
      objectName: 'generate report',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        reportType: selectedConfig.name,
        accountSelected: selectedAccount,
        periodStart: startTime,
        periodEnd: endTime,
        formatSelected: this.selectFormat.getValue(),
        emailSelected: !!isPresent(emails),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const accountId =
      marketplaceConfigTypes.includes(selectedConfig.type) &&
      (!selectedAccount.current ? selectedAccount.id : undefined);

    const payload = {
      config_id: selectedConfig.id,
      start_time: startTime,
      end_time: endTime,
      emails: isPresent(emails) ? emails : undefined,
      ...this.selectFormat.getValue(),
      ...this.getCustomPayloadForConfigs(),
    };

    return this.props.onGenerateReport(payload, accountId);
  };

  getCustomPayloadForConfigs() {
    const customPayload = {};

    // Request to '/logs' does send mode in url. But for payment links service, mode is needed to be sent explicitly in body
    if (this.state.selectedConfig.type === 'paymentlinksv2') {
      customPayload.template_overrides = {
        filters: {
          paymentlinksv2: {
            mode: {
              op: 'IN',
              values: [this.props.mode],
            },
          },
        },
        file_meta: {
          extension: this.selectFormat?.state?.value,
        },
      };
    }

    return customPayload;
  }

  generateCustomConfigReport = () => {
    const { mode } = this.props;
    const selectedConfigId = this.state.selectedConfig.id;
    const { month, year } = this.selectPeriod.getCustomConfigYear();

    window.open(`/${mode}/reports/${selectedConfigId}/?year=${year}&month=${month}`, '_blank');
  };

  render() {
    const {
      configs,
      customConfigs,
      accounts,
      showSelectAccount,
      onlyDailyOptionsInReferredAccounts,
    } = this.props;
    const { selectedConfig, dateRangeError, selectedAccount } = this.state;
    let allConfigs = [...configs.items, ...customConfigs];

    const skipConfigs = [];

    allConfigs = allConfigs.filter((config) => {
      const isInSkipConfigs = skipConfigs.indexOf(config.type) > -1;

      return !isInSkipConfigs;
    }); // Filtering out configs from skipConfigs

    const isCustomConfig = (selectedConfig || {}).type === 'custom';
    const isFormDisabled = !selectedConfig;

    const avlblPeriodOptions = getAvlblPeriodOptions({
      onlyDailyOptionsInReferredAccounts,
      selectedConfig,
    });

    return configs.loading ? (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    ) : (
      <div className="GenerateReportPanel">
        <div className="m-b">
          You can generate new reports or download from the list of recently generated reports
        </div>
        <Form onChange={this.onChange}>
          <SelectConfig
            configs={allConfigs}
            onConfigChange={this.onConfigChange}
            selectedConfig={selectedConfig}
          />

          {showSelectAccount &&
            marketplaceConfigTypes.includes((selectedConfig || {}).type) &&
            (accounts.loading ? (
              'Loading Accounts...'
            ) : (
              <SelectAccount
                accounts={accounts.accounts}
                onChange={this.onAccountChange}
                selectedAccount={selectedAccount || {}}
              />
            ))}

          <SelectPeriod
            selectedConfig={selectedConfig}
            avlblPeriodOptions={avlblPeriodOptions}
            ref={(ref) => (this.selectPeriod = ref)}
            isCustomConfig={isCustomConfig}
            isFormDisabled={isFormDisabled}
            dateRangeError={dateRangeError}
            onDateRangeChanges={this.onDateRangeChanges}
          />

          <Input.Group class="InputGroup--inline">
            <div class="Input-content">
              {/* there is no format option in case of custom configs */}
              {!isCustomConfig && (
                <SelectFormat
                  selectedConfigId={(selectedConfig || {}).id}
                  allConfigs={configs.items}
                  ref={(ref) => (this.selectFormat = ref)}
                  isFormDisabled={isFormDisabled}
                />
              )}

              <EmailReport
                ref={(ref) => (this.emailReport = ref)}
                emails={this.props.emailReportOptions}
                isFormDisabled={isFormDisabled}
              />
            </div>
          </Input.Group>
          <Input.Group>
            <AsyncBtn.Primary
              pendingState="Requesting..."
              type="submit"
              onClick={this.onGenerateReport}
              disabled={isFormDisabled || !!dateRangeError}
              class="m-t"
            >
              {isCustomConfig ? 'Download Report' : 'Generate Report'}
            </AsyncBtn.Primary>
          </Input.Group>
        </Form>
      </div>
    );
  }
}

const defaultPeriodOptions = [
  { label: 'Today', name: 'today' },
  { label: 'Yesterday', name: 'yesterday' },
  { label: 'Last 7 days', name: 'last_7_days' },
  { label: 'Last Month', name: 'last_month' },
  { label: 'Daily', name: 'daily' },
  { label: 'Monthly', name: 'monthly' },
  { label: 'Custom', name: 'dateRange' },
];

const dailyPeriodOptions = [
  { label: 'Today', name: 'today' },
  { label: 'Yesterday', name: 'yesterday' },
  { label: 'Daily', name: 'daily' },
];

function getAvlblPeriodOptions({ onlyDailyOptionsInReferredAccounts, selectedConfig = {} }) {
  const isReferredAccountsAll = (selectedConfig.template || {}).referred_accounts === 'all';
  if (onlyDailyOptionsInReferredAccounts && isReferredAccountsAll) {
    return dailyPeriodOptions;
  }
  return defaultPeriodOptions;
}

function getDateRangeError(startAt, endAt) {
  const difference = endAt.diff(startAt, 'days');
  if (difference < 0) {
    return "Start at date can't exceed end at date";
  }
  return false;
}
