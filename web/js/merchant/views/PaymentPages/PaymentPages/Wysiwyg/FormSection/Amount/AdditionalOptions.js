import React from 'react';

import Button from 'common/new-ui/Button';
import FieldOptionsDropdownWrapper, {
  OptionsItem,
} from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/FieldOptionsDropdown';

import { isFormItemOfTypeLateFee } from './helpers';
import { LATE_FEE_FIELD_TYPES } from './helpers/fieldTypes';

function AdditionalOptions({
  isBatchPaymentPages,
  field,
  onUpdateImage,
  openImageCropper,
  onCloseForm,
  disableSubmit,
  hasDescription,
  toggleDescriptionField,
  isMandatory,
  toggleIsMandatory,
  openAdvancedForm,
  onDeleteField,
  selfIndex,
  setLateFeeType,
  selectedLateFeeType,
}) {
  const handleFeeTypeClick = (feeType) => {
    setLateFeeType(feeType);
  };

  const handleImageClick = () => {
    if (field?.image_url) {
      onUpdateImage(null);
    } else {
      openImageCropper();
    }
  };

  const Image = () => (
    <OptionsItem>
      <div onClick={handleImageClick}>
        <i className="i i-add_image" />
        {field?.image_url ? 'Remove Image' : 'Add Image'}
      </div>
    </OptionsItem>
  );

  const Description = () => (
    <OptionsItem isSelected={Boolean(hasDescription)}>
      <div onClick={toggleDescriptionField}>
        <i className="i i-sort i-fix-sort" />
        {hasDescription ? 'Remove Description' : 'Add Description'}
      </div>
    </OptionsItem>
  );

  const Mandatory = () => (
    <OptionsItem isSelected={!isMandatory}>
      <div onClick={toggleIsMandatory}>
        <i className="i i-optional_mark" />
        {isMandatory ? 'Make it Optional Item' : 'Optional Item'}
      </div>
    </OptionsItem>
  );

  const AdvancedForm = () => (
    <OptionsItem>
      <div onClick={openAdvancedForm}>
        <i className="i i-options" />
        <div>
          Advanced Options
          <div className="subOption">Add quantity, define rules around quantity, etc.</div>
        </div>
      </div>
    </OptionsItem>
  );

  const Delete = ({ label = 'Delete Field' }) => {
    if (selfIndex === undefined || !onDeleteField) return null;
    return (
      <OptionsItem>
        <div className="OptionsDropdown-item--delete" onClick={onDeleteField}>
          <i className="i i-delete" />
          <div>{label}</div>
        </div>
      </OptionsItem>
    );
  };

  const LateFeeType = () => {
    const { flat_fee, per_day_fee } = LATE_FEE_FIELD_TYPES;

    const isFlatFee = flat_fee?.type === selectedLateFeeType;
    const feeType = isFlatFee ? per_day_fee?.type : flat_fee.type;
    const label = isFlatFee
      ? 'Apply as per day late payment charge'
      : 'Apply as total late payment charge';

    return (
      <OptionsItem>
        <div onClick={() => handleFeeTypeClick(feeType)}>
          <i className="i i-optional_mark" />
          {label}
        </div>
      </OptionsItem>
    );
  };

  const getOptions = () => {
    const isLateFeeField = isFormItemOfTypeLateFee(field);

    if (isLateFeeField) {
      return (
        <>
          <Description />
          <LateFeeType />
          <Delete label="Disable Field" />
        </>
      );
    } else if (isBatchPaymentPages) {
      return (
        <>
          <Description />
          <Mandatory />
          <Delete />
        </>
      );
    }

    return (
      <>
        <Image />
        <Description />
        <Mandatory />
        <AdvancedForm />
        <Delete />
      </>
    );
  };

  const options = getOptions();

  return (
    <>
      <FieldOptionsDropdownWrapper
        trigger={
          <Button.Transparent>
            <i className="i i-ellipsis-v" />
          </Button.Transparent>
        }
      >
        {options}
      </FieldOptionsDropdownWrapper>

      <Button.Transparent
        className="base-form-side-btn base-form-cancel"
        type="button"
        onClick={onCloseForm}
      >
        <span>&times;</span>
        Cancel
      </Button.Transparent>

      <Button.Transparent
        className="base-form-side-btn base-form-save"
        type="submit"
        disabled={disableSubmit}
      >
        <span className="icon i-check" />
        Save
      </Button.Transparent>
    </>
  );
}

export default AdditionalOptions;
