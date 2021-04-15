import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import axios from 'axios';
import { AsyncBtn } from 'common/new-ui/Button';
import { email as validateEmail, phone as validatePhone } from 'common/utils/validators';
import { RadioGroup } from 'common/ui/Forms/RadioGroup';
import isEmpty from '@universe/utils/isEmpty';
import InputField from 'common/ui/Forms/InputField';
import RTracking from 'react-tracking';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getCookie } from '../../utils/cookies';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';
import { caReqEventType } from 'merchant/containers/Home/OnboardingCard/data';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';

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

const BENEFITS = {
  other: [
    'Make rule based payouts seamlessly',
    'Transact 24*7 even on bank holidays',
    'Get a consolidated view of your finances',
    'Make Payouts via NEFT/IMPS/RTGS',
    'Process thousands of payouts at once',
    'Track & automate all your finances',
  ],
  nitro_hyderabad_v2: [
    '500 Free Payouts every months',
    'Make Payouts via IMPS/NEFT/RTGS/UP',
    'Make TDS & GST payments with 1 click',
    'Fully automated vendor payments',
    'Integrations with Tally & more tools',
    'Get a consolidated view of your finances',
  ],
};

export const nitroCampaignId = () => {
  const map = {
    nitro_hyderabad_v2: {
      version: 'nitro_hyderabad_v2',
      version_description: 'Nitro for hyderabad',
    },
    nitro_hyderabad_v3: {
      version: 'nitro_hyderabad_v3',
      version_description: 'Nitro for hyderabad',
    },
    project_nitro: {
      version: 'nitro_bangalore_v1',
      version_description: 'Nitro for bangalore',
    },
    project_nitro_1: {
      version: 'nitro_bangalore_v1',
      version_description: 'Nitro for bangalore',
    },
    project_nitro_feb_2021: {
      version: 'nitro_bangalore_v1',
      version_description: 'Nitro for bangalore',
    },
    project_nitro_feb_2021_1: {
      version: 'nitro_bangalore_v1',
      version_description: 'Nitro for bangalore',
    },
    nitro_midmarket_mumbai_v1: {
      version: 'nitro_midmarket_mumbai_v1',
      version_description: 'Nitro for mumbai mid market',
    },
    GwPth7nhHNdMND: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
    },
    GxtSf8y77iWw9e: {
      version: 'nitro_hyderabad_v4',
      version_description: 'Nitro for hyderabad',
    },
  };

  const getExpStatus = (name) => {
    if (abExperimentsMap.project_nitro.includes(name)) {
      return (window.rzp_user?.splitz_experiments || {})[name]?.variables?.result === 'on';
    }
    return ((window.rzp_user.experiments || {})[name] || {}).result === 'on';
  };

  const featureId = Object.keys(map).find((feature) => getExpStatus(feature));

  return {
    ...map[featureId],
    campaign: 'nitro',
    target_product_feature: 'XCA',
    target_metric: 'MTU',
  };
};

const selector = formValueSelector('customerDetails');

const SubmissionSuccessfull = () => (
  <div className="success-message">
    <h3>Congratulations! Your first step to a better Current Account has begun!</h3>
    <p>You will receive an email shortly that guides you to the next steps.</p>
    <p>
      You’ll also receive a call from our banking experts that’ll assist you with any queries you
      may have about your new Current Account.
    </p>
  </div>
);

class DetailView extends React.Component {
  render() {
    const { onOfferAccept } = this.props;
    const content = BENEFITS[nitroCampaignId().version] || BENEFITS.other;

    return (
      <div className="razorpayx-announcement-details">
        <div className="section">
          <div className="left-section">
            <h3 className="heading">
              Get 1.65% pricing when you switch to a RazorpayX Current Account
            </h3>
            <ul className="list">
              {content.map((data) => (
                <li key={data}>
                  <img src="https://razorpay.com/assets/payouts/footer/footer-pointer.png" />
                  {data}
                </li>
              ))}
            </ul>
            <div className="btn-wrapper">
              <button class="btn btn-primary logout-btn" onClick={onOfferAccept}>
                Apply For Offer
              </button>
            </div>
          </div>
          <div className="right-section">
            <img src="https://razorpay.com/assets/x/macbook.svg" alt="macbook-img"></img>
          </div>
        </div>
      </div>
    );
  }
}

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
  trackCTAClick = (status) => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: 'Request for a Current Account',
        pageUrl: window.location.href,
        formId: 'NitroV1-Bangalore-v1',
        status,
        ...nitroCampaignId(),
      }),
    );
  };

  saveSubmissionSuccessInUser = () => {
    const _settings = this.props.user.user.settings;
    _settings['clicked_ca_apply_request_done'] = '1';
    merchantFetch({
      url: 'users',
      mode: 'live',
      method: 'patch',
      data: { settings: _settings },
    }).then(() => {
      updateUser({ settings: _settings });
    });
  };

  sendDataToHubspot = (formData) => {
    return axios({
      method: 'post',
      baseURL:
        'https://api.hsforms.com/submissions/v3/integration/submit/5558946/e591bdcd-2304-458e-bc4c-72d3f41a75b8',
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        fields: [
          ...fields.map((field) => ({
            name: field,
            value: formData[field],
          })),
          {
            name: 'campaignid',
            value: nitroCampaignId().version,
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

  sendDataToSalesForce = (data) => {
    const SF_CHALLENGES =
      'what_are_the_biggest_challenges_you_face_with_your_current_account_today';
    const SF_VENDORS = 'how_do_you_pay_your_vendors_customers';
    const SF_MONTHLY_PAYMENTS__GIVEN = 'how_many_outward_payments_do_you_make_in_a_month';
    const SF_RAZORPAYX_SWITCH = 'how_soon_can_you_switch_to_a_razorpayx_current_account';
    const SF_MONTHLY_PAYMENTS_RECEIVED = 'how_many_payments_do_you_receive_every_month';

    const payload = {
      event_type: caReqEventType,
      event_properties: {
        interested_in_current_account: 1,
        product_name: 'Current_Account',
        source: 'Project Nitro',
        Campaign_ID: nitroCampaignId().version,
        contact_name: data[NAME],
        business_name: data[NAME],
        contact_email: data[EMAIL],
        contact_mobile: data[PHONE],
        [SF_CHALLENGES]: data[CHALLENGES],
        [SF_VENDORS]: data[VENDORS],
        [SF_MONTHLY_PAYMENTS__GIVEN]: data[MONTHLY_PAYMENTS__GIVEN],
        [SF_MONTHLY_PAYMENTS_RECEIVED]: data[MONTHLY_PAYMENTS_RECEIVED],
        [SF_RAZORPAYX_SWITCH]: data[RAZORPAYX_SWITCH],
        pin_code: null,
        average_monthly_balance: null,
        current_ca: null,
        use_case: null,
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
    const { onSubmissionSuccess } = this.props;

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
    const { handleSubmit, change } = this.props;
    const shouldSubmitBeDisabled = fields.some((field) => isEmpty(this.props[field]));

    return (
      <form autoComplete="off" onSubmit={handleSubmit(this.save)}>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label className="control-label label-required">Full name</label>
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
          <div class="col-md-6">
            <div class="form-group">
              <label className="control-label label-required">Phone number</label>
              <div>
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
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label className="control-label label-required">Work email</label>
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
          <div class="col-md-6">
            <div class="form-group">
              <label className="control-label label-required">
                What are the biggest challenges with your Current Account today?
              </label>
              <div>
                <Field
                  name={CHALLENGES}
                  placeholder="What are the biggest challenges with your Current Account today?"
                  component={InputField}
                  class="form-control"
                  onBlur={this.props.onBlur}
                  required
                />
              </div>
            </div>
          </div>
        </div>
        <div className="row">
          <div className="col-md-6">
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
          <div className="col-md-6">
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
        </div>
        <div className="row">
          <div className="col-md-6">
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
          <div className="col-md-6">
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
        </div>
        <div class="Modal__actions">
          <AsyncBtn.Primary
            type="submit"
            class="btn btn-primary"
            disabled={shouldSubmitBeDisabled}
            onClick={handleSubmit(this.save)}
          >
            Request for a Current Account
          </AsyncBtn.Primary>
        </div>
      </form>
    );
  }
}

const RazorpayXNitroAnnouncement = ({ hideModal, fromWhere, tracking, user }) => {
  const [activeView, setActiveView] = useState('detail-view');

  const onOfferAccept = () => {
    setActiveView('form-view');

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_popup_screen1_cta`, {
        ...nitroCampaignId(),
      }),
    );
  };

  const handleClose = () => {
    setActiveView('detail-view');
    hideModal();
  };

  return (
    <div ariaHideApp={false} id="hubspot-ca-form-modal">
      <button type="button" class="close" onClick={handleClose}>
        <i class="i i-close" />
      </button>
      <div className="razorpayx-announcement">
        <img className="rx-logo" src="https://lp.razorpay.com/hubfs/logo1.png" alt="rx-logo" />
        {activeView === 'detail-view' && <DetailView onOfferAccept={onOfferAccept} />}
        {activeView === 'form-view' && (
          <InfoForm
            onSubmissionSuccess={() => setActiveView('submission-success-view')}
            tracking={tracking}
          />
        )}
        {activeView === 'submission-success-view' && <SubmissionSuccessfull />}
      </div>
    </div>
  );
};

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
  RTracking({
    page: 'ScheduledNitroBanner',
  }),
)(RazorpayXNitroAnnouncement);
