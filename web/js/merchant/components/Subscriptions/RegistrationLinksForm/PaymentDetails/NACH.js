import Popover, { PopoverBody } from 'rzp/ui/Popover';
import Input from 'component/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default ({
  accountType,
  isNachFormAval,
  bankAccountIFSC,
  beneficiaryName,
  bankAccountNumber,
}) => (
  <React.Fragment>
    <Input.Check
      name="isNachFormAval"
      label="NACH Form"
      class="InputGroup--vTop"
      checked={isNachFormAval}
      fieldLabel={
        <div>
          I have Customer's signed Form{' '}
          <span>
            <i class="i i-info-circle" />
            <Popover theme="dark">
              <PopoverBody>
                If you’ve already received the customer’s NACH form, you can
                upload it after the registration link is created.
              </PopoverBody>
            </Popover>
          </span>
        </div>
      }
    />

    <BankDetails required hideBankName bankAccountIFSC={bankAccountIFSC} />

    <AccountDetails
      required
      beneficiaryName={beneficiaryName}
      bankAccountNumber={bankAccountNumber}
    >
      <Input.Select
        name="accountType"
        options={['--Select Bank--', ...OPTIONS]}
        placeholder="Account Type"
        value={accountType}
      />
    </AccountDetails>
  </React.Fragment>
);

const OPTIONS = [
  {
    label: 'Savings Bank',
    name: 'savings',
  },
  {
    label: 'Current Bank',
    name: 'current',
  },
];
