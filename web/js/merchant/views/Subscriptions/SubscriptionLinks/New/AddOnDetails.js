import Input from 'common/new-ui/Input';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import { isPresent } from 'common/utils/rzp-utils';

import AddOnItem from './AddOnItem';
import UPIBanner from '../components/UPIBanner';

export default function NewSubscriptionLinkAddOnDetails({
  items,
  selectedItems,
  fields: { addons },
  internals,
  currency,
  ...props
}) {
  const filteredAddOns = items.items.filter(item => item.currency === currency);

  const sumOfAddons = addons.reduce((previous, addonItem) => {
    const totalAmount =
      addonItem.item && addonItem.item.amount * addonItem.quantity;

    return totalAmount + previous;
  }, 0);

  const showUPIUnAvlBanner = sumOfAddons > UPI_AVL_LIMIT;
  return (
    <>
      <div class="Subscription--New-addons">
        <Input.Check
          data-name="_addOnPresent"
          fieldLabel="I want to add an upfront amount"
          class="Input--noMarginLeft"
          checked={internals._addOnPresent}
        />
        <ol class="list">
          {addons.map((addon, index) => (
            <li key={`${index}-${Math.random()}`}>
              <AddOnItem
                key={index}
                name={`addons.${index}`}
                items={filteredAddOns}
                itemsLoading={items.loading}
                onSelectItem={props.onSelectItem(index)}
                selectedItem={addon}
                currency={currency}
              />
              <span class="remove-btn" onClick={props.removeAddOn(index)}>
                <i class="i i-close" />
              </span>
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

      {showUPIUnAvlBanner && <UPIBanner />}
    </>
  );
}
