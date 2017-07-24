import React, { Component } from 'react';
import { PowerSelectMultiple } from 'react-power-select';
import './TaggedInput.styl';

export default class TaggedInput extends Component {
  handleOptionsChange = (value, select) => {
    if (value.length > 1 && value.charAt(value.length - 1) === ',') {
      let data = this.props.input.value.slice();
      let result = value.slice(0, -1)
      if (this.props.validate(result)) {
        data.push(result);

        this.props.input.onChange(data);

        select.search('');
        select.focus();
      } else {
        // set field invalid
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
        onChange={({options}) => {this.props.input.onChange(options);}}
        {...rest}
      />
    );
  }
}