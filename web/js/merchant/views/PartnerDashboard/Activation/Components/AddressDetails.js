import React from 'react';
import Input, { Label } from 'common/new-ui/Input';
import { pinCode } from 'common/utils/validators';
import { states } from 'merchant/helpers/data';
import FileUpload from 'merchant/components/File/Upload';

const OPTION_AADHAR = { name: 'aadhar', label: 'Aadhar' };
const OPTION_PASSPORT = { name: 'passport', label: 'Passport' };
const OPTION_VOTER_ID = { name: 'voter_id', label: 'Voter Id' };

export const addressProofOptions = [OPTION_AADHAR, OPTION_PASSPORT, OPTION_VOTER_ID];

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
  progress,
  onFormChange,
  onFileUpload,
  onFileClose,
  autoFillFromPinCode,
  commonLockedFields = [],
}) => {
  const selectedAddressProof = formState.address_proof_type || OPTION_AADHAR.name;
  const addressProofFrontLabel = `${selectedAddressProof}_front`;
  const addressProofBackLabel = `${selectedAddressProof}_back`;
  const addressFrontValue = addressDetails && addressDetails[addressProofFrontLabel];
  const addressBackValue = addressDetails && addressDetails[addressProofBackLabel];
  const isAddressSame = formState?.isOpAddressSameAsRegAddress;
  const frontAddressFileName = addressFrontValue && addressFrontValue[0]?.metadata?.file_name;
  const backAddressFileName = addressBackValue && addressBackValue[0]?.metadata?.file_name;
  if (addressFrontValue && addressFrontValue[0]) addressFrontValue[0].name = frontAddressFileName;
  if (addressBackValue && addressBackValue[0]) addressBackValue[0].name = backAddressFileName;

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
        defaultValue={addressDetails.business_registered_city}
        disabled={isFormLocked || commonLockedFields.includes('business_registered_city')}
        size="small"
        required
        className="Input--capitalize"
        value={formState.business_registered_city || addressDetails.business_registered_city}
      />
      <Input.Select
        name="business_registered_state"
        label="Registered Business State"
        size="small"
        options={stateOptions}
        required
        value={formState.business_registered_state || addressDetails.business_registered_state}
        disabled={isFormLocked || commonLockedFields.includes('business_registered_state')}
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
      <Input.Select
        name="address_proof_type"
        label="Address Proof"
        size="small"
        options={addressProofOptions}
        value={selectedAddressProof}
        required
      />

      <div className="Input">
        <Label text="Address Proof Front" />
        <FileUpload
          name={addressProofFrontLabel}
          files={addressFrontValue}
          accept={['jpg', 'png', 'pdf']}
          maxSize={4194304} // 4MB
          progress={progress}
          showCloseBtn
          customClassName="transactionlimit-fileupload Input-content"
          onFileChange={(file) => onFileUpload(file, addressProofFrontLabel)}
          onCloseClick={() => onFileClose(addressFrontValue[0]?.id, addressProofFrontLabel)}
        />
      </div>

      <div className="Input">
        <Label text="Address Proof Back" />
        <FileUpload
          name={addressProofBackLabel}
          files={addressBackValue}
          accept={['jpg', 'png', 'pdf']}
          maxSize={4194304} // 4MB
          progress={progress}
          showCloseBtn
          customClassName="transactionlimit-fileupload Input-content"
          onFileChange={(file) => onFileUpload(file, addressProofBackLabel)}
          onCloseClick={() => onFileClose(addressBackValue[0]?.id, addressProofBackLabel)}
        />
      </div>
    </form>
  );
};

export default AddressDetails;
