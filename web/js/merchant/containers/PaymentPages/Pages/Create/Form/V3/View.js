import { connect } from 'react-redux';
import AmountDisplayField from './Amount/AmountDisplayField';
import UDFDisplayField from './UDF/UDFDisplayField';
import AddUDFButton from './UDF/AddUDFButton';
import AddAmountButton from './Amount/AddAmountButton';
import EditLayer from '../../EditLayer';

import {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  isFormItemOfTypeAmount,
  reorderFormItems,
} from 'merchant/modules/wysiwyg';
import { constructFieldSchema } from '../UDF_Fields/V3';
import { constructAmountField } from '../Amount_Fields/V3';
import { sortableContainer, sortableElement } from 'react-sortable-hoc';
import { getCurrency } from 'rzp/ui/Amount';
import { arrayMove } from 'common/util';
import CreatorModal from '../V3/CreatorModal';
import Input from 'component/Input';
import Button from 'component/Button';
import { classList } from 'common/util';

const Sortable_UDFDisplayField = sortableElement(UDFDisplayField);
const Sortable_AmountDisplayField = sortableElement(AmountDisplayField);

@sortableContainer
class SortableFormItemsList extends React.Component {
  render() {
    const {
      currency,
      FORM_ITEMS,
      isListSorting,
      validateSameTitleExists,
      checkoutOptions,
      updateData,
      onDeleteUDFItem,
      onDeleteAmountItem,
      onSubmitUDFField,
      onSubmitAmountField,
    } = this.props;

    return (
      <div class="FormItems">
        {FORM_ITEMS.map((fi, idx) => {
          if (isFormItemOfTypeAmount(fi)) {
            return (
              <Sortable_AmountDisplayField
                key={fi.item.name}
                index={idx}
                field={fi}
                currency={currency}
                isListSorting={isListSorting}
                updateData={updateData}
                onDeleteFormItem={onDeleteAmountItem}
                onSubmitAmountField={onSubmitAmountField}
                validateSameTitleExists={validateSameTitleExists}
              />
            );
          } else {
            return (
              <Sortable_UDFDisplayField
                key={fi.name}
                index={idx}
                field={fi}
                isListSorting={isListSorting}
                checkoutOptions={checkoutOptions}
                onDeleteFormItem={onDeleteUDFItem}
                onSubmitUDFField={onSubmitUDFField}
                validateSameTitleExists={validateSameTitleExists}
              />
            );
          }
        })}
      </div>
    );
  }
}

@connect(state => ({ ...state.wysiwyg }), {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  reorderFormItems,
})
export default class View extends React.PureComponent {
  state = {
    isListSorting: false,
    hasAmountItem: null,
  };

  componentWillReceiveProps(nextProps) {
    if (this.props.payment_page_id !== nextProps.payment_page_id) {
      // TODO: Update state.hasAmountItem = false if nextProps. payment_page_id doesn't exist
      // this.onCreatorClose(); // TODO: Important controller point to close all the modals
    }
  }

  onSubmitAmountField = (formData, indexInFormItems) => {
    const amountItem = constructAmountField(formData);

    this.props.updateInFormItems({
      formItem: amountItem,
      index: indexInFormItems,
    });

    this.setState({
      hasAmountItem: this.state.hasAmountItem + 1,
    });
  };

  onDeleteAmountItem = indexInFormItems => {
    this.props.deleteInFormItems(indexInFormItems);

    this.setState({
      hasAmountItem: this.state.hasAmountItem - 1,
    });
  };

  onDeleteUDFItem = indexInFormItems => {
    this.props.deleteInFormItems(indexInFormItems);
  };

  onSubmitUDFField = (formData, indexInFormItems, isCheckoutOption) => {
    // console.log('FORM DATA.....', formData);
    const fieldSchema = constructFieldSchema(formData);
    // console.log('FIELD SCHEMA...', fieldSchema);

    if (
      !fieldSchema ||
      (fieldSchema.enum && (!formData.enum || !formData.enum.length))
    ) {
      throw 'Invalid field data';
    }

    if (fieldSchema.enum) {
      fieldSchema.enum = formData.enum.concat();
    }

    // Remap the email and phone in checkout_options as per their updated label
    if (isCheckoutOption) {
      const checkoutOptions = this.props.paymentPageEntity.settings
        .checkout_options;
      checkoutOptions[fieldSchema.pattern] = fieldSchema.name; // Update the key

      this.props.updateData({
        settings: { checkout_options: checkoutOptions },
      });
    }

    this.props.updateInFormItems({
      formItem: fieldSchema,
      index: indexInFormItems,
    });
  };

  validateSameTitleExists = (title, fieldSelfIndex) => {
    const allFieldsTitles = this.props.FORM_ITEMS.map(f => {
      return isFormItemOfTypeAmount(f) ? f.item.name : f.title;
    });

    const sameTitleIndex = allFieldsTitles.indexOf(title);

    if (sameTitleIndex > -1 && sameTitleIndex !== fieldSelfIndex) {
      return true;
    }
  };

  onSortStart = _ => {
    this.setState({
      isListSorting: true,
    });
  };

  onSortEnd = _ => {
    this.setState({
      isListSorting: false,
    });

    this.props.reorderFormItems(_);
  };

  render() {
    const { paymentPageEntity, FORM_ITEMS } = this.props;

    if (!paymentPageEntity) {
      return null;
    }

    if (
      paymentPageEntity.id &&
      typeof paymentPageEntity.title === 'undefined'
    ) {
      return (
        <div class="spinner-container">
          <div class="spin-btn large visible" />
        </div>
      );
    }

    const hasAmountItem =
      this.state.hasAmountItem !== null
        ? this.state.hasAmountItem
        : paymentPageEntity.payment_page_items &&
          paymentPageEntity.payment_page_items.length;

    return (
      <React.Fragment>
        <div class="UI-form">
          {!hasAmountItem && (
            <div class="Field Field-dummyAmount">
              <div class="Field-label" style={{ opacity: 0.6 }}>
                Amount
              </div>

              <div class="Field-content">
                <AddAmountButton
                  field={{ item: { name: 'Amount' } }}
                  currency={paymentPageEntity.currency}
                  updateData={this.props.updateData}
                  onDeleteFormItem={this.onDeleteFormItem}
                  onSubmitAmountField={formData =>
                    this.onSubmitAmountField(formData, -1)
                  } /*Added in the starting of form Items*/
                  validateSameTitleExists={this.validateSameTitleExists}
                />
              </div>
            </div>
          )}

          <SortableFormItemsList
            lockAxis="y"
            useDragHandle
            lockToContainerEdges
            helperClass="CreatorManager"
            helperContainer={document.getElementById(
              'draggableElementsContainer'
            )}
            onSortEnd={this.onSortEnd}
            onSortStart={this.onSortStart}
            isListSorting={this.state.isListSorting}
            currency={paymentPageEntity.currency}
            FORM_ITEMS={FORM_ITEMS}
            updateData={this.props.updateData}
            checkoutOptions={paymentPageEntity.settings.checkout_options}
            onDeleteUDFItem={this.onDeleteUDFItem}
            onDeleteAmountItem={this.onDeleteAmountItem}
            onSubmitAmountField={this.onSubmitAmountField}
            onSubmitUDFField={this.onSubmitUDFField}
            validateSameTitleExists={this.validateSameTitleExists}
          />

          <div class="Field" style={{ margin: '32px 0 0' }}>
            <div class="Field-label" style={{ opacity: 0.6 }}>
              Add new
            </div>

            <div class="Field-content">
              <AddUDFButton
                onDeleteFormItem={this.onDeleteUDFItem}
                onSubmitUDFField={this.onSubmitUDFField}
                validateSameTitleExists={this.validateSameTitleExists}
              />
              <AddAmountButton
                currency={paymentPageEntity.currency}
                updateData={this.props.updateData}
                onDeleteFormItem={this.onDeleteAmountItem}
                onSubmitAmountField={this.onSubmitAmountField}
                validateSameTitleExists={this.validateSameTitleExists}
              />
            </div>
          </div>

          <FormFooter
            currency={paymentPageEntity.currency}
            paymentButtonLabel={paymentPageEntity.settings.payment_button_label}
            updateData={this.props.updateData}
            isListSorting={this.state.isListSorting}
          />

          <div id="draggableElementsContainer" />
        </div>
      </React.Fragment>
    );
  }
}

class FormFooter extends React.PureComponent {
  state = {
    isEditModalOpened: false,
    paymentButtonLabel: this.props.paymentButtonLabel,
    disableSubmit: false,
  };

  componentDidUpdate(prevProps, prevState) {
    if (
      prevProps.paymentButtonLabel !== this.props.paymentButtonLabel ||
      prevState.isEditModalOpened !== this.state.isEditModalOpened
    ) {
      this.setState({
        paymentButtonLabel: this.props.paymentButtonLabel,
      });
    }
  }

  toggleModal = force => {
    this.setState({
      isEditModalOpened:
        typeof force !== 'undefined' ? force : !this.state.paymentButtonLabel,
    });
  };

  onChangePaymentButtonLabel = e => {
    this.setState(
      {
        paymentButtonLabel: e.target.value,
      },
      _ => {
        let disableSubmit =
          !!this.formFooter.querySelectorAll('.is-invalid').length ||
          !this.state.paymentButtonLabel;

        this.setState({
          disableSubmit,
        });
      }
    );
  };

  savePaymentButtonLabel = () => {
    this.props.updateData({
      settings: {
        payment_button_label: this.state.paymentButtonLabel,
      },
    });

    this.toggleModal(false);
  };

  setRef = el => (this.formFooter = el);

  render() {
    const { currency, isListSorting } = this.props;
    const { isEditModalOpened, paymentButtonLabel, disableSubmit } = this.state;

    const content = (
      <div class="form-footer-payment">
        <img
          id="fin-logo"
          alt="pay-methods"
          src="https://cdn.razorpay.com/static/assets/upi_visa_mc_ae_pc.png"
        />

        <button class="btn btn-gradient">
          {isEditModalOpened
            ? paymentButtonLabel
            : this.props.paymentButtonLabel}{' '}
          <span style={{ marginLeft: 4 }}>
            <b class="currency-symbol">{getCurrency(currency).symbol}</b> 000.00
          </span>
        </button>
      </div>
    );

    return (
      <div id="form-footer" ref={this.setRef}>
        {isEditModalOpened && (
          <CreatorModal class="CreatorModal-BaseForm" overElement>
            <div>
              <Input
                name="payment_button_label"
                required
                maxLength="16"
                pattern="^[0-9a-zA-Z ]+"
                label="Payment Button Label"
                value={paymentButtonLabel}
                onChange={this.onChangePaymentButtonLabel}
                autoFocus
              />
              {content}
            </div>

            <Button.Transparent
              class="base-form-side-btn base-form-cancel"
              type="button"
              onClick={_ => this.toggleModal(false)}
            >
              <span>&times;</span>
              Cancel
            </Button.Transparent>

            <Button.Transparent
              class="base-form-side-btn base-form-save"
              type="button"
              disabled={disableSubmit}
              onClick={this.savePaymentButtonLabel}
            >
              <span class="icon i-check" />
              Save
            </Button.Transparent>
          </CreatorModal>
        )}

        <EditLayer
          class={classList(
            'edit-layer--formFooter',
            isListSorting && 'disable-hover'
          )}
          onClick={_ => this.toggleModal(true)}
        >
          {content}
          <i class="i i-edit" />
        </EditLayer>
      </div>
    );
  }
}
