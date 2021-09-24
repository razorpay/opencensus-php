import React, { useState, useEffect } from 'react';
import Input from 'common/new-ui/Input';
import {
  validateCompanyPAN,
  name,
  validateCompanyAB,
  validatePersonalPAN,
  validateIFSC,
} from 'common/utils/validators';
import { getDetailsForIFSC, isValidGSTIN } from 'common/utils/rzp-utils';
import {
  getBusinessTypeInfo,
  BusinessTypeOptions,
  getBusinessNameInfo,
  getAccountNumberInfo,
  isUnregisteredBusiness,
  displayCompanyPAN,
  PROPRIETORSHIP,
} from '../utils/ActivationUtils';

const BusinessDetails = ({
  businessDetails,
  isFormLocked,
  formState,
  onFormChange,
  commonLockedFields = [],
}) => {
  const [currentBusinessType, setCurrentBusinessType] = useState(businessDetails.business_type);
  const [isUnregistered, setIsUnregistered] = useState();
  const [shouldShowGSTin, setShouldShowGSTin] = useState(false);
  const reBankAccountEl = React.useRef(null);

  useEffect(() => {
    const currentHasGSTin = formState.has_gstin || businessDetails.has_gstin;
    const showGSTInInput = !isUnregistered && currentHasGSTin === '0';
    setShouldShowGSTin(showGSTInInput);
  }, [isUnregistered, formState.has_gstin, businessDetails.has_gstin]);

  useEffect(() => {
    setIsUnregistered(isUnregisteredBusiness(currentBusinessType));
  }, [currentBusinessType]);

  const businessNameValidator = (value) => {
    const contactName = formState.contact_name || businessDetails.contact_name;
    return validateCompanyAB(value, contactName, true);
  };

  const personalPANValidator = (value) => {
    return validatePersonalPAN(value, isUnregistered);
  };

  const reBankAccountValidator = (value) => {
    if (!value) {
      return '';
    }
    const bankAccountNo = formState.bank_account_number || businessDetails.bank_account_number;

    if (bankAccountNo && value !== bankAccountNo) {
      // Something changed in main 'bank account' field
      return 'Account no. does not match';
    }
    return '';
  };

  const gstinValidator = (value) => {
    if (!isValidGSTIN(value)) {
      return 'Please provide valid GSTIN';
    }
    return '';
  };

  const setRefReBankAccount = (el) => {
    reBankAccountEl.current = el;
  };

  const handleBankAccountBlur = () => {
    const bankAccountNumber = formState.bank_account_number;
    const accountNo = formState.account_no;

    const isMatching = bankAccountNumber == accountNo;

    if (!!bankAccountNumber && (!accountNo || !isMatching)) {
      reBankAccountEl.current?.focus(); // Focus on dependent field on Blur. Will be ignored if that is disabled.
    }
  };

  const getGSTinDescription = () => {
    if (formState.has_gstin == '1') {
      if (currentBusinessType == PROPRIETORSHIP) {
        return (
          <span className="text-danger">
            Please note that skipping GSTIN might lead to delay in your account review by upto two
            weeks, usually it takes 3-4 days
          </span>
        );
      }
      return 'You can add your GST details later once you are registered';
    }
    return '';
  };

  const getBenificiaryNameInfo = () => {
    const text = isUnregistered ? 'Individual' : 'Company';
    return `The beneficiary name should be same as ${text} name`;
  };

  return (
    <form onChange={onFormChange} className="Form Form--tabular">
      <Input.Select
        name="business_type"
        label="Business Type"
        defaultValue={businessDetails.business_type}
        disabled={isFormLocked || commonLockedFields.includes('business_type')}
        size="small"
        options={BusinessTypeOptions}
        required
        info={getBusinessTypeInfo(currentBusinessType)}
        onChange={(e) => setCurrentBusinessType(e.target.value)}
      />
      {isUnregistered ? (
        ''
      ) : (
        <Input
          name="business_name"
          label="Business Name"
          defaultValue={businessDetails.business_name}
          disabled={isFormLocked || commonLockedFields.includes('business_name')}
          size="small"
          required
          info={getBusinessNameInfo(currentBusinessType)}
          validator={businessNameValidator}
          placeholder="Business name as per PAN"
        />
      )}
      {displayCompanyPAN(currentBusinessType) ? (
        <Input
          name="company_pan"
          label="Business PAN"
          defaultValue={businessDetails.company_pan}
          disabled={isFormLocked || commonLockedFields.includes('company_pan')}
          size="small"
          required
          info="Mandatory for Companies. PAN details should be of the mentioned business only."
          placeholder="PAN of the company"
          validator={validateCompanyPAN}
          className="Input--capitalize"
        />
      ) : (
        <>
          <Input
            name="promoter_pan"
            label="PAN"
            defaultValue={businessDetails.promoter_pan}
            disabled={isFormLocked || commonLockedFields.includes('promoter_pan')}
            size="small"
            required
            info="Mandatory for Companies. PAN details should be of the mentioned business only."
            placeholder="Business owner’s PAN"
            validator={personalPANValidator}
          />
          <Input
            name="promoter_pan_name"
            label="PAN Owner’s Name"
            defaultValue={businessDetails.promoter_pan_name}
            disabled={isFormLocked || commonLockedFields.includes('promoter_pan_name')}
            size="small"
            required
            info="We verify the details with the central PAN database. Please ensure you enter the correct PAN details"
            placeholder="Name as per PAN"
            validator={name()}
          />
        </>
      )}
      <Input
        name="bank_account_name"
        label="Beneficiary Name"
        maxLength={120}
        minLength={4}
        defaultValue={businessDetails.bank_account_name}
        disabled={isFormLocked || commonLockedFields.includes('bank_account_name')}
        size="small"
        required
        info={getBenificiaryNameInfo()}
      />
      <Input
        name="bank_account_number"
        label="Account Number"
        defaultValue={businessDetails.bank_account_number}
        disabled={isFormLocked || commonLockedFields.includes('bank_account_number')}
        size="small"
        required
        info={getAccountNumberInfo(currentBusinessType)}
        placeholder="Bank account number"
        autoComplete="new-password"
        onBlur={handleBankAccountBlur}
      />
      <Input
        name="account_no"
        label="Re-Enter Account Number"
        defaultValue={businessDetails.bank_account_number}
        disabled={isFormLocked || commonLockedFields.includes('bank_account_number')}
        size="small"
        required
        info="Please re-enter the bank account number."
        placeholder="Re-enter the bank account number."
        validator={reBankAccountValidator}
        autoComplete="new-password"
        setRef={setRefReBankAccount}
      />
      <Input
        name="bank_branch_ifsc"
        label="Branch IFSC Code"
        defaultValue={businessDetails.bank_branch_ifsc}
        disabled={isFormLocked || commonLockedFields.includes('bank_branch_ifsc')}
        size="small"
        required
        info={(e) => {
          if (!e) {
            return null;
          }
          return getDetailsForIFSC(e.target.value);
        }}
        validator={validateIFSC}
      />
      {!isUnregistered && (
        <Input.Radio
          name="has_gstin"
          options={['We have a registered GSTIN', "We don't have a GSTIN"]}
          label="GSTIN"
          className="Input--vTop Input--capitalize"
          defaultValue={businessDetails.has_gstin}
          disabled={isFormLocked || commonLockedFields.includes('gstin')}
          size="small"
          required
          description={getGSTinDescription}
        />
      )}
      {shouldShowGSTin && (
        <Input
          name="gstin"
          label="GST Identification Number (GSTIN)"
          placeholder="Enter GSTIN"
          defaultValue={businessDetails.gstin}
          disabled={isFormLocked || commonLockedFields.includes('gstin')}
          size="small"
          required
          info="Enter GSTIN & get reviewed faster. Should match your business address."
          validator={gstinValidator}
        />
      )}
    </form>
  );
};

export default BusinessDetails;
