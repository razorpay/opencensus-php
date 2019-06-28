import Button from 'component/Button';

import { SelectField } from 'ui/Field';

export default class ReminderOptionSetting extends React.Component {
  constructor(props) {
    super();

    this.name = props.name;

    this.state = {
      options: props.remindersList,
      [this.name]: props.selectedReminders,
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.remindersList.length !== this.props.remindersList.length) {
      this.setState({
        options: remindersList,
      });
    }

    if (nextProps.selectedReminders !== this.prop.selectedReminders) {
      this.setState({
        [this.name]: nextProps.selectedReminders,
      });
    }
  }

  handleChange = id => (e, { value }) => {
    const newList = [...this.state[this.name]];
    newList[id] = value;

    const newOptions = this.state.options.filter(option => option.id !== value);

    this.setState({
      options: newOptions,
      [this.name]: newList,
    });
  };

  handleRemove = value => () => {
    this.setState({
      [this.name]: this.state[this.name].filter(val => val !== value),
    });
  };

  handleAddButton = () => {
    const newList = [...this.state[this.name]];
    newList.push(0);

    this.setState({
      [this.name]: newList,
    });
  };

  render() {
    const { isExpiry, maxSelections } = this.props,
      { options } = this.state;

    const label = isExpiry
      ? 'For links with expiry'
      : 'For links without expiry';

    const showAddBtn =
      maxSelections && this.state[this.name].length < maxSelections;

    return (
      <div class="setting">
        <label>{label}</label>

        <div class="add-to-list">
          {this.state[this.name].map((value, idx) => (
            <RemovableSelect
              required
              key={idx}
              value={value}
              options={options}
              name={`${this.name}_${value}`}
              onChange={this.handleChange(idx)}
              onRemove={this.handleRemove(value)}
            />
          ))}

          {showAddBtn && (
            <Button.Transparent onClick={this.handleAddButton}>
              Add Reminder
            </Button.Transparent>
          )}
        </div>
      </div>
    );
  }
}

const RemovableSelect = ({ options, onRemove, ...otherProps }) => (
  <div class="removable-select">
    <SelectField required {...otherProps}>
      {options.map(({ id, value: label }) => (
        <option key={id} value={id}>
          {label}
        </option>
      ))}
    </SelectField>

    <i class="i-close" onClick={onRemove} />
  </div>
);
