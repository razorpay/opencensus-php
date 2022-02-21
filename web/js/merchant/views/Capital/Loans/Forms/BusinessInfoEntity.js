import React, { Component } from 'react';
import { Field, Form, isDirty, reduxForm } from 'redux-form';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { APPLICATION_STATES, BUSINESS_TYPES, HOTJAR_TRIGGERS } from '../constants';
import {
  registerBusiness,
  saveApplicationDetails,
  saveBusinessDetails,
  saveRequestedLoanAttributes,
} from 'merchant/reducers/capital';
import { AsyncBtn } from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { isPreceedingState } from '../../utils';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';

//TODO:Refactor this when removed redux form dependency.
const TextInputWrapper = ({ input, ...rest }) => <Input {...input} {...rest} />;
const SelectInputWrapper = ({ input, ...rest }) => {
  return <Input.Select {...input} {...rest} />;
};
const TextAreaWrapper = ({ input, ...rest }) => {
  return <Input.Textarea {...input} {...rest} />;
};
const OutlineLockIcon = <i class="i i-outline-lock" />;

@connect(
  (state) => {
    if (state.loanApplicationDetails.meta.data.application.id === 'new') {
      const {
        session: { user },
      } = state;
      const { loan_attributes: loanAttributes, business_details } = state.loanApplicationDetails;
      return {
        session: state.session,
        loanApplicationDetails: state.loanApplicationDetails,
        hasLoanAttributesChanged: isDirty('loanee-business-details')(state, [
          'amount',
          'expected_tenure',
          'monthly_volume',
          'credit_request_purpose',
        ]),
        initialValues: {
          ...(business_details.data.business
            ? {
                legal_name: business_details.data.business.legal_name,
                business_email: business_details.data.business.emails[0].email_id,
                business_pan: business_details.data.business.business_pan,
                business_type: BUSINESS_TYPES[parseInt(user.business_type, 10)],
              }
            : {
                business_type: BUSINESS_TYPES[parseInt(user.business_type, 10)],
                legal_name: user.business_name,
                business_email: user.email,
                business_pan: user.company_pan,
              }),
          ...(loanAttributes
            ? {
                amount: loanAttributes.amount,
                expected_tenure: loanAttributes.expected_tenure,
                credit_request_purpose: loanAttributes.credit_request_purpose,
                monthly_volume: loanAttributes.monthly_volume,
              }
            : {
                amount: 'R50K_TO_100K',
                expected_tenure: 'LESS_THAN_A_MONTH',
                monthly_volume: 'R1LAKH_TO_5LAKHS',
                credit_request_purpose: '',
              }),
        },
      };
    }
    const {
      business_details,
      meta: {
        data: { application },
      },
    } = state.loanApplicationDetails;
    if (business_details.data.business) {
      return {
        session: state.session,
        loanApplicationDetails: state.loanApplicationDetails,
        initialValues: {
          business_type: business_details.data.business.deed_type,
          legal_name: business_details.data.business.legal_name,
          business_email: business_details.data.business.emails[0].email_id,
          business_pan: business_details.data.business.business_pan,
          monthly_volume: application.requested_product_attributes.monthly_volume,
          amount: application.requested_product_attributes.amount,
          credit_request_purpose: application.requested_product_attributes.credit_request_purpose,
          expected_tenure: application.requested_product_attributes.tenure,
        },
        hasLoanAttributesChanged: isDirty('loanee-business-details')(state, [
          'amount',
          'expected_tenure',
          'monthly_volume',
          'credit_request_purpose',
        ]),
      };
    } else {
      return {
        session: state.session,
        loanApplicationDetails: state.loanApplicationDetails,
      };
    }
  },
  {
    saveBusinessDetails,
    saveRequestedLoanAttributes,
    registerBusiness,
    saveApplicationDetails,
    ...NotificationsActions,
  },
)
@reduxForm({
  form: 'loanee-business-details',
  enableReinitialize: true,
})
class BusinessInfoEntity extends Component {
  constructor() {
    super();
    this.seedOptions = {
      credit_amount: [],
      monthly_volume: [],
      expected_tenure: [],
    };
  }
  componentDidMount() {
    const { loanApplicationDetails } = this.props;
    const seedDataEnumMap = {
      monthly_volume: 'monthly_volume_labels',
      credit_amount: 'amount_labels',
      expected_tenure: 'tenure_labels',
    };
    if (loanApplicationDetails.seed_data.data) {
      this.seedOptions = Object.entries(seedDataEnumMap).reduce(
        (acc, [optionsKey, seedDataKey]) => {
          return {
            ...acc,
            [optionsKey]: Object.entries(loanApplicationDetails.seed_data.data[seedDataKey]).map(
              ([volume, volume_label]) => ({
                label: volume_label,
                name: volume,
              }),
            ),
          };
        },
        {},
      );
    }
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_BUSINESS_INFO);
  }

  canModify = () =>
    isPreceedingState(
      this.props.loanApplicationDetails.meta.data.application.status,
      APPLICATION_STATES.CONTRACT_PENDING,
    );

  handleSubmit = (formData) => {
    const {
      legal_name,
      business_email,
      business_pan,
      monthly_volume,
      amount,
      expected_tenure,
      credit_request_purpose,
    } = formData;
    const {
      loanApplicationDetails,
      session: { user },
    } = this.props;

    if (!this.canModify()) {
      this.props._trackNavigationActions('NEXT', 'PROMOTER_INFO_PENDING');
      this.props.navigation.next();
      return;
    }
    const businessExists = Boolean(
      loanApplicationDetails?.business_details?.data?.business &&
        loanApplicationDetails?.business_details?.data?.business.id,
    );
    const businessDetails = loanApplicationDetails?.business_details?.data?.business;
    const payload = {
      business: {
        ...(businessExists
          ? {
              id: businessDetails.id,
            }
          : {}),
        reference_id: businessExists ? businessDetails.id : user.current,
        reference_type: 'MID',
        legal_name,
        deed_type: BUSINESS_TYPES[parseInt(user.business_type, 10)],
        business_pan,
        addresses: [
          {
            ...(businessExists
              ? businessDetails.addresses[0]
              : {
                  address_type: 'ADDRESS_TYPE_BUSINESS',
                  address_line1: user.business_registered_address,
                  address_line2: user.business_registered_address_l2,
                  city: user.business_registered_city,
                  state: user.business_registered_state,
                  pincode: user.business_registered_pin,
                  country: user.business_registered_country || 'IN',
                  is_primary: true,
                }),
          },
        ],
        phones: [
          {
            ...(businessExists
              ? {
                  id: businessDetails.phones[0].id,
                }
              : {}),
            country_code: '+91',
            phone_number: user.contact_mobile,
            is_primary: true,
            is_verified: user.user.contact_mobile_verified,
          },
        ],
        emails: [
          {
            ...(businessExists
              ? {
                  id: businessDetails.emails[0].id,
                }
              : {}),
            email_id: business_email,
            is_primary: true,
            verified: true,
          },
        ],
      },
    };

    const loanAttributes = {
      amount,
      currency: 'INR',
      interest_rate: 10,
      expected_tenure,
      monthly_volume,
      credit_request_purpose,
    };
    if (
      this.props?.loanApplicationDetails?.meta?.data?.application?.id &&
      this.props?.loanApplicationDetails?.meta?.data?.application?.id !== 'new'
    ) {
      const applicationPayload = {
        id: this.props.loanApplicationDetails.meta.data.application.id,
        owner_id: payload.business.id,
        owner_type: 'BUSINESS',
        product_id: this.props.loanApplicationDetails.products.data[0].id,
        requested_product_attributes: {
          amount: loanAttributes.amount,
          currency: 'INR',
          interest_rate: 10,
          tenure: loanAttributes.expected_tenure,
          credit_request_purpose: loanAttributes.credit_request_purpose,
          monthly_volume: loanAttributes.monthly_volume,
        },
        tnc_consent_attributes: {
          consent_given: true,
          given_at: Date.now(),
        },
      };
      this.props
        .saveApplicationDetails(applicationPayload)
        .then((_) => {
          this.props._trackNavigationActions('NEXT', 'PROMOTER_INFO_PENDING');
          this.props.navigation.next();
        })
        .catch((e) => {
          this.props.showNotification({
            type: 'error',
            message: e.errors ? e.errors[0] : 'Something went wrong.',
          });
        });
    } else {
      this.props.registerBusiness(payload);
      this.props._trackNavigationActions('NEXT', 'PROMOTER_INFO_PENDING');
      this.props.navigation.next();
      this.props.saveRequestedLoanAttributes(loanAttributes);
    }
  };

  render() {
    if (this.props.loanApplicationDetails.business_details.loading) return <FormLoader />;

    const canModify = this.canModify();
    return (
      <Form
        layout="tabular"
        class="Form Form--tabular loan-application-form"
        onSubmit={this.props.handleSubmit(this.handleSubmit)}
      >
        <Field
          key="business_name"
          component={TextInputWrapper}
          addonAfter={OutlineLockIcon}
          disabled={true}
          label="Business Name"
          name="legal_name"
          placeholder=""
          autoFocus={true}
          size="medium"
          required
        />
        <Field
          key="business_email"
          component={TextInputWrapper}
          addonAfter={OutlineLockIcon}
          disabled={true}
          label="Business Email"
          name="business_email"
          placeholder=""
          size="medium"
          required
        />
        {this.props.initialValues.business_type !== BUSINESS_TYPES[1] && (
          <Field
            key="business_pan"
            component={TextInputWrapper}
            addonAfter={OutlineLockIcon}
            disabled={true}
            label="Business PAN"
            name="business_pan"
            placeholder=""
            size="medium"
            required
          />
        )}
        <Input.Group label="Monthly Business Value" className="InputGroup--inline" required>
          <div className="Input-content">
            <Input.CurrencySelect name="inrs" defaultValue="INR" disabled />
            <Field
              key="monthly_volume"
              component={SelectInputWrapper}
              name="monthly_volume"
              placeholder=""
              size="medium"
              options={this.seedOptions.monthly_volume}
              required
              disabled={!canModify}
            />
          </div>
        </Input.Group>
        <Input.Group label="Expected Loan Amount" className="InputGroup--inline" required>
          <div className="Input-content">
            <Input.CurrencySelect name="curr" defaultValue="INR" disabled />
            <Field
              key="amount"
              component={SelectInputWrapper}
              name="amount"
              placeholder=""
              size="medium"
              options={this.seedOptions.credit_amount}
              disabled={!canModify}
              required
            />
          </div>
        </Input.Group>
        <Field
          key="expected_tenure"
          label="Tenure"
          component={SelectInputWrapper}
          disabled={!canModify}
          name="expected_tenure"
          placeholder=""
          size="medium"
          options={this.seedOptions.expected_tenure}
          required
        />
        <Field
          size="large"
          key="credit_request_purpose"
          label="Purpose"
          component={TextAreaWrapper}
          disabled={!canModify}
          name="credit_request_purpose"
          placeholder="Use this space to describe the purpose of your loan to the best of your ability"
        />

        <div class="loan-application-form-footer">
          <AsyncBtn.Primary
            type="submit"
            class="btn btn-primary pull-right no-margin"
            onClick={this.props.handleSubmit(this.handleSubmit)}
          >
            {canModify ? 'Save & Next' : 'Next'}
            <i className="i i-chevron-right" />
          </AsyncBtn.Primary>
        </div>
      </Form>
    );
  }
}

export default BusinessInfoEntity;
