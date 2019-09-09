import Input from 'component/Input';

const options = [
  { label: 'Axis Bank', authTypes: ['netbanking'], name: 'UTIB' },
  {
    label: 'Bank of Baroda - Retail Banking',
    authTypes: ['netbanking'],
    name: 'BARB_R',
  },
  { label: 'Bank of Maharashtra', authTypes: ['netbanking'], name: 'MAHB' },
  { label: 'Central Bank of India', authTypes: ['netbanking'], name: 'CBIN' },
  { label: 'City Union Bank', authTypes: ['netbanking'], name: 'CIUB' },
  { label: 'Deutsche Bank', authTypes: ['netbanking'], name: 'DEUT' },
  {
    label: 'Equitas Small Finance Bank',
    authTypes: ['netbanking'],
    name: 'ESFB',
  },
  { label: 'Federal Bank', authTypes: ['netbanking'], name: 'FDRL' },
  { label: 'HDFC Bank', authTypes: ['netbanking'], name: 'HDFC' },
  { label: 'ICICI Bank', authTypes: ['netbanking'], name: 'ICIC' },
  { label: 'IDBI', authTypes: ['netbanking'], name: 'IBKL' },
  { label: 'IDFC FIRST Bank', authTypes: ['netbanking'], name: 'IDFB' },
  { label: 'Indian Overseas Bank', authTypes: ['netbanking'], name: 'IOBA' },
  { label: 'Indusind Bank', authTypes: ['netbanking'], name: 'INDB' },
  { label: 'Kotak Mahindra Bank', authTypes: ['netbanking'], name: 'KKBK' },
  { label: 'Paytm Payments Bank', authTypes: ['netbanking'], name: 'PYTM' },
  {
    label: 'Punjab National Bank - Retail Banking',
    authTypes: ['netbanking'],
    name: 'PUNB_R',
  },
  { label: 'RBL Bank', authTypes: ['netbanking'], name: 'RATN' },
  { label: 'South Indian Bank', authTypes: ['netbanking'], name: 'SIBL' },
  { label: 'State Bank of India', authTypes: ['netbanking'], name: 'SBIN' },
  {
    label: 'Tamilnadu Mercantile Bank',
    authTypes: ['netbanking'],
    name: 'TMBL',
  },
  {
    label: 'Ujjivan Small Finance Bank',
    authTypes: ['netbanking'],
    name: 'USFB',
  },
  { label: 'Yes Bank', authTypes: ['netbanking'], name: 'YESB' },
];

export const BankDetails = ({
  disabled,
  // options,
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
      <Input.Select
        name="bankName"
        options={['Select Bank', ...options]}
        placeholder="Bank Name"
        value={bankName}
        description="Preferred bank for authentication"
      />

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
  children,
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

    {children}
  </Input.Group>
);
