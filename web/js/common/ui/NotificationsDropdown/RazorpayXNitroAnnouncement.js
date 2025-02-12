import React, { useState } from 'react';
import axios from 'axios';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';
import { Field, formValueSelector, reduxForm } from 'redux-form';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Textarea from 'common/ui/Forms/AutoResizeTextarea';
import InputField from 'common/ui/Forms/InputField';
import { RadioGroup } from 'common/ui/Forms/RadioGroup';
import { getCookie } from 'common/utils/cookies';
import { setItem } from 'common/utils/localStorage';
import { email as validateEmail, phone as validatePhone } from 'common/utils/validators';
import CrossSellSubscriptionsModal from 'merchant/components/Announcements/CrossSellSubscriptions/CrossSellSubscriptionsModal';
import { caReqEventType } from 'merchant/containers/Home/OnboardingCard/data';
import { getUser } from 'merchant/store';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateUser } from 'merchant_common/reducers/user';

import RazorpayXLogoWhite from 'assets/razorpay-x-logo-white.svg';
import RXCABullet from 'assets/rxca-bullet.svg';
import RXCADashboardBG from 'assets/rxca-dashboard-bg.svg';

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
  return {
    campaign: 'nitro',
    target_metric: 'MTU',
  };
};

export const getCampaignID = () => {
  const user = getUser();
  return nitroCampaignId(user).version;
};

export const getProductName = () => {
  return 'Current_Account';
};

const selector = formValueSelector('customerDetails');

class InfoForm extends React.Component {
  render() {
    const { handleSubmit, change } = this.props;
    const shouldSubmitBeDisabled = fields.some((field) => isEmpty(this.props[field]));

    return (
      <form autoComplete="off">
        <div className="left-section">
          <img className="rx-logo" src={RazorpayXLogoWhite} alt="rx-logo" />
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">Name</label>
              <div>
                <Field
                  name={NAME}
                  placeholder="Full Name"
                  component={InputField}
                  className="form-control"
                  autoFocus
                  required
                />
              </div>
            </div>
          </div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">Email ID</label>
              <div>
                <Field
                  name={EMAIL}
                  placeholder="Work email"
                  component={InputField}
                  className="form-control"
                  validate={validateEmail('Please provide a valid email')}
                  onBlur={this.props.onBlur}
                  required
                />
              </div>
            </div>
          </div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">Phone number</label>
              <div className="phone-field">
                <Field
                  name="country_code"
                  component={InputField}
                  className="form-control"
                  readOnly
                />
                <Field
                  name={PHONE}
                  placeholder="Phone number"
                  component={InputField}
                  className="form-control"
                  onBlur={this.props.onBlur}
                  validate={validatePhone('Please provide a valid phone')}
                  required
                />
              </div>
            </div>
          </div>
          <div className="row">
            <div className="form-group">
              <label className="control-label label-required">
                What are the biggest challenges with your Current Account today?
              </label>
              <div>
                <Field
                  name={CHALLENGES}
                  placeholder="Tell us about your challenges here"
                  component={Textarea}
                  className="form-control"
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
            className="btn btn-primary submit-btn"
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

const InfoFormComponent = compose(
  connect(
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
  ),
  reduxForm({
    form: 'customerDetails',
  }),
)(InfoForm);

export const SubmissionSuccessfull = ({ handleClose }) => {
  return (
    <div className="rxca-submit-finish-modal">
      <div className="header">
        <div className="title">
          Congratulations! We’re processing your request for a RazorpayX powered Current Account.
        </div>
        <button type="button" className="close" onClick={handleClose}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="description">
        <p>
          Our sales heroes will get in touch with you shortly. In the meantime, we highly recommend
          you keep the required documents ready so we can speed up the process.
        </p>
        <a
          href="https://razorpay.com/docs/razorpayx/current-account/"
          target="_blank"
          rel="noreferrer noopener"
        >
          <Button.Primary className="btn btn-primary" type="button">
            View Documents Required
          </Button.Primary>
        </a>
      </div>
      <p className="footer">Once your Current Account is created, your offer will be activated.</p>
    </div>
  );
};

class DetailView extends React.Component {
  state = {
    showNitroFormFields: false,
  };

  trackCTAClick = (status) => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: 'Get Offer Now',
        pageUrl: window.location.href,
        formId: 'NitroV1-Bangalore-v1',
        trackingID: 'NitroV1-Bangalore-v1',
        status,
        form_version: 'without_fields',
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

  sendDataToHubspot = () => {
    let formID = '';
    const { user } = this.props;

    const formValues = [
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
            value: getCampaignID(),
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

  sendDataToSalesForce = () => {
    const { user } = this.props;

    const formValues = {
      contact_email: user?.user?.email,
      contact_mobile: user?.user?.contact_mobile,
    };

    const payload = {
      event_type: caReqEventType,
      event_properties: {
        interested_in_current_account: 1,
        product_name: getProductName(),
        source: 'Project Nitro',
        Campaign_ID: getCampaignID(),
        form_version: 'without_fields',
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

  save = () => {
    const { onOfferAccept, onSubmissionSuccess } = this.props;

    onOfferAccept();

    return this.sendDataToHubspot()
      .then(() => this.sendDataToSalesForce())
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
    const { isCSSEducationEnabled, isCSSOtherBusinessesEnabled } = this.props.user;
    const content = BENEFITS.other;

    if (showNitroFormFields)
      return <InfoFormComponent save={this.save} tracking={this.props.tracking} />;
    if (isCSSEducationEnabled || isCSSOtherBusinessesEnabled)
      return <CrossSellSubscriptionsModal user={this?.props?.user} />;

    return (
      <div className="razorpayx-announcement-details">
        <div className="section">
          <div className="left-section">
            <img className="rx-logo" src={RazorpayXLogoWhite} alt="rx-logo" />
            <h3 className="heading">
              Get <span>1.65%* pricing</span> when you switch to a RazorpayX Current Account
            </h3>
            <ul className="list">
              {content.map((data) => (
                <li key={data}>
                  <img src={RXCABullet} />
                  <span>{data}</span>
                </li>
              ))}
            </ul>
            <div className="btn-wrapper">
              <AsyncBtn.Primary type="submit" className="btn btn-primary" onClick={this.save}>
                Apply For Current Account
              </AsyncBtn.Primary>
            </div>
          </div>
          <div className="right-section">
            <img src={RXCADashboardBG} alt="razorpayx-current-account" />
          </div>
        </div>
      </div>
    );
  }
}

const DetailViewComponent = connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    showNotification,
  },
)(DetailView);

const RazorpayXNitroAnnouncement = ({ hideModal, fromWhere, tracking }) => {
  const [activeView, setActiveView] = useState('detail-view');

  const onOfferAccept = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_popup_screen1_cta`, {
        ...nitroCampaignId(),
        form_version: 'without_fields',
      }),
    );
  };

  const handleClose = () => {
    setActiveView('detail-view');
    hideModal();
  };

  if (activeView === 'detail-view') {
    return (
      // eslint-disable-next-line react/no-unknown-property
      <div ariaHideApp={false} id="hubspot-ca-form-modal">
        <button type="button" className="close btn-close-modal" onClick={handleClose}>
          <i className="i i-close" />
        </button>
        <div className="razorpayx-announcement">
          <DetailViewComponent
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
  rTracking({
    page: 'ScheduledNitroBanner',
  }),
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
)(RazorpayXNitroAnnouncement);
