import React from 'react';
import errorService from '@razorpay/universe-utils/errorService';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { withI18Service } from 'common/i18';
import { AsyncBtn } from 'common/new-ui/Button';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import SelectAccount from 'common/ui/AccountsList';
import Spinner from 'common/ui/Spinner';
import { analyticsTrack } from 'common/utils/analytics';
import { isPresent, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { reportConfigType } from 'merchant_common/containers/ReportsAsync/utils';

import EmailReport from './EmailReport';
import SelectConfig from './SelectConfig';
import SelectFormat from './SelectFormat';
import SelectPeriod from './SelectPeriod';

const marketplaceConfigTypes = ['transactions', 'payments', 'refunds', 'settlements'];
@RTracking(() => window.rzpQ.component('GenerateReportPanel'))
class GenerateReportPanel extends React.PureComponent {
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
      added null check for the selectedConfig as the component allow search ahead
      and if no match is found for search and its entered by customer
      it will be null so in that case we are not changing the state as well as firing any analytics
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
      selfServeTrackInitiate({
        selfServeAction: 'Report Generated',
        page: 'Reports',
        screen: 'Reports',
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
    const { selectedConfig } = this.state;
    const isAggregatedPartnerReport = selectedConfig?.template?.referred_accounts === 'all';
    this.setState({
      dateRangeError: getDateRangeError(startAt, endAt, isAggregatedPartnerReport),
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
    } catch (error) {
      errorService.captureError(error, {
        tags: {
          team: Teams.COMMON,
        },
        rank: Ranks.P2,
      });
    }
  })
  onGenerateReport = () => {
    const { selectedConfig, selectedAccount = {} } = this.state;

    if (selectedConfig.type === 'custom') {
      selfServeTrackInitiate({
        selfServeAction: 'Report Downloaded',
        page: 'Reports',
        screen: 'Reports',
      });
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
    selfServeTrackSuccess({
      selfServeAction: 'Report Downloaded',
      page: 'Reports',
      screen: 'Reports',
    });
  };

  render() {
    const { user, configs, customConfigs, accounts, emailReportOptions, showSelectAccount, i18 } =
      this.props;
    const { selectedConfig, dateRangeError, selectedAccount } = this.state;
    let allConfigs = [...configs.items, ...customConfigs];

    const skipConfigs = [];

    allConfigs = allConfigs.filter((config) => {
      const isInSkipConfigs = skipConfigs.indexOf(config.type) > -1;
      return !isInSkipConfigs;
    }); // Filtering out configs from skipConfigs

    const isCustomConfig = (selectedConfig || {}).type === 'custom';
    const isFormDisabled = !selectedConfig;

    const avlblPeriodOptions = [
      { label: 'Today', name: 'today' },
      { label: 'Yesterday', name: 'yesterday' },
      { label: 'Last 7 days', name: 'last_7_days' },
      { label: 'Last Month', name: 'last_month' },
      { label: 'Daily', name: 'daily' },
      { label: 'Monthly', name: 'monthly' },
      { label: 'Custom', name: 'dateRange' },
    ];

    if (user?.findTag) {
      allConfigs = allConfigs.filter((config) => {
        const REPORT_CONFIG_TYPE = reportConfigType(i18);
        /**
         * For reports, we check whether the corresponding tag is enabled
         * by matching either the name or type. If the tag is enabled,
         * we return false to remove that report from the rendering list,
         * effectively hiding it from view.
         */
        const configType = REPORT_CONFIG_TYPE[config.type];
        const configName = REPORT_CONFIG_TYPE[config.name];
        const i18TagFound = configType || configName;
        if (i18TagFound) return false;
        if (config?.name === 'Monthly Invoice Report' && user.isSupportRole) return false;
        if (config?.name === 'Optimiser Settlements' && user.isOptimizerRZPVASEnabled) return false;
        return true;
      });
    }

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
                emails={emailReportOptions}
                isFormDisabled={isFormDisabled}
              />
            </div>
          </Input.Group>
          <Input.Group>
            <AsyncBtn.Primary
              pendingState="Requesting..."
              type="submit"
              onClick={this.onGenerateReport}
              disabled={isFormDisabled || !!dateRangeError || !emailReportOptions.length}
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

function getDateRangeError(startAt, endAt, aggregatedPartnerReport) {
  const difference = endAt.diff(startAt, 'days');
  if (difference < 0) {
    return "Start at date can't exceed end at date";
  }
  if (difference > 31 && aggregatedPartnerReport) {
    return 'You can only select up to 31 days for this report.';
  }
  return false;
}

export default connect(
  (state) => ({ user: state.session.user }),
  null,
)(withI18Service(GenerateReportPanel));
