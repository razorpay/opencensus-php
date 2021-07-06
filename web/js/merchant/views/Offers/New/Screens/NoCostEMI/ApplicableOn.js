import React from 'react';
import Input from 'common/new-ui/Input';
import Amount from 'common/ui/Amount';

import { PAYMENT_NETWORK_MAP, ISSUERS } from 'merchant/views/Offers/constants';
import { rupeesToPaise } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink'

const NetworksAndIssuers = { ...PAYMENT_NETWORK_MAP, ...ISSUERS };

// TODO: Fix EMI Tenure validations
export default class ApplicableOn extends React.Component {
  constructor(props) {
    super();

    this.ISSUERS_OPTIONS = [
      {
        name: '',
        label: '--Select Issuer--',
      },
    ];

    Object.entries(props.emiData.emi_plans).forEach(([issuer, issuerData]) => {
      if (issuerData.min_amount <= rupeesToPaise(props.minAmount)) {
        this.ISSUERS_OPTIONS.push({
          name: issuer,
          label: NetworksAndIssuers[issuer] || issuer,
        });
      }
    });
  }

  handleEmiDuration = (duration) => (event) => {
    event.stopPropagation();

    let emi_durations = [
      ...(this.props.formData.emi_durations ? this.props.formData.emi_durations : []),
    ];

    if (event.target.value === '0') {
      emi_durations = emi_durations.filter((ele) => ele != duration);
    }

    if (event.target.value === '1') {
      if (!emi_durations.includes(duration)) {
        emi_durations.push(duration);
      }
    }

    this.props.onChange({
      target: {
        name: 'emi_durations',
        value: emi_durations,
      },
    });
  };

  render() {
    const { formData, minAmount } = this.props;
    const SelectedEMIOptions = this.props.emiData.emi_options[formData.issuer]?.sort(
      (a, b) => a.duration - b.duration,
    );
    return (
      <React.Fragment>
        <Input.Select
          required
          label="Issuer"
          name="issuer"
          placeholder="Select network"
          options={this.ISSUERS_OPTIONS}
          defaultValue={formData.issuer}
        />

        {formData.issuer && (
          <Input.Group label="EMI Tenure" required>
            <div class="emi-options">
              <div class="emi-option heading">
                <div class="emi-check-field">
                  <p>EMI tenure</p>
                </div>
                <p>Discount borne by merchant</p>
              </div>
              {SelectedEMIOptions.map((plan) => (
                <div class="emi-option" key={plan.duration}>
                  <div class="emi-check-field">
                    <Input.Check
                      fieldLabel={`${plan.duration} Months`}
                      onChange={this.handleEmiDuration(plan.duration)}
                      defaultValue={formData.emi_durations?.indexOf(plan.duration) > -1}
                    />
                  </div>

                  <p>{plan.merchant_payback} %</p>
                </div>
              ))}
            </div>
          </Input.Group>
        )}

        <div className="no-cost-emi-footnote">
          <ul>
            <li>
              Only banks with minimum EMI order amount of{' '}
              <Amount value={rupeesToPaise(minAmount)} /> are being displayed.
            </li>
            <li>
              In No-Cost-EMI, the interest charged by bank is given as a discount to the customer.
              To know more about how this works, click{' '}
              <DocLink
                target="_blank"
                rel="noopener noreferrer"
                href="https://razorpay.com/docs/offers/no-cost-emi/"
              >
                here
              </DocLink>
              .
            </li>
          </ul>
        </div>
      </React.Fragment>
    );
  }
}
