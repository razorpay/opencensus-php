import CreatorModal from '../CreatorModal';
import BaseForm from './BaseForm';
import AdvancedForm from './AdvancedForm';
import { ImageCropperModal } from './ImageCropper';
import FIELD_TYPES from '../../Amount_Fields/fieldTypes';

export default function CreatorManager(_WrappedDisplayFieldComponent) {
  class HOC extends React.PureComponent {
    state = this.initState;

    get initState() {
      return {
        isBaseFormOpened: false,
        isAdvancedFormOpened: false,
        isImageCropperOpened: false,
        fieldType: null,
        field: this.props.field,
      };
    }

    closeBaseForm = _ => {
      this.setState(this.initState);
    };

    toggleImageCropper = forceStatus => {
      this.setState({
        isImageCropperOpened:
          typeof forceStatus !== 'undefined'
            ? forceStatus
            : !this.state.isImageCropperOpened,
      });
    };

    componentDidUpdate(prevProps) {
      if (prevProps.field !== this.props.field) {
        this.setState({
          field: this.props.field,
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

    toggleAdvancedForm = forcedState => {
      this.setState({
        isAdvancedFormOpened:
          typeof forcedState !== 'undefined'
            ? forcedState
            : !this.state.isAdvancedFormOpened,
      });
    };

    onSaveImageForm = imgUrl => {
      // Check whether to remove image or upload image
      const { field } = this.state;

      this.setState({
        field: {
          ...field,
          image_url: imgUrl,
        },
      });
    };

    onSaveBaseForm = formData => {
      const { currency, ...restFormData } = formData;

      // Combine data from advanced form
      const combinedFormData = {
        ...this.state.field,
        ...restFormData,
      };

      // Update amount item
      this.props.onSubmitAmountField(combinedFormData, this.props.index);

      // Update currency for payment page entity
      this.props.updateData({
        currency,
      });
    };

    onSaveAdvancedForm = (formData, fieldType) => {
      const { field } = this.state;

      console.log(formData);

      // Explicitly handle field.mandatory because unlike udf field having field for "required", amount field doesn't have for "mandatory".
      let isMandatory =
        typeof field.mandatory === 'boolean'
          ? field.mandatory
          : Boolean(Number(field.mandatory));

      if (fieldType === FIELD_TYPES.dynamic_price.key) {
        isMandatory = Number(formData.min_amount) > 0; // mandatory only if min_amount > 0
      } else if (fieldType === FIELD_TYPES.multiple_purchase.key) {
        isMandatory = Number(formData.min_purchase) > 0; // mandatory only if min_purchase > 0
      }

      if (formData.hasOwnProperty('min_purchase') && !formData.min_purchase) {
        formData.min_purchase = 0; // Cannot be null (inorder to differentiate field definition from fixed price optional field)
      }

      if (
        formData.hasOwnProperty('max_purchase') &&
        !Number(formData.max_purchase)
      ) {
        formData.max_purchase = null;
      }

      if (
        formData.hasOwnProperty('min_amount') &&
        !Number(formData.min_amount)
      ) {
        formData.min_amount = null; // Has to be null, not 0 for proper validation at BE
      }

      if (
        formData.hasOwnProperty('max_amount') &&
        !Number(formData.max_amount)
      ) {
        formData.max_amount = null;
      }

      this.setState({
        field: {
          ...field,
          ...formData,
          mandatory: isMandatory,
        },
      });
    };

    render() {
      const {
        index,
        currency,
        validateSameTitleExists,
        onDeleteFormItem,
        ...restProps
      } = this.props;

      const {
        field,
        fieldType,
        isBaseFormOpened,
        isAdvancedFormOpened,
        isImageCropperOpened,
      } = this.state;

      return (
        <div class="CreatorManager">
          <_WrappedDisplayFieldComponent
            field={field}
            openBaseForm={this.openBaseForm}
            currency={currency}
            {...restProps}
          />
          {isBaseFormOpened && (
            <BaseFormModal
              index={index}
              field={field}
              fieldType={fieldType}
              currency={currency}
              validateSameTitleExists={validateSameTitleExists}
              onSaveForm={this.onSaveBaseForm}
              onDeleteFormItem={onDeleteFormItem}
              closeFormModal={this.closeBaseForm}
              openAdvancedForm={_ => this.toggleAdvancedForm(true)}
              openImageCropper={_ => this.toggleImageCropper(true)}
              onUpdateImage={this.onSaveImageForm}
            />
          )}

          {isAdvancedFormOpened && (
            <AdvancedFormModal
              field={field}
              fieldType={fieldType}
              currency={currency}
              onSaveForm={this.onSaveAdvancedForm}
              closeFormModal={_ => this.toggleAdvancedForm(false)}
            />
          )}

          {isImageCropperOpened && (
            <ImageCropperModal
              imgUrl={field.image_url}
              onSave={this.onSaveImageForm}
              closeCropperModal={_ => this.toggleImageCropper(false)}
            />
          )}
        </div>
      );
    }
  }

  return HOC;
}

class BaseFormModal extends React.PureComponent {
  onSaveForm = formData => {
    this.props.onSaveForm(formData);
    this.props.closeFormModal();
  };

  onDeleteFormItem = () => {
    this.props.onDeleteFormItem(this.props.index);
    this.props.closeFormModal();
  };

  render() {
    const {
      index,
      field,
      fieldType,
      currency,
      validateSameTitleExists,
      closeFormModal,
      openAdvancedForm,
      openImageCropper,
      onUpdateImage,
    } = this.props;

    return (
      <CreatorModal class="CreatorModal-BaseForm" overElement>
        <BaseForm
          field={field}
          fieldType={fieldType}
          selfIndex={index}
          validateSameTitleExists={validateSameTitleExists}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          onDeleteField={this.onDeleteFormItem}
          openAdvancedForm={openAdvancedForm}
          openImageCropper={openImageCropper}
          onUpdateImage={onUpdateImage}
          currency={currency}
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
    const { field, fieldType, currency, closeFormModal } = this.props;

    // TODO: Handle currency
    return (
      <CreatorModal class="CreatorModal-AdvancedForm" onClose={closeFormModal}>
        <AdvancedForm
          field={field}
          fieldType={fieldType}
          onCloseForm={closeFormModal}
          onSaveForm={this.onSaveForm}
          currency={currency}
        />
      </CreatorModal>
    );
  }
}
