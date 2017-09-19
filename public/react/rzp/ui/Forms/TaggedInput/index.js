import React, { Component, PropTypes } from 'react';
import { PowerSelectMultiple } from 'react-power-select';

export default class TaggedInput extends Component {
  handleOptionsChange = (value, select) => {
    if (value.length > 1 && value.charAt(value.length - 1) === ',') {
      let data = this.props.input.value || [];
      data = data.slice();
      let result = value.slice(0, -1);
      if (this.props.validator(result)) {
        data.push(result);

        this.props.input.onChange(data);

        select.search('');
        select.focus();
      }
    }
  };

  render() {
    let data = this.props.input.value || [];
    let { selected, options, onSearchInputChange, ...rest } = this.props;
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
        onChange={({ options }) => {
          this.props.input.onChange(options);
        }}
        {...rest}
      />
    );
  }
}

TaggedInput.propTypes = {
  validator: PropTypes.func.isRequired,
};
