import Popover, { PopoverBody } from 'common/ui/Popover';
import Input from 'common/new-ui/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default ({
  formReference1,
  formReference2,
  accountType,
  isNachFormAval,
  bankAccountIFSC,
  beneficiaryName,
  bankAccountNumber,
  trackReceivedNACHForm,
  trackNACHToolTipHover,
}) => (
  <React.Fragment>
    <Input.Check
      name="isNachFormAval"
      label="NACH Form"
      class="InputGroup--vTop"
      checked={isNachFormAval}
      onChange={trackReceivedNACHForm}
      fieldLabel={
        <React.Fragment>
          I have Customer's signed Form{' '}
          <React.Fragment>
            <i class="i i-info-circle" />
            <Popover
              theme="dark"
              align="bottom"
              parentQuerySelector=".ModalSingleForm"
            >
              <PopoverBody>
                <div onMouseOver={trackNACHToolTipHover}>
                  If you’ve already received the customer’s NACH form, you can
                  upload it after the registration link is created.
                </div>
              </PopoverBody>
            </Popover>
          </React.Fragment>
        </React.Fragment>
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
        options={['--Select Account Type--', ...OPTIONS]}
        placeholder="Account Type"
        value={accountType}
      />
    </AccountDetails>

    <Input name="formReference1" value={formReference1} label="Reference 1" />

    <Input name="formReference2" value={formReference2} label="Reference 2" />
  </React.Fragment>
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
