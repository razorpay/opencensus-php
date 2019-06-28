import { findBy } from 'rzp/utils/rzp-utils';

import Button from 'component/Button';

import { PowerSelect } from 'react-power-select';

import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default class ReminderOptionSetting extends React.Component {
  handleChange = id => ({ option }) => {
    const newList = [...this.props.selectedReminders];
    newList[id] = option;

    this.props.onChange(newList);
  };

  handleRemove = value => () => {
    const newList = this.props.selectedReminders.filter(
      selectedReminder => selectedReminder.value !== value
    );

    this.props.onChange(newList);
  };

  handleAddButton = () => {
    const { props } = this;

    const newList = [...props.selectedReminders];

    const nextOptions = filterOptions(
      props.remindersList,
      props.selectedReminders
    );

    newList.push(nextOptions[0]);

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
            {selectedReminders.map((selectedOption, idx) => (
              <RemovableSelect
                required
                key={idx}
                name={`${name}_${selectedOption.value}`}
                onChange={this.handleChange(idx)}
                onRemove={this.handleRemove(selectedOption)}
                selected={selectedOption}
                options={filterOptions(
                  remindersList,
                  selectedReminders,
                  selectedOption
                )}
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
    <PowerSelect
      {...otherProps}
      options={options}
      showClear={false}
      searchEnabled={false}
      optionLabelPath="label"
    />

    <span class="close-btn" onClick={onRemove}>
      <i class="i-close" />
    </span>
  </div>
);

function filterOptions(options, selectedReminders, selectedOption) {
  return options.filter(option => {
    if (selectedOption && option.value === selectedOption.value) return true;

    return !findBy(selectedReminders, 'value', option.value);
  });
}
