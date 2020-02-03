import RTracking from 'react-tracking';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { isPresent } from 'common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';
import SelectAccount from 'common/ui/AccountsList';

import SelectConfig from './SelectConfig';
import SelectPeriod from './SelectPeriod';
import SelectFormat from './SelectFormat';
import EmailReport from './EmailReport';

@RTracking(() => window.rzpQ.component('GenerateReportPanel'))
export default class GenerateReportPanel extends React.PureComponent {
  static getDerivedStateFromProps(nextProps, prevState) {
    const { accounts } = nextProps;
    const { selectedAccount } = prevState;
    if (
      accounts &&
      (!selectedAccount && !accounts.loading && isPresent(accounts.accounts))
    ) {
      return {
        selectedAccount: accounts.accounts[0],
      };
    }

    return null;
  }

  state = {};

  onConfigChange = selectedConfig => {
    this.setState({ selectedConfig });
  };

  onAccountChange = selectedAccount => {
    this.setState({ selectedAccount });
  };

  onDateRangeChanges = (startAt, endAt) => {
    this.setState({
      dateRangeError: getDateRangeError(startAt, endAt),
    });
  };

  @RTracking((props, state) => {
    try {
      const { selectedConfig: { id, name, report_type } = {} } = state;
      const { tracking } = props;
      return tracking.trackEvent(
        window.rzpQ.reporting().initiated('reporting.generate_report', {
          config_id: id,
          config_name: name,
          config_report_type: report_type,
        })
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

    const accountId =
      marketplaceConfigTypes.includes(selectedConfig.type) &&
      (!selectedAccount.current ? selectedAccount.id : undefined);

    const payload = {
      config_id: selectedConfig.id,
      start_time: startTime,
      end_time: endTime,
      emails: isPresent(emails) ? emails : undefined,
      ...this.selectFormat.getValue(),
    };

    return this.props.onGenerateReport(payload, accountId);
  };

  generateCustomConfigReport = () => {
    const { mode } = this.props;
    const selectedConfigId = this.state.selectedConfig.id;
    const { month, year } = this.selectPeriod.getCustomConfigYear();

    window.open(
      `/${mode}/reports/${selectedConfigId}/?year=${year}&month=${month}`,
      '_blank'
    );
  };

  render() {
    const { configs, customConfigs, accounts, showSelectAccount } = this.props;
    const { selectedConfig, dateRangeError, selectedAccount } = this.state;
    const allConfigs = [...configs.items, ...customConfigs];

    const isCustomConfig = (selectedConfig || {}).type === 'custom';
    const isFormDisabled = !selectedConfig;

    return configs.loading ? (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    ) : (
      <div className="GenerateReportPanel">
        <div className="m-b">
          You can generate new reports or download from the list of recently
          generated reports
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
            avlblPeriodOptions={defaultPeriodOptions}
            ref={ref => (this.selectPeriod = ref)}
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
                  ref={ref => (this.selectFormat = ref)}
                  isFormDisabled={isFormDisabled}
                />
              )}

              <EmailReport
                ref={ref => (this.emailReport = ref)}
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
              {isCustomConfig ? 'Download' : 'Generate'} Report
            </AsyncBtn.Primary>
          </Input.Group>
        </Form>
      </div>
    );
  }
}

const defaultPeriodOptions = [
  { label: 'Yesterday', name: 'yesterday' },
  { label: 'Last 7 days', name: 'last_7_days' },
  { label: 'Last Month', name: 'last_month' },
  { label: 'Daily', name: 'daily' },
  { label: 'Monthly', name: 'monthly' },
  { label: 'Custom', name: 'dateRange' },
];

const marketplaceConfigTypes = [
  'transactions',
  'payments',
  'refunds',
  'settlements',
];

function getDateRangeError(startAt, endAt) {
  const difference = endAt.diff(startAt, 'days');
  if (difference > 7) {
    return 'Date range cannot exceed period of 7 days';
  } else if (difference < 0) {
    return "Start at date can't exceed end at date";
  }
  return false;
}
