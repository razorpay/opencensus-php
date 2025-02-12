import { Component } from 'react';
import { TypeAhead } from 'react-power-select';
import { connect } from 'react-redux';

import Amount from 'common/ui/Amount';
import QuickAdd from 'common/ui/Select/QuickAdd';
import { isPresent } from 'common/utils/rzp-utils';
import NewItem from 'merchant/views/Invoices/Items/New';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import QuantitySelector from './QuantitySelector';
import analytics from '../../analytics';

class AddOnItem extends Component {
  addNewItem = () => {
    const { closeModal: closeModalcb, currency, cloneOptions } = this.props;

    this.props.openModal({
      size: 'small',
      overlayStyles: { zIndex: 100001 },
      component: (
        <NewItem
          closeModal={closeModalcb}
          onSave={() => {
            analytics.track('subscription.create.addon_create', cloneOptions);
            closeModalcb();
          }}
          currency={currency}
          disableCurrencySelect
          type="addon"
          isSubscriptionItem
        />
      ),
    });
    analytics.track('subscription.create.addon_new', cloneOptions);
  };

  render() {
    const props = this.props;
    return (
      <div className="Addon--Item">
        <TypeAhead
          showClear={false}
          name={`${props.name}.item`}
          options={props.items}
          disabled={props.itemsLoading}
          searchIndices={['name', 'desctiption']}
          placeholder={props.itemsLoading ? 'Loading...' : 'Select an item'}
          optionComponent={ItemOption}
          selectedOptionLabelPath="name"
          onChange={props.onSelectItem}
          selected={props.selectedItem.item}
          className="ps-in-modal"
          afterOptionsComponent={(select) => <QuickAdd {...select} onClick={this.addNewItem} />}
        />

        {isPresent(props.selectedItem.item) && (
          <QuantitySelector
            informativeMessage={getInformativeMessage}
            name={`${props.name}.quantity`}
            quantity={props.selectedItem.quantity}
            rate={props.selectedItem.item.amount}
            currency={props.currency}
          />
        )}
      </div>
    );
  }
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
      Total: <Amount value={totalAmount} currency={currency} parentQuerySelector=".Modal-body" />
    </p>
  );
}

export default connect(null, { openModal, closeModal })(AddOnItem);
