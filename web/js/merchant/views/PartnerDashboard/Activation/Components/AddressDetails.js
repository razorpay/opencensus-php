import React from 'react';
import Input from 'common/new-ui/Input';
import { pinCode } from 'common/utils/validators';
import { states } from 'merchant/helpers/data';

export const stateOptions = ['--Select--'].concat(
  Object.keys(states).map((c) => {
    return {
      name: c,
      label: states[c],
    };
  }),
);

export const validateBothAddressSame = (addressDetails) => {
  const {
    business_registered_address,
    business_registered_pin,
    business_registered_city,
    business_registered_state,
    business_operation_address,
    business_operation_pin,
    business_operation_city,
    business_operation_state,
  } = addressDetails;
  return (
    business_registered_address === business_operation_address &&
    business_registered_pin === business_operation_pin &&
    business_registered_city === business_operation_city &&
    business_registered_state === business_operation_state
  );
};

const AddressDetails = ({
  addressDetails,
  isFormLocked,
  formState,
  onFormChange,
  autoFillFromPinCode,
  commonLockedFields = [],
}) => {
  const isAddressSame = formState?.isOpAddressSameAsRegAddress;

  return (
    <form onChange={onFormChange} className="Form Form--tabular">
      <Input
        name="business_registered_address"
        label="Registered Business Address"
        defaultValue={addressDetails.business_registered_address}
        disabled={isFormLocked || commonLockedFields.includes('business_registered_address')}
        size="small"
        required
        placeholder="Enter Street Address"
        className="Input--capitalize"
      />
      <Input
        type="number"
        name="business_registered_pin"
        label="Registered Business Pincode"
        defaultValue={addressDetails.business_registered_pin}
        disabled={isFormLocked || commonLockedFields.includes('business_registered_pin')}
        size="small"
        required
        validator={pinCode()}
        onBlur={autoFillFromPinCode}
      />
      <Input
        name="business_registered_city"
        label="Registered Business City"
        disabled={isFormLocked || commonLockedFields.includes('business_registered_city')}
        size="small"
        required
        className="Input--capitalize"
        value={formState.business_registered_city}
        autoRender
      />
      <Input.Select
        name="business_registered_state"
        label="Registered Business State"
        size="small"
        options={stateOptions}
        required
        value={formState.business_registered_state || addressDetails.business_registered_state}
        disabled={isFormLocked || commonLockedFields.includes('business_registered_state')}
        autoRender
      />
      <Input.Radio
        name="isOpAddressSameAsRegAddress"
        options={[
          { label: 'No', value: false },
          { label: 'Yes', value: true },
        ]}
        label="Is operational address same as registered address"
        className="Input--vTop Input--capitalize"
        defaultValue={isAddressSame}
        size="small"
        required
      />
      {!isAddressSame && (
        <>
          <Input
            name="business_operation_address"
            label="Operational Business Address"
            defaultValue={addressDetails.business_operation_address}
            disabled={isFormLocked || commonLockedFields.includes('business_operation_address')}
            size="small"
            required
            placeholder="Enter Street Address"
            className="Input--capitalize"
          />
          <Input
            type="number"
            name="business_operation_pin"
            label="Operational Business Pincode"
            defaultValue={addressDetails.business_operation_pin}
            disabled={isFormLocked || commonLockedFields.includes('business_operation_pin')}
            size="small"
            required
            validator={pinCode()}
            onBlur={autoFillFromPinCode}
          />
          <Input
            name="business_operation_city"
            label="Operational Business City"
            defaultValue={addressDetails.business_operation_city}
            disabled={isFormLocked || commonLockedFields.includes('business_operation_city')}
            size="small"
            required
            className="Input--capitalize"
          />
          <Input.Select
            name="business_operation_state"
            label="Operational Business State"
            size="small"
            options={stateOptions}
            required
            disabled={isFormLocked || commonLockedFields.includes('business_operation_state')}
            value={formState.business_operation_state || addressDetails.business_operation_state}
          />
        </>
      )}
    </form>
  );
};

export default AddressDetails;
