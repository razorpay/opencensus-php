import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import axios from 'axios';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import RTracking from 'react-tracking';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getCookie } from '../../utils/cookies';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';
import { setItem } from 'common/utils/localStorage';
import { caReqEventType } from 'merchant/containers/Home/OnboardingCard/data';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';
import isEmpty from '@universe/utils/isEmpty';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { email as validateEmail, phone as validatePhone } from 'common/utils/validators';
import { RadioGroup } from 'common/ui/Forms/RadioGroup';
import InputField from 'common/ui/Forms/InputField';
import Textarea from 'common/ui/Forms/AutoResizeTextarea';
import KeystoneModal from 'common/ui/OffersForYou/components/KeystoneModal';
import NitroSelfServe from './Neostone/index';
import NitroCCCampaignModal from 'common/ui/OffersForYou/components/NitroCCCampaignModal';
import NitroICICIModal from '../../../merchant/components/Announcements/NitroICICIBanner/NitroICICIModal';

const BENEFITS = {
  other: [
    'Use the dashboard or APIs to make rule based payouts',
    'Add your entire team with specific access controls',
    'Get a consolidated view of your finances 24X7',
    'Track & automate every aspect of your finances',
    'Process thousands of payouts simultaneously',
    'Payouts via NEFT/IMPS/RTGS',
  ],
  corporateCards: [
    'Minimum Limit of Rs. 25,000 and upto 10 lacs limit* on your Corporate Card',
    'Add your entire team with specific access controls',
    'Get a consolidated view of your finances 24X7',
    'Use the dashboard or APIs to make rule based payouts',
    'Process thousands of payouts simultaneously',
    'Payouts via NEFT/IMPS/RTGS',
  ],
};

const NAME = 'full_name';
const PHONE = 'phone';
const EMAIL = 'email';
const CHALLENGES = 'what_are_the_biggest_challenges_you_face_with_your_current_account_today_';
const VENDORS = 'how_do_you_pay_your_vendors_customers_';
const MONTHLY_PAYMENTS__GIVEN = 'how_many_outward_payments_do_you_make_in_a_month_';
const RAZORPAYX_SWITCH = 'how_soon_can_you_switch_to_a_razorpayx_current_account_';
const MONTHLY_PAYMENTS_RECEIVED = 'how_many_payments_do_you_receive_every_month_';
const fields = [
  NAME,
  PHONE,
  EMAIL,
  CHALLENGES,
  VENDORS,
  MONTHLY_PAYMENTS__GIVEN,
  RAZORPAYX_SWITCH,
  MONTHLY_PAYMENTS_RECEIVED,
];

export const nitroCampaignId = () => {
  const map = {
    // Beta Nitro
    GwPth7nhHNdMND: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA',
    },
    H7361l13HhrBgO: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
      target_product_feature: 'XCA',
    },
    H75RfvQFecKHsT: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
      target_product_feature: 'XCA',
    },
    H75Qu5SInWp3SQ: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
      target_product_feature: 'XCA',
    },
    H75Q0JHjnUd5xs: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
      target_product_feature: 'XCA',
    },
    HF0Ml2IU6gH9rt: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
      target_product_feature: 'XCA',
    },
    HF0NZThSDtgNGB: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
      target_product_feature: 'XCA',
    },
    HF0OIJAqllZPRu: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
      target_product_feature: 'XCA',
    },
    HF0Ox4LNEgYHbV: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
      target_product_feature: 'XCA',
    },
    HZ76WCrNYDOyy9: {
      version: 'project-nitro-appswitcher',
      version_description: 'Nitro for appswitcher merchants',
      target_product_feature: 'XCA',
    },

    // Prod Nitro
    GxtSf8y77iWw9e: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA',
    },
    H6qGPCBPduY7Gl: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
      target_product_feature: 'XCA',
    },
    H6qJ2X77dqHG9I: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
      target_product_feature: 'XCA',
    },
    H6qIJWTzrqt54X: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
      target_product_feature: 'XCA',
    },
    HExafLb492K7LU: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
      target_product_feature: 'XCA',
    },
    H6qHJJnYOtwfoc: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
      target_product_feature: 'XCA',
    },
    HExehMbAqYqlWF: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
      target_product_feature: 'XCA',
    },
    HExiHP6GBUEVcu: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
      target_product_feature: 'XCA',
    },
    HExnzHcFfimA6u: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
      target_product_feature: 'XCA',
    },
    HPc6GXsuboNXiS: {
      version: 'project-nitro-delhi-v1',
      version_description: 'Nitro for delhi',
      target_product_feature: 'XCA',
    },
    HPc7OB0N3kh5BR: {
      version: 'project-nitro-mumbai-v1',
      version_description: 'Nitro for mumbai',
      target_product_feature: 'XCA',
    },
    HPc8DrLeWZc76W: {
      version: 'project-nitro-pune-v1',
      version_description: 'Nitro for pune',
      target_product_feature: 'XCA',
    },
    HPc9cMyPKKeAAX: {
      version: 'project-nitro-gurgaon-v1',
      version_description: 'Nitro for gurgaon',
      target_product_feature: 'XCA',
    },
    HPcAKrn53GP41d: {
      version: 'project-nitro-nagpur-v1',
      version_description: 'Nitro for nagpur',
      target_product_feature: 'XCA',
    },
    HPcBJQw2E0BzpZ: {
      version: 'project-nitro-kolhapur-v1',
      version_description: 'Nitro for kolhapur',
      target_product_feature: 'XCA',
    },
    Hrt8rX7v1tehY1: {
      version: 'project-nitro-coimbatore-v1',
      version_description: 'Nitro for coimbatore',
      target_product_feature: 'XCA',
    },
    HzaFJoqZsRDu0c: {
      version: 'nitro-othercities-v1',
      version_description: 'Nitro for others cities v1',
      target_product_feature: 'XCA',
    },
    HYlnMMJoE1RjFf: {
      version: 'project-nitro-appswitcher',
      version_description: 'Nitro for appswitcher merchants',
      target_product_feature: 'XCA',
    },

    // Beta nitro corporate cards
    HVcXIX8S1cokqB: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA+CCC',
    },

    // Prod nitro corporate cards
    HVdaH5ipHEzj6x: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
      target_product_feature: 'XCA+CCC',
    },
    HW1KsF0APg55vP: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
      target_product_feature: 'XCA+CCC',
    },
    // Test account for prod
    HWP22TCyDAfcRG: {
      version: 'test_nitro_kolkata_v1',
      version_description: 'Testing Nitro for kolkata',
      target_product_feature: 'XCA+CCC',
    },
    HW1HhztGJNiaYA: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
      target_product_feature: 'XCA+CCC',
    },
    HW1IZP67ejfclG: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
      target_product_feature: 'XCA+CCC',
    },
    HW14FoCLRKdABS: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
      target_product_feature: 'XCA+CCC',
    },
    HW1382Z5BUYrDV: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
      target_product_feature: 'XCA+CCC',
    },
    HW15QtkHIilowU: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
      target_product_feature: 'XCA+CCC',
    },
    HW16dcasfI78sW: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
      target_product_feature: 'XCA+CCC',
    },
    HW17UktB0YY7X7: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
      target_product_feature: 'XCA+CCC',
    },
    HW18PSOqi56mMN: {
      version: 'project-nitro-delhi-v1',
      version_description: 'Nitro for delhi',
      target_product_feature: 'XCA+CCC',
    },
    HW19AUgSRz2frR: {
      version: 'project-nitro-mumbai-v1',
      version_description: 'Nitro for mumbai',
      target_product_feature: 'XCA+CCC',
    },
    HW19wimb0Bhu8N: {
      version: 'project-nitro-pune-v1',
      version_description: 'Nitro for pune',
      target_product_feature: 'XCA+CCC',
    },
    HW1Al5SNojQepQ: {
      version: 'project-nitro-gurgaon-v1',
      version_description: 'Nitro for gurgaon',
      target_product_feature: 'XCA+CCC',
    },
    HW1Bb4TphEGzuU: {
      version: 'project-nitro-nagpur-v1',
      version_description: 'Nitro for nagpur',
      target_product_feature: 'XCA+CCC',
    },
    HW1CwITu2o0hdO: {
      version: 'project-nitro-kolhapur-v1',
      version_description: 'Nitro for kolhapur',
      target_product_feature: 'XCA+CCC',
    },
    HrtIYOAiX2ipgI: {
      version: 'project-nitro-coimabtore-v1',
      version_description: 'Nitro for coimabtore',
      target_product_feature: 'XCA+CCC',
    },
    HzaHdQAoyYFlFJ: {
      version: 'nitro-othercities-v1',
      version_description: 'Nitro for others cities v1',
      target_product_feature: 'XCA+CCC',
    },
  };

  const getExpStatus = (name, experimentNameInAbExperimentsMap) => {
    const splitzExperiment = window.rzp_user?.splitz_experiments[name];
    if (
      abExperimentsMap[experimentNameInAbExperimentsMap].includes(name) &&
      !isEmpty(splitzExperiment)
    ) {
      return splitzExperiment?.variables?.result === 'on';
    }
    return false;
  };

  const featureId = Object.keys(map).find(
    (feature) =>
      getExpStatus(feature, 'project_nitro') || getExpStatus(feature, 'nitro_corporate_cards'),
  );

  return {
    ...map[featureId],
    campaign: 'nitro',
    target_metric: 'MTU',
  };
};

const selector = formValueSelector('customerDetails');

@connect(
  (state) => ({
    user: state.session.user,
    ...fields.reduce(
      (acc, element) => ({
        ...acc,
        [element]: selector(state, element),
      }),
      {},
    ),
    initialValues: {
      [VENDORS]: '',
      [NAME]: (state.session.user.user || {}).name,
      [EMAIL]: (state.session.user.user || {}).email,
      [PHONE]: (state.session.user.user || {}).contact_mobile,
      country_code: '+91 - ',
    },
  }),
  {
    showNotification,
  },
)
@reduxForm({
  form: 'customerDetails',
})
class InfoForm extends React.Component {
  render() {
    const { handleSubmit, change } = this.props;
    const shouldSubmitBeDisabled = fields.some((field) => isEmpty(this.props[field]));

    return (
      <form autoComplete="off">
        <div className="left-section">
          <img className="rx-logo" src="/dist/css/assets/razorpay-x-logo-white.svg" alt="rx-logo" />
          <div className="row">
            <div class="form-group">
              <label className="control-label label-required">Name</label>
              <div>
                <Field
                  name={NAME}
                  placeholder="Full Name"
                  component={InputField}
                  class="form-control"
                  autoFocus
                  required
                />
              </div>
            </div>
          </div>
          <div className="row">
            <div class="form-group">
              <label className="control-label label-required">Email ID</label>
              <div>
                <Field
                  name={EMAIL}
                  placeholder="Work email"
                  component={InputField}
                  class="form-control"
                  validate={validateEmail('Please provide a valid email')}
                  onBlur={this.props.onBlur}
                  required
                />
              </div>
            </div>
          </div>
          <div className="row">
            <div class="form-group">
              <label className="control-label label-required">Phone number</label>
              <div className="phone-field">
                <Field name="country_code" component={InputField} class="form-control" readOnly />
                <Field
                  name={PHONE}
                  placeholder="Phone number"
                  component={InputField}
                  class="form-control"
                  onBlur={this.props.onBlur}
                  validate={validatePhone('Please provide a valid phone')}
                  required
                />
              </div>
            </div>
          </div>
          <div className="row">
            <div class="form-group">
              <label className="control-label label-required">
                What are the biggest challenges with your Current Account today?
              </label>
              <div>
                <Field
                  name={CHALLENGES}
                  placeholder="Tell us about your challenges here"
                  component={Textarea}
                  class="form-control"
                  onBlur={this.props.onBlur}
                  required
                />
              </div>
            </div>
          </div>
        </div>
        <div className="right-section">
          <div className="title">Help us understand your business</div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">
                How do you pay your vendors/customers?
              </label>
              <div className="checkbox-row">
                {['Cash', 'Cheque', 'NEFT', 'RTGS'].map((mode, index) => (
                  <label key={mode} htmlFor={`vendors[${index}]`}>
                    <Field
                      id={`vendors[${index}]`}
                      name={`vendors[${index}]`}
                      component="input"
                      required
                      type="checkbox"
                      onChange={(e) => {
                        let optionsSelected = !isEmpty(this.props[VENDORS])
                          ? this.props[VENDORS].split(';')
                          : [];

                        if (!e.target.value) {
                          optionsSelected.push(mode);
                        } else if (optionsSelected.includes(mode)) {
                          optionsSelected = optionsSelected.filter((option) => option !== mode);
                        }

                        change(VENDORS, optionsSelected.join(';'));
                      }}
                    />
                    {mode}
                  </label>
                ))}
              </div>
            </div>
          </div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">
                How many outward payments do you do every month?
              </label>
              <Field
                component={RadioGroup}
                name={MONTHLY_PAYMENTS__GIVEN}
                required
                options={[
                  { title: 'Less than 50', value: 'Less than 50' },
                  { title: '51 to 100', value: '51 to 100' },
                  { title: '101 to 250', value: '101 to 250' },
                  { title: '251 to 500', value: '251 to 500' },
                  { title: '500+', value: '500+' },
                ]}
              />
            </div>
          </div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">
                How many payments do you receive every month?
              </label>
              <Field
                component={RadioGroup}
                name={MONTHLY_PAYMENTS_RECEIVED}
                required
                options={[
                  { title: 'Less than 50', value: 'Less than 50' },
                  { title: '51 to 100', value: '51 to 100' },
                  { title: '101 to 250', value: '101 to 250' },
                  { title: '251 to 500', value: '251 to 500' },
                  { title: '500+', value: '500+' },
                ]}
              />
            </div>
          </div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">
                How soon can you switch to RazorpayX Current Account?
              </label>
              <Field
                component={RadioGroup}
                name={RAZORPAYX_SWITCH}
                required
                options={[
                  { title: 'Immediately', value: 'Immediately' },
                  { title: 'After 2 weeks', value: 'After 2 weeks' },
                  { title: 'After 4 weeks', value: 'After 4 weeks' },
                  { title: 'After 6 weeks', value: 'After 6 weeks' },
                ]}
              />
            </div>
          </div>
          <AsyncBtn.Primary
            type="submit"
            class="btn btn-primary submit-btn"
            disabled={shouldSubmitBeDisabled}
            onClick={handleSubmit(this.props.save)}
          >
            Request for a Current Account
          </AsyncBtn.Primary>
        </div>
      </form>
    );
  }
}

const SubmissionSuccessfull = ({ handleClose }) => {
  return (
    <div className="rxca-submit-finish-modal">
      <div className="header">
        <div className="title">
          Congratulations! We're processing your request for a Current Account with RazorpayX.
        </div>
        <button type="button" class="close" onClick={handleClose}>
          <i class="i i-close" />
        </button>
      </div>
      <div className="description">
        <p>
          Our banking experts will be reaching out to you shortly. In the meantime, we highly
          recommend you keep the required documents for creating a current account handy.
        </p>
        <a
          href="https://razorpay.com/docs/razorpayx/current-account/"
          target="_blank"
          rel="noreferrer"
        >
          <Button.Primary class="btn btn-primary" type="button">
            View Documents Required
          </Button.Primary>
        </a>
      </div>
      <p className="footer">
        Once your new current account gets created you're pricing for Razorpay will automatically be
        reduced to 1.65% as promised!
      </p>
    </div>
  );
};

@connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    showNotification,
  },
)
class DetailView extends React.Component {
  state = {
    showNitroFormFields: false,
  };
  showKeystoneModal =
    this.props.user.isProjectKeystoneCorporateCardsEnabled ||
    this.props.user.isProjectKeystoneCashAdvanceEnabled;

  getCampaignID = () => {
    const user = this.props.user;

    if (user.isProjectKeystoneCorporateCardsEnabled) return 'Nitro_Keystone_Card';
    if (user.isProjectKeystoneCashAdvanceEnabled) return 'Nitro_Keystone_CashAdvance';
    if (user.isProjectNitroCorporateCard) return 'Nitro_Capital';
    if (user.isNitroIciciBrandedCampaignEnabled) return 'Nitro_ICICIBranded';
    if (user.isNitroIciciRemarketingCampaignEnabled) return 'Nitro_ICICIRemarketing';
    if (user.isNitroCCCampaignEnabled) return 'Nitro_CardOffer';
    return nitroCampaignId(user).version;
  };

  trackCTAClick = (status) => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: 'Get Offer Now',
        pageUrl: window.location.href,
        formId: 'NitroV1-Bangalore-v1',
        status,
        form_version: this.props.user.isNitroFormFillEnabled ? 'with_fields' : 'without_fields',
        ...nitroCampaignId(),
      }),
    );
  };

  saveSubmissionSuccessInUser = () => {
    const _settings = this.props.user.user.settings;
    _settings.clicked_ca_apply_request_done = '1';
    merchantFetch({
      url: 'users',
      mode: 'live',
      method: 'patch',
      data: { settings: _settings },
    }).then(() => {
      updateUser({ settings: _settings });
    });

    setItem('offers_for_you_state', 'hasAppliedCA');
  };

  sendDataToHubspot = (formData) => {
    let formValues = [];
    let formID = '';
    const { user } = this.props;

    if (user.isNitroFormFillEnabled && !this.showKeystoneModal) {
      formValues = [
        ...fields.map((field) => ({
          name: field,
          value: formData[field],
        })),
      ];
      formID = 'e591bdcd-2304-458e-bc4c-72d3f41a75b8';
    } else {
      formValues = [
        {
          name: 'phone',
          value: user?.user?.contact_mobile,
        },
        {
          name: 'email',
          value: user?.user?.email,
        },
        {
          name: 'merchant_id__c',
          value: user?.current,
        },
      ];
      formID = '0ef8b5a3-f35f-48c4-be29-98b207699192';
    }

    return axios({
      method: 'post',
      baseURL: `https://api.hsforms.com/submissions/v3/integration/submit/5558946/${formID}`,
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        fields: [
          ...formValues,
          {
            name: 'campaignid',
            value: this.getCampaignID(),
          },
        ],
        context: {
          hutk: getCookie('hubspotutk'),
          pageUri: window.location.href,
          pageName: document.title,
        },
      },
    });
  };

  sendDataToSalesForce = (formData) => {
    let formValues = [];
    const SF_CHALLENGES =
      'what_are_the_biggest_challenges_you_face_with_your_current_account_today';
    const SF_VENDORS = 'how_do_you_pay_your_vendors_customers';
    const SF_MONTHLY_PAYMENTS__GIVEN = 'how_many_outward_payments_do_you_make_in_a_month';
    const SF_RAZORPAYX_SWITCH = 'how_soon_can_you_switch_to_a_razorpayx_current_account';
    const SF_MONTHLY_PAYMENTS_RECEIVED = 'how_many_payments_do_you_receive_every_month';
    const { user } = this.props;

    if (user.isNitroFormFillEnabled && !this.showKeystoneModal)
      formValues = {
        contact_name: formData[NAME],
        business_name: formData[NAME],
        contact_email: formData[EMAIL],
        contact_mobile: formData[PHONE],
        [SF_CHALLENGES]: formData[CHALLENGES],
        [SF_VENDORS]: formData[VENDORS],
        [SF_MONTHLY_PAYMENTS__GIVEN]: formData[MONTHLY_PAYMENTS__GIVEN],
        [SF_MONTHLY_PAYMENTS_RECEIVED]: formData[MONTHLY_PAYMENTS_RECEIVED],
        [SF_RAZORPAYX_SWITCH]: formData[RAZORPAYX_SWITCH],
        pin_code: null,
        average_monthly_balance: null,
        current_ca: null,
        use_case: null,
      };
    else
      formValues = {
        contact_email: user?.user?.email,
        contact_mobile: user?.user?.contact_mobile,
      };

    const payload = {
      event_type: caReqEventType,
      event_properties: {
        interested_in_current_account: 1,
        product_name: user.isProjectNitroCorporateCard ? 'CARDS' : 'Current_Account',
        source: 'Project Nitro',
        Campaign_ID: this.getCampaignID(),
        form_version: user.isNitroFormFillEnabled ? 'with_fields' : 'without_fields',
        ...formValues,
      },
    };

    return merchantFetch({
      url: `merchant/${this.props.user.current}/salesforce_event`,
      mode: 'live',
      method: 'post',
      data: payload,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  };

  save = (formData) => {
    const { onOfferAccept, onSubmissionSuccess } = this.props;

    onOfferAccept();

    return this.sendDataToHubspot(formData)
      .then(() => this.sendDataToSalesForce(formData))
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'You’ve successfully applied for this offer',
          hidePrevious: true,
        });
        onSubmissionSuccess();
        this.saveSubmissionSuccessInUser();
        this.trackCTAClick('success');
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.message,
        });

        this.trackCTAClick(err.message);
      });
  };

  render() {
    const showNitroFormFields = this.state.showNitroFormFields;
    const { isProjectNitroCorporateCard, isNitroFormFillEnabled } = this.props.user;
    const content = isProjectNitroCorporateCard ? BENEFITS.corporateCards : BENEFITS.other;

    if (this.showKeystoneModal)
      return (
        <KeystoneModal user={this.props.user} save={this.save} tracking={this.props.tracking} />
      );
    if (showNitroFormFields) return <InfoForm save={this.save} tracking={this.props.tracking} />;
    if (this.props.user.isNitroIciciBrandedCampaignEnabled)
      return <NitroICICIModal save={this.save} />;
    if (this.props.user.isNitroIciciRemarketingCampaignEnabled)
      return <NitroICICIModal save={this.save} />;
    if (this.props.user.isNitroCCCampaignEnabled)
      return (
        <NitroCCCampaignModal
          user={this.props.user}
          save={this.save}
          tracking={this.props.tracking}
        />
      );
    return (
      <div className="razorpayx-announcement-details">
        <div className="section">
          <div className="left-section">
            <img
              className="rx-logo"
              src="/dist/css/assets/razorpay-x-logo-white.svg"
              alt="rx-logo"
            />
            {isProjectNitroCorporateCard ? (
              <h3 className="heading">
                Get <span>1.65% pricing</span> & a Corporate Card by switching to a RazorpayX
                Current Account
              </h3>
            ) : (
              <h3 className="heading">
                Get <span>1.65% pricing</span> when you switch to a RazorpayX Current Account
              </h3>
            )}
            <ul className="list">
              {content.map((data) => (
                <li key={data}>
                  <img src="/dist/css/assets/rxca-bullet.svg" />
                  <span>{data}</span>
                </li>
              ))}
            </ul>
            <div className="btn-wrapper">
              <AsyncBtn.Primary
                type="submit"
                class="btn btn-primary"
                onClick={() => {
                  if (isNitroFormFillEnabled) this.setState({ showNitroFormFields: true });
                  else return this.save();
                  return null;
                }}
              >
                Apply For Current Account
              </AsyncBtn.Primary>
            </div>
          </div>
          <div className="right-section">
            <img
              src={
                isProjectNitroCorporateCard
                  ? '/dist/css/assets/rxcacc-dashboard-bg.png'
                  : '/dist/css/assets/rxca-dashboard-bg.svg'
              }
              alt="razorpayx-current-account"
            />
          </div>
        </div>
      </div>
    );
  }
}

const RazorpayXNitroAnnouncement = ({ hideModal, fromWhere, tracking, user }) => {
  const [activeView, setActiveView] = useState('detail-view');

  const onOfferAccept = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_popup_screen1_cta`, {
        ...nitroCampaignId(),
        form_version: user.isNitroFormFillEnabled ? 'with_fields' : 'without_fields',
      }),
    );
  };

  const handleClose = () => {
    setActiveView('detail-view');
    hideModal();
  };

  if (user.isPartOfNeostone)
    return <NitroSelfServe user={user} handleClose={handleClose} tracking={tracking} />;

  if (activeView === 'detail-view') {
    return (
      <div ariaHideApp={false} id="hubspot-ca-form-modal">
        <button type="button" class="close" onClick={handleClose}>
          <i class="i i-close" />
        </button>
        <div className="razorpayx-announcement">
          <DetailView
            onOfferAccept={onOfferAccept}
            onSubmissionSuccess={() => setActiveView('submission-success-view')}
            tracking={tracking}
          />
        </div>
      </div>
    );
  }

  return <SubmissionSuccessfull handleClose={handleClose} />;
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking({
    page: 'ScheduledNitroBanner',
  }),
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
)(RazorpayXNitroAnnouncement);
