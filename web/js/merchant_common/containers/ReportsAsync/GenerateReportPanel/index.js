import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { isPresent } from 'common/utils/rzp-utils';

import SelectConfig from './SelectConfig';
import SelectPeriod from './SelectPeriod';
import SelectFormat from './SelectFormat';
import EmailReport from './EmailReport';

export default class GenerateReportPanel extends React.PureComponent {
  state = {};

  onConfigChange = selectedConfig => {
    this.setState({ selectedConfig });
  };

  onDateChange = (value, name) => {
    const target = { value, name };
    this.onChange({ target });
  };

  onGenerateReport = () => {
    const { selectedConfig } = this.state;
    if (selectedConfig.type === 'custom') {
      return this.generateCustomConfigReport();
    }

    const [startTime, endTime] = this.selectPeriod.getDateRange();
    const emails = this.emailReport.getWrappedInstance().getValue();

    const payload = {
      config_id: selectedConfig.id,
      start_time: startTime,
      end_time: endTime,
      emails: isPresent(emails) ? emails : undefined,
      ...this.selectFormat.getValue(),
    };

    return this.props.onGenerateReport(payload);
  };

  generateCustomConfigReport = () => {
    const { mode } = this.props;
    const selectedConfigId = this.state.selectedConfig.id;
    const { month, year } = this.selectPeriod.getCustomConfigYear();
    console.log({ month, year, selectedConfigId, mode });

    window.open(
      `/${mode}/reports/${selectedConfigId}/?year=${year}&month=${month}`,
      '_blank'
    );
  };

  render() {
    const { configs, customConfigs } = this.props;
    const { selectedConfig } = this.state;
    const allConfigs = [...configs.items, ...customConfigs];

    const isCustomConfig = (selectedConfig || {}).type === 'custom';

    return configs.loading ? (
      <p>Loading...</p>
    ) : (
      <Form onChange={this.onChange}>
        <SelectConfig
          configs={allConfigs}
          onConfigChange={this.onConfigChange}
        />

        <SelectPeriod
          avlblPeriodOptions={defaultPeriodOptions}
          onDateChange={this.onDateChange}
          ref={ref => (this.selectPeriod = ref)}
          isCustomConfig={isCustomConfig}
        />

        <div class="m-t" />
        <Input.Group class="InputGroup--inline">
          <div class="Input-content">
            {/* there is no format option in case of custom configs */}
            {!isCustomConfig && (
              <SelectFormat
                selectedConfigId={(selectedConfig || {}).id}
                allConfigs={configs.items}
                ref={ref => (this.selectFormat = ref)}
              />
            )}

            <EmailReport
              ref={ref => (this.emailReport = ref)}
              emails={this.props.emailReportOptions}
            />
          </div>
        </Input.Group>

        <AsyncBtn.Primary
          pendingState="Requesting..."
          type="submit"
          onClick={this.onGenerateReport}
          disabled={!selectedConfig}
          class="m-t"
        >
          {isCustomConfig ? 'Download' : 'Generate'} Report
        </AsyncBtn.Primary>
      </Form>
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
