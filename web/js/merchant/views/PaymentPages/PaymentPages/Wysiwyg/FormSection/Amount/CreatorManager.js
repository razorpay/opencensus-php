import React from 'react';
import { connect } from 'react-redux';
import CreatorModal from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/CreatorModal';
import BaseForm from './BaseForm';
import AdvancedForm from './AdvancedForm';
import { ImageCropperModal } from './ImageCropper';
import FIELD_TYPES_MAP from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/fieldTypes';
import {
  isMandatoryToBool,
  mapFieldToAmountFieldType,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';
import { getCurrency } from 'common/ui/Amount';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = this.initState;

    get initState() {
      return {
        isBaseFormOpened: false,
        isAdvancedFormOpened: false,
        isImageCropperOpened: false,
        fieldType: mapFieldToAmountFieldType(this.props.field, this.props.countryCode) || null,
        field: this.props.field,
        currency: this.props.currency,
      };
    }

    closeBaseForm = (_) => {
      this.setState(this.initState);
    };

    toggleImageCropper = (forceStatus) => {
      this.setState((prevState) => ({
        ...prevState,
        isImageCropperOpened:
          typeof forceStatus !== 'undefined' ? forceStatus : !prevState.isImageCropperOpened,
      }));
    };

    componentDidUpdate(prevProps) {
      if (prevProps.field !== this.props.field) {
        this.setState({
          field: this.props.field,
        });
      }

      if (prevProps.currency !== this.props.currency) {
        this.setState({
          currency: this.props.currency,
        });
      }
    }

    openBaseForm = (intentFieldType, initWithBaseField) => {
      const newState = {
        isBaseFormOpened: true,
      };

      // Checking if intent is not event
      if (intentFieldType && !intentFieldType.hasOwnProperty('target')) {
        newState.fieldType = intentFieldType;

        if (initWithBaseField) {
          newState.field = initWithBaseField;
        }
      }

      this.setState(newState);
    };

    toggleAdvancedForm = (forcedState) => {
      this.setState((prevState) => ({
        ...prevState,
        isAdvancedFormOpened:
          typeof forcedState !== 'undefined' ? forcedState : !prevState.isAdvancedFormOpened,
      }));
    };

    onSaveImageForm = (imgUrl) => {
      // Check whether to remove image or upload image
      const { field } = this.state;

      this.setState({
        field: {
          ...field,
          image_url: imgUrl,
        },
      });
    };

    onUpdateCurrency = (currency) => {
      // Updating currency only for this amount item. This is to keep Base form and Advanced form consistent
      this.setState({
        currency,
      });
    };

    // To keep BaseForm and AdvancedForm in sync. Helps in adding default value and validators on min_purchase / min_amount.
    onChangeIsMandatory = (mandatory) => {
      const isMandatory = isMandatoryToBool(mandatory);
      const { fieldType, currency, field } = this.state;
      const { countryCode } = this.props;

      const newField = { ...field };
      newField.mandatory = isMandatory;
      const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

      if (isMandatory) {
        // eslint-disable-next-line default-case
        switch (fieldType) {
          case FIELD_TYPES.dynamic_price.key: {
            const minAmountAllowed = i18CurrencyConversionFromMinorUnitToCommonUnit(
              getCurrency(currency).min_value,
              currency,
            );

            if (Number(newField.min_amount) < Number(minAmountAllowed)) {
              newField.min_amount = minAmountAllowed; // Must be at least min payable value as per currency
            }

            break;
          }

          case FIELD_TYPES.multiple_purchase.key: {
            if (Number(newField.min_purchase) === 0) {
              newField.min_purchase = 1; // Must be at least 1 if mandatory field
            }

            break;
          }

          default:
            break;
        }
      }

      this.setState({
        field: newField,
      });
    };

    onSaveBaseForm = (formData) => {
      const { currency, ...restFormData } = formData;

      // Combine data from advanced form
      const combinedFormData = {
        ...this.state.field,
        ...restFormData,
      };

      // Update amount item
      this.props.onSubmitAmountField(combinedFormData, this.props.indexInRenderOrder);

      // Update currency for payment page entity
      this.props.updateData({
        currency,
      });
    };

    onSaveAdvancedForm = (formData) => {
      const { field } = this.state;

      // console.log(formData);

      // TODO: Can be merged with constructAmountField which handles extra cases as well, but not straightforward
      if (formData.hasOwnProperty('min_purchase') && !formData.min_purchase) {
        formData.min_purchase = 0; // Cannot be null (inorder to differentiate field definition from fixed price optional field)
      }

      if (formData.hasOwnProperty('max_purchase') && !Number(formData.max_purchase)) {
        formData.max_purchase = null;
      }

      if (formData.hasOwnProperty('min_amount') && !Number(formData.min_amount)) {
        formData.min_amount = null; // Has to be null, not 0 for proper validation at BE
      }

      if (formData.hasOwnProperty('max_amount') && !Number(formData.max_amount)) {
        formData.max_amount = null;
      }

      // If stock key is not defined, that means, Unlimited is selected.
      formData.stock = formData.stock || null; // Setting key to null explicitly inorder to override previous value, cuz "objects" are merged, not override.

      this.setState({
        field: {
          ...field,
          ...formData,
        },
      });
    };

    render() {
      const {
        indexInRenderOrder,
        validateSameTitleExists,
        onDeleteFormItem,
        isPaymentPageEditMode,
        isBatchPaymentPages,
        countryCode,
        ...restProps
      } = this.props;

      const {
        field,
        currency,
        fieldType,
        isBaseFormOpened,
        isAdvancedFormOpened,
        isImageCropperOpened,
      } = this.state;
      return (
        <div class="CreatorManager">
          {/* eslint-disable-next-line react/jsx-pascal-case */}
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
            currency={currency}
            isBatchPaymentPages={isBatchPaymentPages}
            countryCode={countryCode}
            {...restProps}
          />
          {isBaseFormOpened && (
            <BaseFormModal
              indexInRenderOrder={indexInRenderOrder}
              field={field}
              fieldType={fieldType}
              currency={currency}
              validateSameTitleExists={validateSameTitleExists}
              onSaveForm={this.onSaveBaseForm}
              onDeleteFormItem={onDeleteFormItem}
              closeFormModal={this.closeBaseForm}
              openAdvancedForm={(_) => this.toggleAdvancedForm(true)}
              openImageCropper={(_) => this.toggleImageCropper(true)}
              onUpdateImage={this.onSaveImageForm}
              onUpdateCurrency={this.onUpdateCurrency}
              isPaymentPageEditMode={isPaymentPageEditMode}
              onChangeIsMandatory={this.onChangeIsMandatory}
              isBatchPaymentPages={isBatchPaymentPages}
            />
          )}

          {isAdvancedFormOpened && (
            <AdvancedFormModal
              field={field}
              fieldType={fieldType}
              currency={currency}
              onSaveForm={this.onSaveAdvancedForm}
              closeFormModal={(_) => this.toggleAdvancedForm(false)}
              countryCode={countryCode}
            />
          )}

          {isImageCropperOpened && (
            <ImageCropperModal
              imgUrl={field.image_url}
              onSave={this.onSaveImageForm}
              closeCropperModal={(_) => this.toggleImageCropper(false)}
            />
          )}
        </div>
      );
    }
  }

  return HOC;
}

@connect((state) => ({ user: state.session.user }))
class BaseFormModal extends React.PureComponent {
  onSaveForm = (formData) => {
    this.props.onSaveForm(formData);
    this.props.closeFormModal();
  };

  onDeleteFormItem = () => {
    this.props.onDeleteFormItem(this.props.indexInRenderOrder);
    this.props.closeFormModal();
  };

  render() {
    const {
      indexInRenderOrder,
      field,
      fieldType,
      currency,
      validateSameTitleExists,
      closeFormModal,
      openAdvancedForm,
      openImageCropper,
      onUpdateImage,
      onUpdateCurrency,
      onChangeIsMandatory,
      isPaymentPageEditMode,
      isBatchPaymentPages,
      user,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement allowScroll>
        <BaseForm
          field={field}
          fieldType={fieldType}
          selfIndex={indexInRenderOrder}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={this.onDeleteFormItem}
          openAdvancedForm={openAdvancedForm}
          openImageCropper={openImageCropper}
          onUpdateImage={onUpdateImage}
          onUpdateCurrency={onUpdateCurrency}
          currency={currency}
          isPaymentPageEditMode={isPaymentPageEditMode}
          onChangeIsMandatory={onChangeIsMandatory}
          isBatchPaymentPages={isBatchPaymentPages}
          countryCode={user.merchant.country_code}
        />
      </CreatorModal>
    );
  }
}

class AdvancedFormModal extends React.PureComponent {
  onSaveForm = (formData, fieldType) => {
    this.props.onSaveForm(formData, fieldType);
    this.props.closeFormModal();
  };

  render() {
    const { field, fieldType, currency, closeFormModal, countryCode } = this.props;

    // TODO: Handle currency
    return (
      <CreatorModal class="CreatorModal-AdvancedForm" onClose={closeFormModal}>
        <AdvancedForm
          field={field}
          fieldType={fieldType}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          currency={currency}
          countryCode={countryCode}
        />
      </CreatorModal>
    );
  }
}
