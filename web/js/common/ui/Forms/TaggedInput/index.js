import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { PowerSelectMultiple } from 'react-power-select';

export default class TaggedInput extends Component {
  handleOptionsChange = (value, select) => {
    if (
      value.length > 1 &&
      (value.charAt(value.length - 1) === ',' || value.charAt(value.length - 1) === ' ')
    ) {
      const result = value.slice(0, -1);
      this.createPills(result, select);
    }
  };

  updateOnBlur = (e, { select }) => {
    const valueOnBlur = e.target.value;

    if (valueOnBlur) {
      const isValidValue = this.createPills(valueOnBlur, select);
      this.props.handleBlur && this.props.handleBlur(valueOnBlur, isValidValue);
    }
  };

  // eslint-disable-next-line consistent-return
  createPills = (value, select) => {
    let data = this.props.input.value || [];
    data = data.slice();

    let isValidEntry;
    if (this.props.validators) {
      for (const validator of this.props.validators) {
        isValidEntry = validator(value);

        if (!isValidEntry) {
          break;
        }
      }
    } else if (this.props.validator) {
      isValidEntry = this.props.validator(value);
    }

    if (isValidEntry) {
      data.push(value);

      this.props.input.onChange(data);

      select.actions.search('');
      select.actions.focus();
    } else {
      return false;
    }
  };

  render() {
    const data = this.props.input.value || [];
    const { selected, options, onSearchInputChange, ...rest } = this.props;
    return (
      <PowerSelectMultiple
        className="TaggedInput"
        selected={data}
        options={data}
        onSearchInputChange={(event, { select }) => {
          this.handleOptionsChange(event.target.value, select);
          if (onSearchInputChange) {
            onSearchInputChange((event, { select }));
          }
        }}
        onBlur={this.updateOnBlur}
        // eslint-disable-next-line no-shadow
        onChange={({ options }) => {
          this.props.input.onChange(options);
        }}
        {...rest}
      />
    );
  }
}

TaggedInput.propTypes = {
  validator: PropTypes.func,
  validators: PropTypes.array,
};
