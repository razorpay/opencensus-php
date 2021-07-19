import React from 'react';
import PropTypes from 'prop-types';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import { PowerSelect, PowerSelectMultiple } from 'react-power-select';

import { INTENT_CREDIT_USE_OPTIONS, INTENT_CREDIT_AMOUNT_OPTIONS } from 'merchant/helpers/data';
import {
  trackDesiredLoanAmount,
  trackGettingLosStarted,
  trackIntentFormContinueCta,
  trackLoanReason,
} from '../ga';

const IntentForm = ({ merchantId, updatedValues, handleIntentSubmit }) => {
  const [formData, setFormData] = React.useState({
    intent_credit_amount: '',
    credit_request_purpose: [],
  });

  React.useEffect(() => {
    if (updatedValues) setFormData(updatedValues);
    else trackGettingLosStarted(merchantId);
  }, []);

  const handleCreditNeedChange = ({ option }) => {
    trackDesiredLoanAmount(merchantId, option);
    setFormData({
      ...formData,
      intent_credit_amount: option,
    });
  };

  const handleCreditUseChange = ({ options }) => {
    trackLoanReason(merchantId, options.toString());
    setFormData({
      ...formData,
      credit_request_purpose: options,
    });
  };

  const isValidIntentForm = () => {
    return formData.intent_credit_amount && formData.credit_request_purpose?.length > 0;
  };

  const handleSubmit = () => {
    trackIntentFormContinueCta(merchantId);
    handleIntentSubmit(formData);
  };

  return (
    <div className="los-intent-details-form">
      <div className="flex">
        <Input.Group
          required
          className="InputGroup--inline InputGroup--vTop intent-field"
          label="How much credit do you need?"
        >
          <div className="Input-content credit-need">
            <Input.CurrencySelect
              className="credit-need-currency"
              name="currency"
              defaultValue="INR"
              disabled
            />

            <PowerSelect
              className="credit-need-select"
              options={INTENT_CREDIT_AMOUNT_OPTIONS}
              selected={formData.intent_credit_amount}
              onChange={handleCreditNeedChange}
              searchEnabled={false}
              placeholder="Up to 10,00,000"
            />
          </div>
        </Input.Group>
      </div>

      <Input.Group
        required
        className="InputGroup--inline InputGroup--vTop intent-field"
        label="How do you plan to use this credit?"
      >
        <div className="Input-content credit-use">
          <div>
            <PowerSelectMultiple
              className={`${
                formData.credit_request_purpose.length
                  ? `selected-${formData.credit_request_purpose.length}`
                  : ''
              } credit-use-select`}
              options={INTENT_CREDIT_USE_OPTIONS}
              selected={formData.credit_request_purpose || []}
              onChange={handleCreditUseChange}
              placeholder="Select one or more options"
            />
          </div>
        </div>
      </Input.Group>

      <AsyncBtn.Primary
        type="submit"
        className="btn btn-primary continue-cta"
        onClick={handleSubmit}
        disabled={!isValidIntentForm()}
      >
        Continue <i className="i i-chevron-right" />
      </AsyncBtn.Primary>
    </div>
  );
};

IntentForm.propTypes = {
  handleIntentSubmit: PropTypes.func.isRequired,
};

export default IntentForm;
