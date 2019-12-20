import { connect } from 'react-redux';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

import SelectConfig from './SelectConfig';
import SelectPeriod from './SelectPeriod';
import SelectFormat from './SelectFormat';

export default class GenerateReportPanel extends React.PureComponent {
  state = {
    values: {},
  };

  onChange = ({ target }) => {
    const { name, value } = target;

    if (toBeStoredFields.includes(name)) {
      this.setState({
        values: {
          ...this.state.values,
          [name]: value,
        },
      });
    }
  };

  onDateChange = (value, name) => {
    const target = { value, name };
    this.onChange({ target });
  };

  onGenerateReport = () => {
    const [startTime, endTime] = this.selectPeriod.getDateRange();
    const payload = {
      config_id: this.state.values.selectedConfigId,
      start_time: startTime,
      end_time: endTime,
      ...this.selectFormat.getValue(),
    };

    return this.props.onGenerateReport(payload);
  };

  render() {
    const { configs } = this.props;
    const { values } = this.state;
    return configs.loading ? (
      <p>Loading...</p>
    ) : (
      <Form onChange={this.onChange}>
        <SelectConfig configs={configs.items} />

        <SelectPeriod
          selectedPeriod={values.selectedPeriod}
          avlblPeriodOptions={defaultPeriodOptions}
          onDateChange={this.onDateChange}
          ref={ref => (this.selectPeriod = ref)}
        />

        <div class="m-t" />
        <Input.Group class="InputGroup--inline">
          <div class="Input-content">
            <SelectFormat
              selectedConfigId={values.selectedConfigId}
              allConfigs={configs.items}
              ref={ref => (this.selectFormat = ref)}
            />
          </div>
        </Input.Group>

        <AsyncBtn.Primary
          pendingState="Requesting..."
          type="submit"
          onClick={this.onGenerateReport}
          class="m-t"
        >
          Generate Report
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

const toBeStoredFields = ['selectedConfigId', 'selectedFormat'];
