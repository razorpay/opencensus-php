import { PowerSelect } from 'react-power-select';
import { TypeAhead } from 'react-power-select';

const AddItemActionLabel = ({ options, select }) => (
  <div class="quick-create">
    <i class="icon icon-plus" />
    <span>
      Add Item {select.searchTerm && <b>{select.searchTerm}...</b>}
    </span>
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
