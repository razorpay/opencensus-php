import { Component } from 'react';
import { TypeAhead } from 'react-power-select';
import { connect } from 'react-redux';

import NewItem from 'merchant/containers/Items/New';
import Amount from 'rzp/ui/Amount';
import QuickAdd from 'rzp/ui/Select/QuickAdd';

import { isPresent } from 'rzp/utils/rzp-utils';

import { openModal, closeModal } from 'rzp/modules/modals';

import QuantitySelector from './QuantitySelector';

@connect(null, { openModal, closeModal })
export default class AddOnItem extends Component {
  addNewItem = () => {
    const { closeModal, currency } = this.props;

    this.props.openModal({
      size: 'small',
      overlayStyles: { zIndex: 100001 },
      component: (
        <NewItem
          closeModal={closeModal}
          onSave={closeModal}
          currency={currency}
        />
      ),
    });
  };

  render() {
    const props = this.props;
    return (
      <div class="Addon--Item">
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
          afterOptionsComponent={select => (
            <QuickAdd {...select} onClick={this.addNewItem} />
          )}
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
      Total:{' '}
      <Amount
        value={totalAmount}
        currency={currency}
        parentQuerySelector=".Modal-body"
      />
    </p>
  );
}
