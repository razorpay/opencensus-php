import { TypeAhead } from 'react-power-select';

import Amount from 'rzp/ui/Amount';

import { isPresent } from 'rzp/utils/rzp-utils';

import QuantitySelector from './QuantitySelector';

export default function AddOnItem(props) {
  return (
    <>
      <TypeAhead
        name={`${props.name}.item`}
        options={props.items}
        disabled={props.itemsLoading}
        searchIndices={['name', 'desctiption']}
        placeholder={props.itemsLoading ? 'Loading...' : 'Select an item'}
        optionComponent={ItemOption}
        selectedOptionLabelPath="name"
        onChange={props.onSelectItem}
        selected={props.selectedItem.item}
        class="ps-in-modal"
      />

      {isPresent(props.selectedItem.item) && (
        <QuantitySelector
          informativeMessage={getInformativeMessage}
          name={`${props.name}.quantity`}
          quantity={props.selectedItem.quantity}
          rate={props.selectedItem.item.amount}
        />
      )}
    </>
  );
}

function ItemOption({ option }) {
  return (
    <div className="custom-powerselect-options">
      <p>{option.name}</p>
      <Amount value={option.amount} currency={option.currency} />
    </div>
  );
}

function getInformativeMessage(totalAmount, currency) {
  return (
    <p>
      Total: <Amount value={totalAmount} currency={currency} />
    </p>
  );
}
