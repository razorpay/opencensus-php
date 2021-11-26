import React from 'react';
import { connect } from 'react-redux';
import { reduxForm, formValueSelector } from 'redux-form';
import PropTypes from 'prop-types';
import InlineField from 'common/ui/Forms/InlineField';
import InputField from 'common/ui/Forms/InputField';
import TypeAhead from 'common/ui/Select/TypeAhead';
import ItemCreation from 'merchant/views/Invoices/Items/New';
import Amount from 'common/ui/Amount';
import * as ModalActions from 'merchant_common/reducers/modals';
import { findBy, isTaxOfTypeCess, calculateTax } from 'common/utils/rzp-utils';
import Item from 'merchant/models/Item';
import { track } from '../../../ga';

const selector = formValueSelector('newInvoice');
@connect((state) => {
  return {
    session: state.session,
    invoice_line_items: selector(state, 'line_items'),
  };
}, ModalActions)
@reduxForm({
  form: 'newInvoice',
  destroyOnUnmount: false,
})
export default class InvoiceLineItem extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);
    this.state = {};
  }

  get isCurrencyInr() {
    return this.props.invoiceCurrency === 'INR';
  }

  quickCreateItem = ({ searchTerm = '' }) => {
    /**
     * Taxes are only to be shown when GSTIN is present.
     * The size of the modal depends on whether or not taxes are to be shown.
     */
    const showTaxes = Boolean(this.gstin) && this.isCurrencyInr;
    this.props.trackLineItem('item_new');
    this.props.openModal({
      size: showTaxes ? 'regular' : 'small',
      component: (
        <ItemCreation
          saveLabel="Add Item"
          onSave={this.selectItemAndCloseModal}
          item={{
            name: searchTerm,
          }}
          isNew
          showTaxes={showTaxes}
          currency={this.props.invoiceCurrency}
        />
      ),
    });
  };

  /**
   * Returns an instance of Item associated
   * with the line item params.
   * @return {Item}
   */

  getItemFromLineItem = () => {
    const { invoice_line_items, index, items } = this.props;

    const lineItemID = invoice_line_items[index].item_id;
    if (!lineItemID) return false;

    const item = items.filter((_item) => _item.id === lineItemID);
    if (item.length === 1) {
      return item[0];
    }

    return false;
  };

  /**
   * Method that opens the Edit Item modal
   */

  quickEditItem = (e) => {
    e.preventDefault();

    /**
     * Taxes are only to be shown when GSTIN is present.
     * The size of the modal depends on whether or not taxes are to be shown.
     */
    const showTaxes = Boolean(this.gstin) && this.isCurrencyInr;

    // Get Item that is to be edited.
    const selectedOption = this.props.invoice_line_items[this.props.index];
    const item = selectedOption.selectedItem || {};

    this.props.trackLineItem('item_edit');

    this.props.openModal({
      size: showTaxes ? 'regular' : 'small',
      component: (
        <ItemCreation
          saveLabel="Update Item"
          onSave={this.selectItemAndCloseModal}
          item={item}
          showTaxes={showTaxes}
          currency={selectedOption.currency || item.currency}
        />
      ),
    });
  };

  /**
   * Updates item and closes modal.
   * @param {Item} item
   */

  updateItem = (item) => {
    this.updateLineItemRow(item);

    this.props.trackLineItem('existing_item');
    this.props.closeModal();
  };

  selectItemAndCloseModal = (item) => {
    this.props.trackLineItem('itemupdate');
    /**
     * Need to fetch the item again with tax expanded, if there's no `tax` key
     * but a `tax_id` key exists.
     */
    if (!item.tax && item.tax_id) {
      const _item = new Item();
      _item
        .fetch(item.id, {
          'expand[]': 'tax',
        })
        .then((fetchedItem) => {
          this.updateItem(fetchedItem);
        });
    } else {
      this.updateItem(item);
    }
  };

  /**
   * Unsets taxes.
   */
  unsetTaxes = () => {
    const { change, fieldName } = this.props;

    change(`${fieldName}.cess`, null);
    change(`${fieldName}.tax_ids`, null);
  };

  setTaxes = (item) => {
    const { fieldName } = this.props;

    // Set HSN Code
    this.props.change(`${fieldName}.hsn_code`, item.hsn_code || null);

    // Set SAC Code.
    this.props.change(`${fieldName}.sac_code`, item.sac_code || null);

    // Set tax rate.
    this.props.change(`${fieldName}.tax_rate`, item.tax_rate || null);

    // Set taxes.
    const cessTax = this.getCessFromItem(item);
    this.props.change(`${fieldName}.taxes`, cessTax ? [cessTax] : []);

    // Set whether or not the item is tax-inclusive.
    this.props.change(`${fieldName}.tax_inclusive`, item.tax_inclusive || false);
  };

  /**
   * When props change to show whether GST will be used or not,
   * update cess and GST, or unset taxes depending on what is passed.
   * @param {Object} nextProps
   */

  updateLineItemRow = (item) => {
    if (!item) return;

    // Determine whether or not taxes are shown.
    const showTaxes = Boolean(this.gstin) && this.isCurrencyInr;

    const { fieldName } = this.props;

    this.props.change(`${fieldName}.item_id`, item.id || 'NULL'); // since redux-form converts falsy values into empty strings
    this.props.change(`${fieldName}.name`, item.name);
    this.props.change(`${fieldName}.quantity`, 1);
    this.props.change(`${fieldName}.description`, item.description || '');
    this.props.change(`${fieldName}.amount`, item.amount || 0);
    this.props.change(`${fieldName}.amountInINR`, item.amountInINR || '0.00');

    // The tax-related details in the line item if taxes are to be shown.
    if (showTaxes) {
      this.setTaxes(item);
    }

    // Set selected item in state.
    this.props.change(`${fieldName}.selectedItem`, item);
    setTimeout(() => this.updateCessAndGSTSlab());

    /**
     * Focus on the quantity field.
     * Using a try-catch block here because document.querySelector
     * might throw an error if fieldName is weird.
     */
    const elem = document.querySelector(`input[name="${fieldName}.quantity"]`);
    if (elem) {
      setTimeout(() => {
        elem.focus();

        /**
         * Re-set the value because elem.focus() will set the cursor
         * at the beginning of the input element and not at the end
         * of the input element.
         */
        const oldVal = elem.value;
        elem.value = '';
        elem.value = oldVal;
      }, 100);
    }
  };

  calculateLineItemTotal() {
    const fieldItem = this.props.invoice_line_items[this.props.index];
    return (Number(fieldItem.amountInINR) * Number(fieldItem.quantity)).toFixed(2);
  }

  /**
   * Returns the cess rate of an item.
   * @param {Item} item
   * @return {Number}
   */

  getCessFromItem = (item) => {
    // Get cess rate.
    let cess = null;
    if (item && isTaxOfTypeCess(item.tax)) {
      cess = item.tax;
    }
    return cess;
  };

  /**
   * Gets cess from option instead of Item object.
   * @param {Object} option
   * @return {Number}
   */

  getCessFromOption = (option) => {
    const { taxes } = option;

    if (taxes && taxes.length > 0) {
      for (let i = 0; i < taxes.length; i++) {
        if (isTaxOfTypeCess(taxes[i])) {
          return taxes[i];
        }
      }
    }

    return false;
  };

  /**
   * Updates cess and GST Slab.
   * @param {Object} gstSlabs GST Slabs to use (might be given from nextProps)
   */
  updateCessAndGSTSlab(gstSlabs = this.props.gstSlabs) {
    const selectedOption = this.props.invoice_line_items[this.props.index];

    const { fieldName } = this.props;

    // Get Tax object that's a cess and set values.
    const cessTax = this.getCessFromOption(selectedOption);
    if (cessTax && cessTax.rate) {
      this.props.change(`${fieldName}.cess`, cessTax.rate);
    } else {
      this.props.change(`${fieldName}.cess`, null);
    }

    // List of Tax IDs.
    let taxIDs = [];

    // Get GST slab.
    let gstSlab;
    if (
      gstSlabs &&
      (typeof selectedOption.tax_rate !== 'undefined' || selectedOption.tax_rate !== null)
    ) {
      gstSlab = gstSlabs[selectedOption.tax_rate * 100];

      // Add to taxIDs array.
      if (gstSlab && gstSlab.mapping) {
        this.props.change(`${fieldName}.tax_ids`, Object.values(gstSlab.mapping));
        taxIDs = taxIDs.concat(Object.values(gstSlab.mapping));
      }
    }

    // Set tax_ids array.
    if (taxIDs.length > 0) {
      this.props.change(`${fieldName}.tax_ids`, taxIDs);
    } else {
      this.props.change(`${fieldName}.tax_ids`, null);
    }

    this.setState({
      gstSlab,
    });
  }

  /**
   * If an exsiting item (determined by the `id` property) is passed,
   * update cess and GST.
   */
  componentDidMount() {
    this.setSelectedItemAfterMount();

    // Set GSTIN
    const user = this.props.session.user;
    const gstin = user.gstin || user.p_gstin;
    this.gstin = gstin;
  }

  /**
   * Updates taxes and sets the selected item.
   */
  setSelectedItemAfterMount = () => {
    const selectedOption = this.props.invoice_line_items[this.props.index];
    if (selectedOption.id) {
      this.updateCessAndGSTSlab();

      // Update selected item.
      this.props.change(`${this.props.fieldName}.selectedItem`, this.getItemFromLineItem());
    }
  };

  /**
   * Removes an item after confirmation.
   */
  onRemove = () => {
    const { onRemove, index, invoice_line_items } = this.props;
    const selectedOption = invoice_line_items[index];

    track({
      eventAction: 'Delete - Item',
    });

    // Remove without confirmation if item was not added.
    if (!selectedOption.item_id) {
      onRemove(index);
      return;
    }

    const { confirm } = this.context;

    this.props.trackLineItem('item_delete');

    confirm({
      header: 'Remove Item',
      message:
        'Are you sure you want to remove this item from this Invoice? You can always add it back any time.',
      affirmativeLabel: 'Yes, Remove',
      abortLabel: "No, Don't",
      affirmativePendingLabel: 'Removing',
      action: () => onRemove(index),
    });
  };

  /**
   * When props change to show whether GST will be used or not,
   * update cess and GST, or unset taxes depending on what is passed.
   * @param {Object} prevProps
   */

  componentDidUpdate(prevProps) {
    const prevSelectedOption = prevProps.invoice_line_items[prevProps.index];
    const selectedOption = this.props.invoice_line_items[this.props.index];

    /**
     * `this.props.initialize` has been called, meaning the Invoice was saved.
     * This component will not be removed and will not be added again,
     * so invoke `setSelectedItemAfterMount` manually.
     */
    if (selectedOption.id !== prevSelectedOption.id) {
      this.setSelectedItemAfterMount();
    }

    // If GST slabs are provided, or GST is to be applied, update things.
    if (
      this.props.applyTaxes !== prevProps.applyTaxes ||
      this.props.gstSlabs !== prevProps.gstSlabs
    ) {
      setTimeout(() => this.updateCessAndGSTSlab());
    }

    // If GST was removed, unset taxes.
    if (!this.props.applyTaxes && prevProps.applyTaxes) {
      this.unsetTaxes();
    }

    /**
     * Whenever a line-item is removed, for some weird-fucking-reason,
     * the item below it's GST slab text is not picked up, but the text stays the same.
     * The code below is to mitigate that, and has taken from me two hours and my will to live.
     * Using setTimeout because some asshole decided JS has to be async.
     */
    if (this.props.invoice_line_items.length !== prevProps.invoice_line_items.length) {
      if (this.props.applyTaxes) {
        this.updateCessAndGSTSlab();
      } else {
        this.unsetTaxes();
      }
    }
  }

  resetSelectedItemForChangingCurrency = (props) => {
    props.change(`${props.fieldName}.amountInINR`, 0);

    const selectedOption = props.invoice_line_items[props.index];
    const showTaxes = Boolean(this.gstin) && props.invoiceCurrency === 'INR';

    if (selectedOption.selectedItem && showTaxes) {
      this.setTaxes(selectedOption.selectedItem);
    }
  };

  componentWillReceiveProps(nextProps) {
    if (nextProps.invoiceCurrency !== this.props.invoiceCurrency) {
      this.resetSelectedItemForChangingCurrency(nextProps);
    }
  }

  render() {
    const { fieldName, index, disabled, items, invoiceCurrency } = this.props;
    const selectedOption = this.props.invoice_line_items[index];
    const isEmptyRow = !(
      (selectedOption.item_id && selectedOption.item_id !== 'NULL') ||
      selectedOption.name
    );

    const { selectedItem = {} } = selectedOption;

    // Get total.
    const lineItemTotal = this.calculateLineItemTotal();
    const lineItemTotalFloat = parseFloat(lineItemTotal);

    const { gstSlab } = this.state;

    // Calculate HSN, SAC details.
    const itemHSNSAC = selectedOption.hsn_code || selectedItem.sac_code || null;
    let HSNSACLabel = '';
    if (selectedOption.hsn_code) {
      HSNSACLabel = 'HSN';
    } else if (selectedOption.sac_code) {
      HSNSACLabel = 'SAC';
    }

    // Get cess rate.
    const cess = selectedOption.cess;

    const selectedCurrency = selectedItem.currency || selectedOption.currency;

    const applyTaxes = this.props.applyTaxes && selectedCurrency === 'INR'; // Selected Item won't be exists for non-inr items

    return (
      <tr class={`${isEmptyRow ? 'lineItem--empty' : ''} ${disabled ? 'lineItem--disabled' : ''}`}>
        <td class={`lineItem__item ${selectedOption.item_id ? 'lineItem__item--added' : ''}`}>
          <span class="remove-row-action" onClick={this.onRemove}>
            <i class="i-close" />
          </span>

          <div class="item-ac-container">
            <div>
              {selectedOption && selectedOption.item_id && !disabled && (
                <button
                  class="btn btn-sm btn-text btn-purple edit-in-input ps-item__editbtn"
                  onClick={this.quickEditItem}
                >
                  Edit
                </button>
              )}
              {disabled ? (
                <InlineField
                  formName="newInvoice"
                  name={`${fieldName}.name`}
                  component="input"
                  class="material-input"
                  readOnly
                />
              ) : (
                <InlineField
                  keepValueInBG={false}
                  formName="newInvoice"
                  name={`${fieldName}.item_id`}
                  class="material-input"
                  component={TypeAhead}
                  labelWhenSearchTermBlank="Create new Item"
                  labelWhenSearchTermValid="Add ':_searchTerm_:' as an Item"
                  maxSearchTermLength="12"
                  options={items}
                  selected={selectedOption}
                  optionLabelPath="name"
                  placeholder="Select an item"
                  showClear={false}
                  onOptionChange={this.updateLineItemRow}
                  onQuickAdd={this.quickCreateItem}
                  normalizeValue={(value) => {
                    const selected = findBy(items || [], 'id', value) || selectedOption;
                    if (selected) {
                      return selected.name;
                    }
                    return value;
                  }}
                  onOpen={() => {
                    this.props.trackLineItem('item');
                  }}
                />
              )}
            </div>
            <p class="lineItem__description">{selectedOption.description}</p>
            {itemHSNSAC && applyTaxes && (
              <p>
                <span class="light">{HSNSACLabel} - </span>
                <strong>{itemHSNSAC}</strong>
              </p>
            )}
          </div>
        </td>

        <td class="lineItem__amount">
          <InlineField
            keepValueInBG={false}
            formName="newInvoice"
            name={`${fieldName}.amountInINR`}
            component="input"
            class="material-input text-right"
            type="number"
            rightAlign={true}
            disabled={disabled}
          />
          {selectedOption.item_id && applyTaxes && (
            <div class="tax-details">
              {gstSlab &&
                gstSlab.groups.map((group) => (
                  <p key={`${selectedOption.item_id}_${group}`}>
                    {group} @ {gstSlab.perGroup / 10000.0}%
                  </p>
                ))}
              {cess && <p>Cess @ {cess / 100.0}%</p>}
            </div>
          )}
        </td>

        <td class="lineItem__qty">
          <InlineField
            keepValueInBG={false}
            formName="newInvoice"
            name={`${fieldName}.quantity`}
            component={InputField}
            class="material-input text-right"
            type="number"
            min={1}
            rightAlign={true}
            disabled={disabled || isEmptyRow}
            showInlineErrorText={false}
          />
        </td>

        <td class="text-right lineItem__total">
          <div class="item-total">
            <Amount value={lineItemTotal * 100} currency={invoiceCurrency} />
          </div>
          {selectedOption.item_id && applyTaxes && (
            <div class="tax-details">
              {gstSlab &&
                gstSlab.groups.map((group) => (
                  <p key={`${selectedOption.item_id}_${group}_rate`}>
                    {selectedOption.tax_inclusive ? '' : '+ '}
                    <Amount
                      value={
                        (calculateTax(
                          lineItemTotalFloat,
                          selectedOption.tax_rate / 100,
                          selectedOption.tax_inclusive,
                        ) *
                          100) /
                        gstSlab.groups.length
                      }
                      currency={invoiceCurrency}
                    />
                  </p>
                ))}
              {cess && (
                <p>
                  {selectedOption.tax_inclusive ? '' : '+ '}
                  <Amount
                    value={
                      calculateTax(lineItemTotalFloat, cess / 100, selectedOption.tax_inclusive) *
                      100
                    }
                    currency={invoiceCurrency}
                  />
                </p>
              )}
              {(gstSlab || cess) && (
                <div class="tax-calc-details">
                  <p>
                    <em>Tax {selectedOption.tax_inclusive ? 'Inclusive' : 'Exclusive'},</em>
                  </p>
                  <p>
                    <em>Rounded-off</em>
                  </p>
                </div>
              )}
            </div>
          )}
        </td>
      </tr>
    );
  }
}
