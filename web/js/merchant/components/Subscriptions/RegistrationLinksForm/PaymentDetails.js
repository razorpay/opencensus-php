import Input from 'component/Input';

import { AmountTooltip } from 'rzp/ui/Amount';

export default props => {
  const {
    mandateMethod: method,
    avlblMethods,
    loading,
    skipBankDetails,
  } = props;

  return (
    <React.Fragment>
      <PaymentMethod loading={loading} avlblMethods={avlblMethods} />

      {method === 'emandate' && (
        <React.Fragment>
          <Input.Check
            data-name="skipBankDetails"
            fieldLabel="Skip Bank Details"
          />

          <Input.Group
            label="Bank Details"
            class="InputGroup--inline"
            disabled={skipBankDetails}
          >
            <div class="Input-content">
              <Input.Select
                name="mandateBankName"
                options={['Select Bank', ...this.state.emandateBanks]}
                size="half_big"
                placeholder="Bank Name"
                description="Preferred bank for authentication"
              />

              <Input
                name="mandateBankAccountIFSC"
                size="half_big"
                placeholder="IFSC"
                description="IFSC on the Bank Account"
              />
            </div>
          </Input.Group>

          <Input.Group
            label="Account Details"
            class="InputGroup--inline"
            disabled={skipBankDetails}
          >
            <div class="Input-content">
              <Input
                placeholder="Beneficiary Name"
                name="mandateBeneficiaryName"
                description="Customer/Beneficiary Name on the Account"
                size="half_big"
              />

              <Input
                placeholder="Account Number"
                name="mandateBankAccountNumber"
                description="Bank Account Number"
                size="half_big"
              />
            </div>
          </Input.Group>

          <Input.Group label="Token Expiry" class="InputGroup--vTop">
            <Input.Check
              fieldLabel="Until cancelled"
              data-name="tokenHasNoExpiry"
              defaultValue="1"
            />

            <Input.ToCalendar
              name="mandateExpireAt"
              placeholder="Expiry (DD-MM-YYYY)"
              disablePastDates
              placement="topLeft"
              size="half_big"
              addonAfter={<i class="i i-date-range" />}
              description="Expiry of Token"
              onChange={this.handleDateChange('mandateExpireAt')}
              disabled={!!Number(this.state.tokenHasNoExpiry)}
            />
          </Input.Group>

          <Input
            name="first_payment_amount"
            type="number"
            placeholder="0"
            size="half_big"
            label="Amount"
            class="Input--Amount"
            description="Amount of First Charge"
            validator={value => {
              return checkIfAmountForFirstCharge(
                Number(this.state.mandateMaxAmount) || 100000,
                value
              );
            }}
            addonBefore={
              <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
            }
          />

          <Input
            name="mandateMaxAmount"
            placeholder="100000"
            label="Token Max Amount"
            addonBefore={
              <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
            }
            size="half_big"
            validator={checkIfAmount}
            description="Max Amount for Mandate"
            class="Input--Amount"
          />
        </React.Fragment>
      )}

      {method === 'card' && (
        <Input.Group class="InputGroup--inline" label="Amount">
          <div class="Input-content">
            <Input.CurrencySelect name="currency" />

            <Input
              name="amount"
              type="tel"
              placeholder="0.00"
              description="Amount of Registration Link Payment"
              validator={checkIfAmount}
              required
            />
          </div>
        </Input.Group>
      )}

      <Input.PairList
        name="notes"
        label="Internal Notes"
        class="Input--vTop"
        onChange={this.handleNotesChange}
      />
    </React.Fragment>
  );
};

// utils required
function checkIfAmount(value) {
  return !isAmount(Number(value)) && 'Invalid Amount';
}

const checkIfAmountForFirstCharge = (maxAmount, value) => {
  const amount = Number(value);

  if (amount === 0) {
    return null;
  }

  if (amount > maxAmount) {
    return 'Amount is should be less than or equal Token Max Amount';
  }

  return !isAmount(value) && 'Invalid Amount';
};

function PaymentMethod({ loading, avlblMethods }) {
  if (loading)
    return (
      <PaymentMethodPlaceHolder
        content={<span class="text-muted">Fetching Methods...</span>}
      />
    );
  return avlblMethods.length > 1 ? (
    <Input.Radio
      required
      label="Payment Method"
      name="mandateMethod"
      options={avlblMethods}
      class="Input--vTop"
      description="Method to be used for Registration Link"
    />
  ) : (
    <PaymentMethodPlaceHolder content={avlblMethods[0].label} />
  );
}

function PaymentMethodPlaceHolder({ content }) {
  return (
    <div class="Input Input--vTop">
      <div class="Input-label">Payment Method</div>
      <div class="Input-content">{content}</div>
    </div>
  );
}
