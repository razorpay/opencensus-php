import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { sortableContainer, sortableElement } from 'react-sortable-hoc';
import AmountDisplayField from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/AmountDisplayField';
import UDFDisplayField from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/UDFDisplayField';
import AddUDFButton from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/AddUDFButton';
import AddAmountButton from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/AddAmountButton';
import FormFooter from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FormFooter';
import {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  reorderFormItems,
  updateReceiptDetails,
  updateMagicData,
} from 'merchant/reducers/wysiwyg';
import {
  checkIsMagicCheckoutField,
  checkIsShiprocketField,
  constructFieldSchema,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers';
import {
  constructAmountField,
  isFormItemOfTypeAmount,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';

import { showNotification } from 'merchant_common/reducers/notifications';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';
import { isMobileDevice } from 'merchant/components/Home/data';
import {
  SEC_REF_ID,
  SEC_REF_ID_MAX_ERROR,
} from 'merchant/views/PaymentPages/PaymentPages/constants';

import { FIXED_FIELDS } from './UDF/helpers/preAddedFields';

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
      showPayerNamePP,
      isBatchPaymentPages,
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
                isBatchPaymentPages={isBatchPaymentPages}
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
                showPayerNamePP={showPayerNamePP}
                isBatchPaymentPages={isBatchPaymentPages}
              />
            );
          }
        })}
      </div>
    );
  }
}

@connect((state) => ({ ...state.wysiwyg, user: state.session.user, org: state.session.org }), {
  updateData,
  deleteInFormItems,
  updateInFormItems,
  reorderFormItems,
  updateReceiptDetails,
  showNotification,
  updateMagicData,
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
    const { user, isBatchPaymentPages, FORM_ITEMS, showNotification } = this.props;
    const fieldSchema = constructFieldSchema(formData);

    if (isBatchPaymentPages) {
      const formItem = FORM_ITEMS[indexInFormItems];
      const secondaryRefIds = FORM_ITEMS?.filter((item) => item?.name?.includes(SEC_REF_ID));

      if (formItem?.name === FIXED_FIELDS.primaryRefId.name) {
        fieldSchema.name = formItem.name; // Preserving the name that got overridden while creating fieldSchema.
      }

      // Check if field is selected as secondary reference id.
      if (formData?.[SEC_REF_ID] === '1') {
        // Not more than 5 secondary reference id can be selected.
        if (secondaryRefIds.length === 5) {
          showNotification({
            type: 'info',
            message: SEC_REF_ID_MAX_ERROR,
          });

          return;
        }

        // Override the same name if it has sec__ref__id prefix.
        if (formItem?.name?.includes?.(SEC_REF_ID)) {
          // Preserving the name that got overridden while creating fieldSchema.
          fieldSchema.name = formItem.name;
        } else {
          // Create a new name with prefix sec__ref__id.
          fieldSchema.name = `${SEC_REF_ID}_${secondaryRefIds.length + 1}`;
        }
      }
    }

    const isPayerNameField = user?.showPayerNamePP && formData?.title === 'Payer Name';

    if (isPayerNameField) {
      fieldSchema.name = 'payer__name';
    }

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

    const { magicCheckout, updateMagicData } = this.props;
    if (magicCheckout?.enabled && checkIsMagicCheckoutField(fieldSchema.name)) {
      updateMagicData({ formModalOpen: true });
    }
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
    const {
      paymentPageEntity,
      FORM_ITEMS,
      magicCheckout,
      updateMagicData,
      user,
      org,
      isBatchPaymentPages,
    } = this.props;
    let _hideDynamicPriceField = user?.hideDynamicPriceFieldPP;
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

    // Always show dynamic price with donation template
    if (paymentPageEntity?.template_type === 'donation') {
      _hideDynamicPriceField = false;
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
                hideDynamicPriceField={_hideDynamicPriceField}
                isBatchPaymentPages={isBatchPaymentPages}
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
          showPayerNamePP={user?.showPayerNamePP}
          isBatchPaymentPages={isBatchPaymentPages}
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
              isMagicCheckoutEnabled={magicCheckout?.enabled}
              updateMagicData={updateMagicData}
              isBatchPaymentPages={isBatchPaymentPages}
            />
            <AddAmountButton
              currency={paymentPageEntity.currency}
              updateData={this.props.updateData}
              onDeleteFormItem={this.onDeleteAmountItem}
              onSubmitAmountField={this.onSubmitAmountField}
              validateSameTitleExists={this.validateSameTitleExists}
              isPaymentPageEditMode={isPaymentPageEditMode}
              hideDynamicPriceField={_hideDynamicPriceField}
              isBatchPaymentPages={isBatchPaymentPages}
            />
          </div>
        </div>

        <FormFooter
          currency={paymentPageEntity.currency}
          paymentButtonLabel={paymentPageEntity.settings.payment_button_label}
          updateData={this.props.updateData}
          isListSorting={this.state.isListSorting}
          securityBrandingLogo={org.security_branding_logo}
        />

        <div id="draggableElementsContainer" />
      </div>
    );
  }
}
