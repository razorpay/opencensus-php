import Button from 'component/Button';

import { SelectField } from 'ui/Field';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default class ReminderOptionSetting extends React.Component {
  handleChange = id => e => {
    const newList = [...this.props.selectedReminders];
    newList[id] = Number(e.target.value);

    this.props.onChange(newList);
  };

  handleRemove = value => () => {
    const newList = this.props.selectedReminders.filter(val => val !== value);

    this.props.onChange(newList);
  };

  handleAddButton = () => {
    const { props } = this;

    const newList = [...props.selectedReminders];

    const nextOptions = filterOptions(
      props.remindersList,
      props.selectedReminders
    );

    newList.push(nextOptions[0].id);

    this.props.onChange(newList);
  };

  render() {
    const {
      name,
      isExpiry,
      maxSelections,
      selectedReminders,
      remindersList,
    } = this.props;

    const label = isExpiry
      ? 'For links with expiry'
      : 'For links without expiry';

    const showAddBtn =
      maxSelections && selectedReminders.length < maxSelections;

    return (
      <div class="setting">
        <EntityDetailRow label={label}>
          <div class="add-to-list">
            {selectedReminders.map((value, idx) => (
              <RemovableSelect
                required
                key={idx}
                value={value}
                name={`${name}_${value}`}
                onChange={this.handleChange(idx)}
                onRemove={this.handleRemove(value)}
                options={filterOptions(remindersList, selectedReminders, value)}
              />
            ))}

            {showAddBtn && (
              <Button.Transparent onClick={this.handleAddButton}>
                Add Reminder
              </Button.Transparent>
            )}
          </div>
        </EntityDetailRow>
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

function filterOptions(options, selectedReminders, currVal) {
  return options.filter(({ id }) => {
    if (currVal !== undefined && id === currVal) return true;

    return !selectedReminders.includes(id);
  });
}
