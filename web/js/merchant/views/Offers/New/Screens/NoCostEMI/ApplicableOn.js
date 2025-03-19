import React from 'react';
import {
  Text,
  Box,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

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

  handleEmiDuration = (duration, handleChange) => (event) => {
    event.stopPropagation();

    let emi_durations = [
      ...(this.props.values.emi_durations ? this.props.values.emi_durations : []),
    ];

    if (event.target.value === '0') {
      emi_durations = emi_durations.filter((ele) => ele != duration);
    }

    if (event.target.value === '1') {
      if (!emi_durations.includes(duration)) {
        emi_durations.push(duration);
      }
    }

    handleChange({
      target: {
        name: 'emi_durations',
        value: emi_durations,
      },
    });
  };

  render() {
    const {
      minAmount,
      offersData,
      values,
      handleChange,
      setFieldTouched,
      setFieldValue,
      errors,
      setErrors,
      touched,
    } = this.props;
    const SelectedEMIOptions = this.props.emiData.emi_options[values.issuer]?.sort(
      (a, b) => a.duration - b.duration,
    );

    const {
      abExperiments: { Low_cost_offer },
    } = this.props.splitz;

    const isLowCostExperimentEnabled = Low_cost_offer?.variables?.result === 'on';

    const onFormChange = (name, value) => {
      setFieldTouched(name);
      setFieldValue(name, value);
      setErrors(errors);
    };
    errors.issuer = validateDiscountType(values.issuer);
    return (
      <StyledOfferForm className={isLowCostExperimentEnabled ? 'low-cost-offer-container' : ''}>
        <Dropdown marginBottom="spacing.7">
          <SelectInput
            isRequired
            necessityIndicator="required"
            label="Issuer"
            placeholder="--Select Issuer--"
            name="issuer"
            labelPosition="left"
            value={values.issuer}
            onChange={({ name, values }) => {
              onFormChange(name, values[0]);
            }}
            validationState={touched.issuer && errors?.issuer ? 'error' : 'none'}
            errorText={errors?.issuer}
          />
          <DropdownOverlay>
            <ActionList>
              {Object.values(this.ISSUERS_OPTIONS).map((type) => (
                <ActionListItem
                  key={type.name}
                  title={type.label}
                  value={type.name}
                  testID={`option-${type.name}`}
                />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>

        {values.issuer && (
          <Box>
            {isLowCostExperimentEnabled ? (
              <NoCostOfferForm
                offersData={offersData}
                values={values}
                handleChange={handleChange}
                setFieldTouched={setFieldValue}
                setFieldValue={setFieldValue}
                errors={errors}
                touched={touched}
                onChange={this.props.onChange}
                onOffersChange={this.props.onOffersChange}
                tenure={SelectedEMIOptions}
              />
            ) : (
              <Input.Group label="EMI Tenure" required>
                <div className="emi-options">
                  <div className="emi-option heading">
                    <div className="emi-check-field">
                      <Text>EMI tenure</Text>
                    </div>
                    <Text>Discount borne by merchant</Text>
                  </div>
                  {SelectedEMIOptions.map((plan) => (
                    <div className="emi-option" key={plan.duration}>
                      <div className="emi-check-field">
                        <Input.Check
                          fieldLabel={`${plan.duration} Months`}
                          onChange={(value) =>
                            this.handleEmiDuration(plan.duration, handleChange)(value)
                          }
                          defaultValue={values.emi_durations?.indexOf(plan.duration) > -1}
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
                  href="https://razorpay.com/docs/payments/offers/no-cost-emi/"
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
function validateDiscountType(val) {
  if (!val) {
    return 'Please select an issuer type';
  }
  return false;
}
