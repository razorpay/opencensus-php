import Input from 'common/new-ui/Input';

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
    class="InputGroup--inline"
    disabled={disabled}
    required={required}
  >
    <div class="Input-content">
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
    class="InputGroup--vTop"
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
      options={['--Select Account Type--', ...OPTIONS]}
      onBlur={onBlurElement}
      placeholder="Account Type"
      value={accountType}
    />
  </Input.Group>
);

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
