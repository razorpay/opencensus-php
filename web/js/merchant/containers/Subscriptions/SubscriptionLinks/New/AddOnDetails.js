import Input from 'component/Input';

import { isPresent } from 'rzp/utils/rzp-utils';

import AddOnItem from './AddOnItem';

export default function NewSubscriptionLinkAddOnDetails({
  items,
  selectedItems,
  ...props
}) {
  return (
    <>
      <Input.Check
        data-name="_addOnPresent"
        fieldLabel="I want to add an upfront amount"
        class="Input--noMarginLeft"
      />
      {props.addons.map((addon, index) => (
        <AddOnItem
          key={index}
          name={`addons.${index}`}
          items={items.items}
          itemsLoading={items.loading}
          onSelectItem={props.onSelectItem(index)}
          selectedItem={addon}
        />
      ))}

      {isPresent(props.addons) && (
        <button class="btn btn-link" onClick={props.onAddAddon}>
          Add New Item
        </button>
      )}
    </>
  );
}
