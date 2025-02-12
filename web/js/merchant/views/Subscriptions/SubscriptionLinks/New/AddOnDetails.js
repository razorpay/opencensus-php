import Input from 'common/new-ui/Input';
import { isPresent } from 'common/utils/rzp-utils';
import analytics from '../../analytics';

import AddOnItem from './AddOnItem';

export default function NewSubscriptionLinkAddOnDetails(props) {
  const {
    items,
    fields: { addons },
    internals,
    currency,
    cloneOptions,
    onSelectItem,
    removeAddOn,
    onAddAddon,
  } = props;
  const filteredAddOns = items.items.filter((item) => item.currency === currency);

  return (
    <div className="Subscription--New-addons">
      <Input.Check
        data-name="_addOnPresent"
        fieldLabel="I want to add an upfront amount"
        className="Input--noMarginLeft"
        checked={internals._addOnPresent}
        onBlur={() => {
          analytics.track('subscription.create.addon', cloneOptions);
        }}
      />
      <ol className="list">
        {addons.map((addon, index) => (
          <li key={`${index}-${Math.random()}`}>
            <AddOnItem
              key={index}
              name={`addons.${index}`}
              items={filteredAddOns}
              itemsLoading={items.loading}
              onSelectItem={(...options) => {
                analytics.track('subscription.create.addon_select', cloneOptions);
                onSelectItem(index)(...options);
              }}
              selectedItem={addon}
              currency={currency}
              cloneOptions={cloneOptions}
            />
            <span
              className="remove-btn"
              onClick={(...options) => {
                analytics.track('subscription.create.addon_deselect', cloneOptions);
                removeAddOn(index)(...options);
              }}
            >
              <i className="i i-close" />
            </span>
          </li>
        ))}
        {isPresent(addons) && isPresent(addons[addons.length - 1]) && (
          <li className="no-counter">
            <button
              className="btn btn-link"
              onClick={() => {
                analytics.track('subscription.create.addon', cloneOptions);
                onAddAddon();
              }}
            >
              Add New Item
            </button>
          </li>
        )}
      </ol>
    </div>
  );
}
