import React from 'react';
import { connect } from 'react-redux';
import CreatorModal from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/CreatorModal';
import BaseForm from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/BaseForm';
import Alert from 'common/new-ui/Alert';
import Button from 'common/new-ui/Button';
import { setSettingsModal } from 'merchant/reducers/wysiwyg';
import { getAlertMsg } from 'merchant/views/PaymentPages/PaymentPages/helpers';
import { FIXED_FIELDS } from './helpers/preAddedFields';

export default function CreatorManager(WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = {
      isBaseFormOpened: false,
      fieldSchema: null,
    };

    closeBaseForm = (_) => {
      this.setState({
        isBaseFormOpened: false,
        fieldSchema: null,
      });
    };

    openBaseForm = (intentSchema) => {
      const newState = {
        isBaseFormOpened: true,
      };

      // Checking if intent is not event
      if (intentSchema && !intentSchema.hasOwnProperty('target')) {
        newState.fieldSchema = intentSchema;
      }

      this.setState(newState);
    };

    render() {
      const {
        field,
        indexInRenderOrder,
        validateSameTitleExists,
        onDeleteFormItem,
        onSubmitUDFField,
        checkoutOptions,
        isShiprocket,
        showPayerNamePP,
        isBatchPaymentPages,
        ...restProps
      } = this.props;

      let isFieldDeletable = true;
      let isFieldForcedRequired = false; // If so, then no option in dropdown to set the field optional.
      let isCheckoutOption = false;
      let isLabelDisabled = false;
      let isPrimaryField = false;

      if (field) {
        if ([checkoutOptions.email, checkoutOptions.phone].indexOf(field.name) > -1) {
          isCheckoutOption = true;
          isFieldDeletable = false;
          isFieldForcedRequired = true; // Email and Phone cannot be made as Optional field
        } else if (isShiprocket) {
          isFieldDeletable = false;
          isFieldForcedRequired = true;
        }

        // If feature flags are enabled then Payer Name should be  mandatory, non editable & remove delete option
        if (showPayerNamePP && field?.name === 'payer__name') {
          isFieldDeletable = false;
          isFieldForcedRequired = true;
          isLabelDisabled = true;
        }

        if (isBatchPaymentPages) {
          // Primary reference id field
          if (field?.name === FIXED_FIELDS.primaryRefId.name) {
            isFieldDeletable = false;
            isFieldForcedRequired = true;
            isPrimaryField = true;
          }

          // First secondary reference id field
          if (field?.name === FIXED_FIELDS.secondaryRefId.name) {
            isFieldDeletable = false;
            isFieldForcedRequired = true;
          }

          if (field?.name === 'email' || field?.name === 'phone') {
            isFieldDeletable = false;
            isFieldForcedRequired = false;
          }
        }
      }

      return (
        <div class="CreatorManager">
          <WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
            isBatchPaymentPages={isBatchPaymentPages}
            {...restProps}
          />
          {this.state.isBaseFormOpened && (
            <BaseFormModal
              indexInRenderOrder={indexInRenderOrder}
              field={field || this.state.fieldSchema}
              validateSameTitleExists={validateSameTitleExists}
              onSubmitUDFField={onSubmitUDFField}
              onDeleteFormItem={onDeleteFormItem}
              closeFormModal={this.closeBaseForm}
              isFieldDeletable={isFieldDeletable}
              isFieldForcedRequired={isFieldForcedRequired}
              isCheckoutOption={isCheckoutOption}
              isShiprocket={isShiprocket}
              isLabelDisabled={isLabelDisabled}
              isPrimaryField={isPrimaryField}
              isBatchPaymentPages={isBatchPaymentPages}
            />
          )}
        </div>
      );
    }
  }

  return HOC;
}

@connect((state) => ({ user: state.session.user }), {
  setSettingsModal,
})
class BaseFormModal extends React.PureComponent {
  onSaveForm = (formData) => {
    this.props.onSubmitUDFField(
      formData,
      this.props.indexInRenderOrder,
      this.props.isCheckoutOption,
    );
    this.props.closeFormModal();
  };

  onDeleteFormItem = () => {
    this.props.onDeleteFormItem(this.props.indexInRenderOrder);
    this.props.closeFormModal();
  };

  openPageSettings = () => {
    this.props.closeFormModal();
    this.props.setSettingsModal(true);
  };

  render() {
    const {
      field,
      indexInRenderOrder,
      validateSameTitleExists,
      closeFormModal,
      isFieldDeletable,
      isFieldForcedRequired,
      isShiprocket,
      isLabelDisabled,
      isBatchPaymentPages,
      isPrimaryField,
      user,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement allowScroll>
        <BaseForm
          field={field}
          selfIndex={indexInRenderOrder}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={isFieldDeletable ? this.onDeleteFormItem : undefined}
          isFieldForcedRequired={isFieldForcedRequired}
          isShiprocket={isShiprocket}
          isLabelDisabled={isLabelDisabled}
          isBatchPaymentPages={isBatchPaymentPages}
          isPrimaryField={isPrimaryField}
          countryCode={user.merchant.country_code}
        />
        {!isFieldDeletable ? (
          isShiprocket ? (
            <Alert.Warning>
              <b>Mandatory</b> {field.title.toLowerCase()} field to be filled by customers and is
              required to create orders on Shiprocket. To delete this, disable Shiprocket order
              creation from{' '}
              <Button.Transparent class="Button--Link" onClick={this.openPageSettings}>
                Page Settings
              </Button.Transparent>
            </Alert.Warning>
          ) : (
            <Alert.Warning>{getAlertMsg({ isBatchPaymentPages, field })}</Alert.Warning>
          )
        ) : null}
      </CreatorModal>
    );
  }
}
