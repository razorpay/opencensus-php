import Button from 'component/Button';

import { SelectField } from 'ui/Field';

export default class ReminderOptionSetting extends React.Component {
  constructor(props) {
    super();

    this.name = props.name;

    this.state = {
      [this.name]: [],
    };
  }

  handleChange = id => e => {
    const newList = [...this.state[this.name]];
    newList[id] = e.target.value;

    this.setState({
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
    const { isExpiry, maxSelections, remindersList } = this.props;

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
              options={remindersList}
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

const RemovableSelect = ({
  name,
  options,
  onRemove,
  onChange,
  disabled,
  defaultValue,
}) => (
  <div class="removable-select">
    <SelectField
      required
      name={name}
      value={value}
      disabled={disabled}
      onChange={onChange}
      defaultValue={defaultValue}
    >
      {options.map(({ id, value: label }) => (
        <option key={id} value={id}>
          {label}
        </option>
      ))}
    </SelectField>

    <i class="i-close" onClick={onRemove} />
  </div>
);
