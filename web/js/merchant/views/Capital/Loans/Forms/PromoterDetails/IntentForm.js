import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { PowerSelect, PowerSelectMultiple } from 'react-power-select';
import Spinner from 'common/ui/Spinner';
import { NOOP } from '../../constants';

import { INTENT_CREDIT_USE_OPTIONS, INTENT_CREDIT_AMOUNT_OPTIONS } from 'merchant/helpers/data';

const IntentForm = ({ loanApplicationDetails, handleIntentSubmit }) => {
  const [loading, setLoading] = React.useState(true);
  const [applicationData, setApplicationData] = React.useState(null);

  React.useEffect(() => {
    if (loanApplicationDetails.applications.data) {
      setApplicationData(loanApplicationDetails.applications.data.applications[0]);
      setLoading(false);
    } else {
      setLoading(true);
    }
  }, [loanApplicationDetails.applications.loading]);

  return (
    <div>
      {loading ? (
        <Spinner />
      ) : (
        <div className="intent-form">
          <Input.Group
            required
            className="InputGroup--inline InputGroup--vTop"
            label="How much credit do you know?"
          >
            <Input.CurrencySelect
              className="credit-need-currency"
              name="currency"
              defaultValue="INR"
              disabled
            />

            <PowerSelect
              className="credit-need-select"
              options={INTENT_CREDIT_AMOUNT_OPTIONS}
              selected={applicationData.intent_credit_amount || ''}
              onChange={NOOP}
              disabled
              searchEnabled={false}
              placeholder="Up to 10,00,000"
            />
          </Input.Group>

          <Input.Group
            required
            className="InputGroup--inline InputGroup--vTop"
            label="How do you plan to use the line of credit?"
          >
            <div className="Input-content credit-use">
              <div>
                <PowerSelectMultiple
                  className="credit-use-select"
                  options={INTENT_CREDIT_USE_OPTIONS}
                  selected={
                    applicationData.credit_request_purpose
                      ? applicationData.credit_request_purpose.split(',')
                      : []
                  }
                  onChange={NOOP}
                  disabled
                  placeholder="Select one or more options"
                />
              </div>
            </div>
          </Input.Group>

          <Button.Primary
            type="submit"
            className="btn btn-primary no-margin new-onboarding-button"
            onClick={handleIntentSubmit}
          >
            Continue <i className="i i-chevron-right" />
          </Button.Primary>
        </div>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  loanApplicationDetails: state.loanApplicationDetails,
});

export default connect(mapStateToProps)(IntentForm);
