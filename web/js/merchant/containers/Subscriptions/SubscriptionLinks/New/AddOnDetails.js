import Input from 'component/Input';

import { isPresent } from 'rzp/utils/rzp-utils';

import AddOnItem from './AddOnItem';

export default function NewSubscriptionLinkAddOnDetails({
  items,
  selectedItems,
  fields: { addons },
  internals,
  ...props
}) {
  return (
    <div class="Subscription--New-addons">
      <Input.Check
        data-name="_addOnPresent"
        fieldLabel="I want to add an upfront amount"
        class="Input--noMarginLeft"
        checked={internals._addOnPresent}
      />
      <ol class="list">
        {addons.map((addon, index) => (
          <li key={index}>
            <AddOnItem
              key={index}
              name={`addons.${index}`}
              items={items.items}
              itemsLoading={items.loading}
              onSelectItem={props.onSelectItem(index)}
              selectedItem={addon}
            />
          </li>
        ))}
        {isPresent(addons) &&
          isPresent(addons[addons.length - 1]) && (
            <li class="no-counter">
              <button class="btn btn-link" onClick={props.onAddAddon}>
                Add New Item
              </button>
            </li>
          )}
      </ol>
    </div>
  );
}
