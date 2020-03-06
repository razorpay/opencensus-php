import Input from 'common/new-ui/Input';

export const BankDetails = ({
  hideBankName,
  disabled,
  options,
  children,
  bankName,
  bankAccountIFSC,
  required,
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
          description="Preferred bank for authentication"
        />
      )}

      <Input
        name="bankAccountIFSC"
        placeholder="IFSC"
        value={bankAccountIFSC}
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
      value={beneficiaryName}
      description="Customer/Beneficiary Name on the Account"
    />

    <Input
      placeholder="Account Number"
      name="bankAccountNumber"
      value={bankAccountNumber}
      description="Bank Account Number"
    />

    <Input.Select
      name="accountType"
      options={['--Select Account Type--', ...OPTIONS]}
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
