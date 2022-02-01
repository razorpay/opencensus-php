import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import AmountDisplayField from './Amount/AmountDisplayField';
import UDFDisplayField from './UDF/UDFDisplayField';
import AddUDFButton from './UDF/AddUDFButton';
import AddAmountButton from './Amount/AddAmountButton';
import FormFooter from './FormFooter';
import {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  isFormItemOfTypeAmount,
  reorderFormItems,
  updateReceiptDetails,
} from 'merchant/reducers/wysiwyg';
import { checkIsShiprocketField, constructFieldSchema } from './UDF/helpers';
import { constructAmountField } from './Amount/helpers';
import { sortableContainer, sortableElement } from 'react-sortable-hoc';

import { showNotification } from 'merchant_common/reducers/notifications';
import track from '../track';
import { isMobileDevice } from 'merchant/components/Home/data';

const SortableUDFDisplayField = sortableElement(UDFDisplayField);
const SortableAmountDisplayField = sortableElement(AmountDisplayField);

@sortableContainer
class SortableFormItemsList extends React.Component {
  render() {
    const {
      isPaymentPageEditMode,
      currency,
      FORM_ITEMS,
      isListSorting,
      validateSameTitleExists,
      checkoutOptions,
      onDeleteUDFItem,
      onDeleteAmountItem,
      onSubmitUDFField,
      onSubmitAmountField,
      isShiprocketSetting,
    } = this.props;

    // disable sorting in mobile view
    const isSortingDisabled = isMobileDevice();

    return (
      <div class="FormItems">
        {FORM_ITEMS.map((fi, idx) => {
          if (isFormItemOfTypeAmount(fi)) {
            return (
              <SortableAmountDisplayField
                key={fi.item.name}
                index={idx}
                indexInRenderOrder={idx}
                field={fi}
                currency={currency}
                isListSorting={isListSorting}
                updateData={this.props.updateData}
                onDeleteFormItem={onDeleteAmountItem}
                onSubmitAmountField={onSubmitAmountField}
                validateSameTitleExists={validateSameTitleExists}
                isPaymentPageEditMode={isPaymentPageEditMode}
                disabled={isSortingDisabled}
              />
            );
          } else {
            return (
              <SortableUDFDisplayField
                key={fi.name}
                index={idx}
                indexInRenderOrder={idx}
                field={fi}
                isListSorting={isListSorting}
                checkoutOptions={checkoutOptions}
                onDeleteFormItem={onDeleteUDFItem}
                onSubmitUDFField={onSubmitUDFField}
                validateSameTitleExists={validateSameTitleExists}
                disabled={isSortingDisabled}
                isShiprocket={checkIsShiprocketField(isShiprocketSetting, fi.name)}
              />
            );
          }
        })}
      </div>
    );
  }
}

@connect((state) => ({ ...state.wysiwyg }), {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  reorderFormItems,
  updateReceiptDetails,
  showNotification,
})
@RTracking(() => window.rzpQ.component('wysiwyg_view'))
export default class View extends React.PureComponent {
  state = {
    isListSorting: false,
    totalAmountItems: null,
  };

  componentDidUpdate(prevProps) {
    const { paymentPageEntity: prevPaymentPageEntity } = prevProps;
    const { paymentPageEntity: curPaymentPageEntity } = this.props;

    const isPageNavigatedToOtherId =
      !prevPaymentPageEntity ||
      !curPaymentPageEntity ||
      prevPaymentPageEntity.id !== curPaymentPageEntity.id;
    const isSamePageButDataFetchedAfterwards =
      prevPaymentPageEntity.id === curPaymentPageEntity.id &&
      !prevPaymentPageEntity.payment_page_items;

    if (
      (isPageNavigatedToOtherId || isSamePageButDataFetchedAfterwards) &&
      curPaymentPageEntity.payment_page_items
    ) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({
        totalAmountItems: curPaymentPageEntity.payment_page_items.length,
      });
    }

    // Perform sanity check for input field in Receipt settings
    if (this.isUDFTouched) {
      this.resetUDFTouched();

      const formItems = this.props.FORM_ITEMS;
      const receipt = curPaymentPageEntity.receipt;

      if (receipt.selected_udf_field) {
        const validFormItem = formItems.find((field) => {
          const isFieldStillPresent =
            !field.hasOwnProperty('entity') && field.name === receipt.selected_udf_field;

          return isFieldStillPresent;
        });

        if (!validFormItem) {
          // Update Receipt settings

          this.props.showNotification({
            type: 'info',
            message: 'Your input field is modified. Update your Receipt Settings.',
          });

          this.props.updateReceiptDetails({
            ...receipt,
            selected_udf_field: '', // Only modify this key in receipt
          });
        }
      }
    }
  }

  onSubmitAmountField = (formData, indexInFormItems) => {
    const amountItem = constructAmountField(formData);

    this.props.updateInFormItems({
      formItem: amountItem,
      index: indexInFormItems,
    });

    this.setState((prevState) => ({
      totalAmountItems: prevState.totalAmountItems + 1,
    }));
  };

  onDeleteAmountItem = (indexInFormItems) => {
    this.props.deleteInFormItems(indexInFormItems);

    this.setState((prevState) => ({
      totalAmountItems: prevState.totalAmountItems - 1,
    }));
  };

  onDeleteUDFItem = (indexInFormItems) => {
    this.setUDFTouched();
    this.props.deleteInFormItems(indexInFormItems);
  };

  onSubmitUDFField = (formData, indexInFormItems, isCheckoutOption) => {
    // console.log('FORM DATA.....', formData);
    const fieldSchema = constructFieldSchema(formData);
    // console.log('FIELD SCHEMA...', fieldSchema);

    if (!fieldSchema || (fieldSchema.enum && (!formData.enum || !formData.enum.length))) {
      throw new Error('Invalid field data');
    }

    if (fieldSchema.enum) {
      fieldSchema.enum = formData.enum.concat();
    }

    // Remap the email and phone in checkout_options as per their updated label
    if (isCheckoutOption) {
      const checkoutOptions = this.props.paymentPageEntity.settings.checkout_options;
      checkoutOptions[fieldSchema.pattern] = fieldSchema.name; // Update the key

      this.props.updateData({
        settings: { checkout_options: checkoutOptions },
      });
    }

    this.setUDFTouched();

    this.props.updateInFormItems({
      formItem: fieldSchema,
      index: indexInFormItems,
    });
  };

  setUDFTouched() {
    this.isUDFTouched = true;
  }

  resetUDFTouched() {
    this.isUDFTouched = false;
  }

  validateSameTitleExists = (title, fieldSelfIndex) => {
    const allFieldsTitles = this.props.FORM_ITEMS.map((f) => {
      return isFormItemOfTypeAmount(f) ? f.item.name.toLowerCase() : f.title.toLowerCase();
    });

    const sameTitleIndex = allFieldsTitles.indexOf(title.toLowerCase());

    if (sameTitleIndex > -1 && sameTitleIndex !== fieldSelfIndex) {
      return true;
    }

    return false;
  };

  onSortStart = (_) => {
    this.setState({
      isListSorting: true,
    });

    track.wysiwyg.reorderField();
  };

  onSortEnd = (_) => {
    this.setState({
      isListSorting: false,
    });

    this.props.reorderFormItems(_);
  };

  getSortableHelperContainer() {
    return document.getElementById('draggableElementsContainer');
  }

  render() {
    const { paymentPageEntity, FORM_ITEMS } = this.props;

    if (!paymentPageEntity) {
      return null;
    }

    if (paymentPageEntity.id && typeof paymentPageEntity.title === 'undefined') {
      return (
        <div class="spinner-container">
          <div class="spin-btn large visible" />
        </div>
      );
    }

    const isPaymentPageEditMode = !!paymentPageEntity.id; // If it has reached uptil here, and id exist, then it's edit mode of existing payment page.

    return (
      <div class="UI-form">
        {!this.state.totalAmountItems && (
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
                onSubmitAmountField={(formData) =>
                  this.onSubmitAmountField(formData, -1)
                } /*Added in the starting of form Items*/
                validateSameTitleExists={this.validateSameTitleExists}
                isPaymentPageEditMode={isPaymentPageEditMode}
              />
            </div>
          </div>
        )}

        <SortableFormItemsList
          isPaymentPageEditMode={isPaymentPageEditMode}
          lockAxis="y"
          useDragHandle
          lockToContainerEdges
          helperClass="CreatorManager"
          helperContainer={this.getSortableHelperContainer}
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
          isShiprocketSetting={
            paymentPageEntity?.settings?.partner_webhook_settings?.partner_shiprocket === '1'
          }
        />

        {this.props.isShiprocketOpened && <div class="shiprocket-blank-preview" />}

        <div class="Field Field-add-new" style={{ margin: '32px 0 0' }}>
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
              isPaymentPageEditMode={isPaymentPageEditMode}
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
    );
  }
}
