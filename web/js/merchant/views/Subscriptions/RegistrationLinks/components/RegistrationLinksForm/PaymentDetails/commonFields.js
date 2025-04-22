import Input from 'common/new-ui/Input';

import { validateBeneficiaryName } from 'common/utils/validators';

const OPTIONS = [
  {
    label: 'Savings',
    name: 'savings',
  },
  {
    label: 'Current',
    name: 'current',
  },
];

const NACH_OPTIONS = [
  {
    label: 'SB-NRE',
    name: 'nre',
  },
  {
    label: 'SB-NRO',
    name: 'nro',
  },
  {
    label: 'Cash Credit',
    name: 'cc',
  },
];

const getAccountTypes = () => {
  return [...OPTIONS, ...NACH_OPTIONS];
};

export const BankDetails = ({
  hideBankName,
  disabled,
  options,
  children,
  bankName,
  bankAccountIFSC,
  required,
  onBlurElement,
}) => (
  <Input.Group
    label="Bank Details"
    className="InputGroup--inline"
    disabled={disabled}
    required={required}
  >
    <div className="Input-content">
      {!hideBankName && (
        <Input.Select
          name="bankName"
          options={['Select Bank', ...options]}
          placeholder="Bank Name"
          value={bankName}
          onBlur={onBlurElement}
          data-name="bank"
          description="Preferred bank for authentication"
        />
      )}

      <Input
        name="bankAccountIFSC"
        placeholder="IFSC"
        data-name="ifsc"
        value={bankAccountIFSC}
        onBlur={onBlurElement}
        description="IFSC on the Bank Account"
      />

      {children}
    </div>
  </Input.Group>
);

export const AccountDetails = ({
  required,
  disabled,
  accountType,
  beneficiaryName,
  bankAccountNumber,
  onBlurElement,
}) => (
  <Input.Group
    label="Account Details"
    className="InputGroup--vTop"
    disabled={disabled}
    required={required}
  >
    <Input
      placeholder="Beneficiary Name"
      name="beneficiaryName"
      data-name="beneficiary_name"
      value={beneficiaryName}
      onBlur={onBlurElement}
      description="Customer/Beneficiary Name on the Account"
      validator={(value) =>
        !validateBeneficiaryName(value) ? 'Please enter a valid name as per your account' : null
      }
    />

    <Input
      placeholder="Account Number"
      name="bankAccountNumber"
      data-name="account_number"
      value={bankAccountNumber}
      description="Bank Account Number"
      onBlur={onBlurElement}
    />

    <Input.Select
      name="accountType"
      data-name="account_type"
      options={['--Select Account Type--', ...getAccountTypes()]}
      onBlur={onBlurElement}
      placeholder="Account Type"
      value={accountType}
    />
  </Input.Group>
);
