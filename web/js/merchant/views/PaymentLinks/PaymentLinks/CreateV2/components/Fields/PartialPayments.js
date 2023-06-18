import { connect } from 'react-redux';
import Popover from 'common/ui/Popover';
import Input from 'common/new-ui/Input';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

import {
  PopoverBodyText,
  MIN_AMOUNT_TEXT,
  validateMinAmount,
} from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/EditMinimumAmount';

const PartialPayments = (props) => (
  <>
    <Input.Check
      autoRender
      name="accept_partial"
      fieldLabel="Enable Partial Payment"
      label="Partial Payment"
      class="Input--vTop"
      disabled={props.disabled}
      defaultValue={props.defaultValue}
      labelClass="Input-label pb-8"
      onBlur={() => {
        track.lj.fields.partialPayment();
        track.segment.fields.partialPayment();
      }}
    />

    {props.showFirstPaymentMinAmount && props.defaultValue === '1' && (
      <div class="Input--custom">
        <Input.Group
          class="InputGroup--inline"
          label={
            <>
              {MIN_AMOUNT_TEXT} (Optional)
              <small className="help-content">
                <i class="i i-info-outline m-l" />
                <Popover align="top" parentQuerySelector=".Modal-body">
                  {PopoverBodyText}
                </Popover>
              </small>
            </>
          }
        >
          <div class="Input-content">
            <Input.CurrencySelect
              disabled
              currency={props.currency}
              defaultValue="INR"
              parentQuerySelector=".Modal-body"
            />

            <Input
              autoRender
              name="first_min_partial_amount"
              placeholder="0.00"
              size="half_big"
              defaultValue={props.defaultFirstMinAmount}
              onBlur={track.lj.fields.firstPaymentMinAmount}
              validator={(val) => {
                return validateMinAmount(val, props.amount, props.currency);
              }}
              disabled={props.disabled}
            />
          </div>
        </Input.Group>
      </div>
    )}
  </>
);

const mapStateTopProps = (state) => ({
  showFirstPaymentMinAmount: state.session.user.isMinimumFirstPaymentEnabled,
});
export default connect(mapStateTopProps)(PartialPayments);
