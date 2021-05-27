import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import axios from 'axios';
import { AsyncBtn } from 'common/new-ui/Button';
import RTracking from 'react-tracking';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getCookie } from '../../utils/cookies';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';
import { caReqEventType } from 'merchant/containers/Home/OnboardingCard/data';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';

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
    H7361l13HhrBgO: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
    },
    H75RfvQFecKHsT: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
    },
    H75Qu5SInWp3SQ: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
    },
    H75Q0JHjnUd5xs: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
    },

    H6qGPCBPduY7Gl: {
      version: 'nitro_kolkata_v1',
      version_description: 'Nitro for kolkata',
    },
    H6qJ2X77dqHG9I: {
      version: 'nitro_chennai_v1',
      version_description: 'Nitro for chennai',
    },
    H6qIJWTzrqt54X: {
      version: 'nitro_jaipur_v1',
      version_description: 'Nitro for jaipur',
    },
    H6qHJJnYOtwfoc: {
      version: 'nitro_surat_v1',
      version_description: 'Nitro for surat',
    },

    // Beta Nitro v3
    HF0Ml2IU6gH9rt: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
    },
    HF0NZThSDtgNGB: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
    },
    HF0OIJAqllZPRu: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
    },
    HF0Ox4LNEgYHbV: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
    },

    // Prod Nitro v3
    HExafLb492K7LU: {
      version: 'project-nitro-gandhinagar-v1',
      version_description: 'Nitro for gandhinagar',
    },
    HExehMbAqYqlWF: {
      version: 'project-nitro-vadodara-v1',
      version_description: 'Nitro for vadodara',
    },
    HExiHP6GBUEVcu: {
      version: 'project-nitro-ahmedabad-v1',
      version_description: 'Nitro for ahmedabad',
    },
    HExnzHcFfimA6u: {
      version: 'project-nitro-bangalore-v1',
      version_description: 'Nitro for bangalore',
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

@connect(
  (state) => ({
    user: state.session.user,
  }),
  {
    showNotification,
  },
)
class DetailView extends React.Component {
  trackCTAClick = (status) => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: 'Get Offer Now',
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

  sendDataToHubspot = () => {
    const { user } = this.props;

    return axios({
      method: 'post',
      baseURL:
        'https://api.hsforms.com/submissions/v3/integration/submit/5558946/0ef8b5a3-f35f-48c4-be29-98b207699192',
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        fields: [
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

  sendDataToSalesForce = () => {
    const { user } = this.props;

    const payload = {
      event_type: caReqEventType,
      event_properties: {
        interested_in_current_account: 1,
        product_name: 'Current_Account',
        source: 'Project Nitro',
        Campaign_ID: nitroCampaignId().version,
        contact_email: user?.user?.email,
        contact_mobile: user?.user?.contact_mobile,
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
              <AsyncBtn.Primary type="submit" class="btn btn-primary" onClick={this.save}>
                Get Offer Now
              </AsyncBtn.Primary>
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

const RazorpayXNitroAnnouncement = ({ hideModal, fromWhere, tracking }) => {
  const [activeView, setActiveView] = useState('detail-view');

  const onOfferAccept = () => {
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
        {activeView === 'detail-view' && (
          <DetailView
            onOfferAccept={onOfferAccept}
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
  RTracking({
    page: 'ScheduledNitroBanner',
  }),
)(RazorpayXNitroAnnouncement);
