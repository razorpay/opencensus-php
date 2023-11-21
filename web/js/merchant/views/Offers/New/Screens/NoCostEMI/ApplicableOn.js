import React from 'react';
import { Text, Box } from '@razorpay/blade/components';

import Input from 'common/new-ui/Input';
import { withSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import { rupeesToPaise } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink';
import { NoCostOfferForm } from 'merchant/views/Offers/New/Screens/NoCostEMI/NoCostOfferForm';
import { FootNote, StyledOfferForm } from 'merchant/views/Offers/New/Screens/NoCostEMI/Styled';
import { getIssuerLabel } from 'merchant/views/Offers/utils';

// TODO: Fix EMI Tenure validations
class ApplicableOn extends React.Component {
  constructor(props) {
    super();

    this.ISSUERS_OPTIONS = [
      {
        name: '',
        label: '--Select Issuer--',
      },
    ];
    this.CO_BRANDING_PARTNERS = ['onecard'];

    Object.entries(props.emiData.emi_plans).forEach(([issuer, issuerData]) => {
      const isCobrandingPartner = this.CO_BRANDING_PARTNERS.includes(issuer);
      if (!isCobrandingPartner && issuerData.min_amount <= rupeesToPaise(props.minAmount)) {
        this.ISSUERS_OPTIONS.push({
          name: issuer,
          label: getIssuerLabel(issuer),
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
    const { formData, minAmount, offersData } = this.props;
    const SelectedEMIOptions = this.props.emiData.emi_options[formData.issuer]?.sort(
      (a, b) => a.duration - b.duration,
    );

    const {
      abExperiments: { Low_cost_offer },
    } = this.props.splitz;

    const isLowCostExperimentEnabled = Low_cost_offer?.variables?.result === 'on';
    return (
      <StyledOfferForm className={isLowCostExperimentEnabled ? 'low-cost-offer-container' : ''}>
        <Input.Select
          required
          label="Issuer"
          name="issuer"
          placeholder="Select network"
          options={this.ISSUERS_OPTIONS}
          defaultValue={formData.issuer}
          className="no-cost-offer-plans"
          onChange={() => {
            this.props.onOffersChange({});
          }}
        />

        {formData.issuer && (
          <Box>
            {isLowCostExperimentEnabled ? (
              <NoCostOfferForm
                formData={formData}
                offersData={offersData}
                onChange={this.props.onChange}
                onOffersChange={this.props.onOffersChange}
                tenure={SelectedEMIOptions}
              />
            ) : (
              <Input.Group label="EMI Tenure" required>
                <div class="emi-options">
                  <div class="emi-option heading">
                    <div class="emi-check-field">
                      <Text>EMI tenure</Text>
                    </div>
                    <Text>Discount borne by merchant</Text>
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

                      <Text>{plan.merchant_payback} %</Text>
                    </div>
                  ))}
                </div>
              </Input.Group>
            )}
          </Box>
        )}

        <FootNote
          className={`${
            isLowCostExperimentEnabled ? 'low-cost-footnote ' : ''
          }no-cost-emi-footnote`}
        >
          {isLowCostExperimentEnabled ? (
            <Text>
              In No Cost EMI the total interest charged is given as a discount and in Low Cost EMI
              partial interest is charged to the customer. To know more about how these work,
              click&nbsp;
              <DocLink
                target="_blank"
                rel="noopener noreferrer"
                href="https://razorpay.com/docs/payments/payment-gateway/affordability/low-cost-emi/"
              >
                here
              </DocLink>
              .
            </Text>
          ) : (
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
          )}
        </FootNote>
      </StyledOfferForm>
    );
  }
}

export default withSplitzService(ApplicableOn);
