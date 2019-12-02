import Form from 'common/new-ui/Form';

import SelectConfig from './SelectConfig';
import SelectPeriod from './SelectPeriod';

const DEFAULT_SELECTED_PERIOD = 'yesterday';

export default class GenerateReportPanel extends React.PureComponent {
  state = {
    values: {
      selectedPeriod: DEFAULT_SELECTED_PERIOD,
    },
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

  render() {
    const { configs } = this.props;
    const { values } = this.state;
    return (
      <Form onChange={this.onChange}>
        <SelectConfig configs={configs.items} />

        <SelectPeriod
          selectedPeriod={values.selectedPeriod}
          avlblPeriodOptions={defaultPeriodOptions}
          onDateChange={this.onDateChange}
        />
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

const toBeStoredFields = ['selectedPeriod'];
