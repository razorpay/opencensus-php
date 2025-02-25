import { PowerSelect, TypeAhead } from 'react-power-select';
import React from 'react';

const AddItemActionLabel = ({ options, select }) => (
  <div className="quick-create">
    <i className="i i-plus" />
    <span>Add Item {select.searchTerm && <b>{select.searchTerm}...</b>}</span>
  </div>
);

export default ({ input, meta, selected, onChange, ...otherProps }) => {
  return (
    <TypeAhead
      {...otherProps}
      selected={input.value || selected}
      onChange={(option, select) => {
        option = option || input.value;
        input.onChange(option);
        onChange(option);
      }}
      afterOptionsComponent={AddItemActionLabel}
    />
  );
};
