import React, { Component } from 'react';
import { PowerSelectMultiple } from 'react-power-select';

/*
  Definition: Select dropdown  where you can select multiple values. It can also display pre-selected values
  Example: See "Edit Merchant" modal in merchant details
  Props:
    1. label: Just like field
    2. name: Just like field. The selected value can be accessed using body[name] (comma separated string)
    3. trackBy: each of the selected option is identified by comma separated trackBy (eg - comma separated ids)
    4. options: exhaustive list of options (array)
    5. defaultValue: array of objects. (trackBy key must be present in items of both defaultValue and options)
    6. keys: used to display default custom option component
    7. CustomOptionComponent: You can pass custom component which has access to option(single item from props.options).
        If passing this, DON'T pass keys props, because then you can handle it directly in parent

  Note: Add support for change listener on input, in case you need the listener
*/

const DefaultCustomOptionComponent = ({ option, keys }) => {
  return keys.length > 1 ? (
    <div>{`${option[keys[0]]}: <${option[keys[1]]}>`}</div>
  ) : (
    <div>{option[keys[0]]}</div>
  );
};

export default class MultiSelectField extends Component {
  constructor(props) {
    super(props);
    this.state = { selectedOptions: [] };

    // Pre-populate selectedOptions
    const preSelectedOptions = props.defaultValue;
    if (preSelectedOptions) {
      let tempArray = [];

      // To select option in powerselect, reference of option must be stored.
      // Storing references of all options from exhaustive list, which matches id(/trackBy) of options in defaultValue array.
      tempArray = preSelectedOptions.map(item => {
        const refInOptions = props.options.find(
          option => option[props.trackBy] === item[props.trackBy]
        );

        if (refInOptions) {
          return refInOptions;
        }
      });

      this.state = { selectedOptions: tempArray };
    }
  }

  handleChange = ({ options }) => {
    this.setState({ selectedOptions: options });
  };

  render() {
    const {
      label,
      name,
      placeholder = '',
      required,
      trackBy,
      options,
      keys,
      CustomOptionComponent,
    } = this.props;

    let selectedValue = this.state.selectedOptions.map(
      option => option[trackBy]
    );
    selectedValue = selectedValue.join(',');

    return (
      <div class="field">
        <label class={required ? 'required' : ''}>{label}</label>
        <input
          class="hide"
          name={name}
          value={selectedValue}
          required={required}
          readOnly
        />

        <PowerSelectMultiple
          options={options}
          class="multi-select"
          selected={this.state.selectedOptions}
          optionLabelPath={trackBy}
          optionComponent={
            CustomOptionComponent ? (
              CustomOptionComponent
            ) : (
              <DefaultCustomOptionComponent keys={keys} />
            )
          }
          onChange={this.handleChange}
          placeholder={placeholder}
        />
      </div>
    );
  }
}
