import React, { useState } from 'react';
import PropTypes from 'prop-types';
import CurrencyInput from 'common/new-ui/Input';
import Amount from 'common/ui/Amount';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import { isAmount } from 'common/utils/validators';

const CurrencyField = (props) => {
  const { dispute, handleInput, disabled } = props;
  const [showEditDisputeAmount, setShowEditDisputeAmount] = useState(false);
  const isDipsuteOpen = dispute.status === 'open';

  if (showEditDisputeAmount)
    return (
      <CurrencyInput
        name="amount"
        required
        disabled={disabled}
        defaultValue={getFormattedAmount(dispute?.evidence?.amount || dispute.amount)}
        description={
          <>
            Enter amount less than&nbsp;
            <Amount value={dispute.amount} currency={dispute.currency} />
            &nbsp;for contesting this dispute partially
          </>
        }
        onBlur={handleInput}
        addonBefore={<span>{window.currencyList[dispute.currency].symbol}</span>}
        validator={(val) => {
          if (!isAmount(val)) {
            const decimal = val && val.split('.');

            if (decimal.length == 2 && decimal[1].length > 2) {
              return 'Enter upto 2 decimals';
            } else {
              return 'Invalid Amount';
            }
          }

          if (val > getFormattedAmount(dispute.amount)) {
            return `Amount can't be more than the total dispute amount`;
          }
          return '';
        }}
      />
    );
  else
    return (
      <>
        <Amount value={dispute?.evidence?.amount || dispute.amount} currency={dispute.currency} />
        <input type="hidden" value={dispute.amount} name="amount" />
        {isDipsuteOpen && !disabled && (
          <div>
            <a class="bold" onClick={() => setShowEditDisputeAmount(true)}>
              Edit
            </a>{' '}
            to contest for a partial amount
          </div>
        )}
      </>
    );
};

CurrencyField.propTypes = {
  dispute: PropTypes.object.isRequired,
  handleInput: PropTypes.func.isRequired,
};

export default CurrencyField;
