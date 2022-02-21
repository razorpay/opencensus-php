import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { isEmail, isPhone, isValidPinCode } from 'common/utils/validators';
import { merchantFetch } from 'merchant/utils/ajax';
import Form from 'common/new-ui/Form';
import { caReqEventType } from 'merchant/containers/Home/OnboardingCard/data';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { showNotification } from 'merchant_common/reducers/notifications';
import RTracking from 'react-tracking';

const CaApplyForm = (props) => {
  const { onClose, user, showNotification, onSuccess, showDeadlineExtentionMessage } = props;
  const [formDisabled, setFormDisabled] = React.useState(false);
  const sendDataToHubspot = (data) => {
    const payload = JSON.stringify(data);
    const portalId = '5558946';
    const formId = 'fa3c8dba-4c22-42d6-92bf-c1b01bf4da54';
    const apiURl = `https://api.hsforms.com/submissions/v3/integration/submit/${portalId}/${formId}`;
    // eslint-disable-next-line no-undef
    axios({
      url: apiURl,
      method: 'post',
      data: payload,
      headers: {
        'Content-Type': 'application/json',
      },
    }).then((data) => {
      console.log('data::::::', data);
    });
  };

  const onSubmit = (data) => {
    if (
      data.name.length <= 0 ||
      data.pin_code.length <= 0 ||
      data.email.length <= 0 ||
      data.phone.length <= 0
    ) {
      showNotification({
        type: 'error',
        message: 'Please fill all the required fields',
      });
      return;
    }
    if (!isPhone(data.phone)) {
      showNotification({
        type: 'error',
        message: 'Please enter a valid phone number',
      });
      return;
    }
    if (!isEmail(data.email)) {
      showNotification({
        type: 'error',
        message: 'Please enter a valid email',
      });
      return;
    }
    if (!isValidPinCode(data.pin_code)) {
      showNotification({
        type: 'error',
        message: 'Please enter a valid Pincode',
      });
      return;
    }
    if (!data.terms_condition) {
      showNotification({
        type: 'error',
        message: 'Please acknowledge the Terms and Conditions to continue',
      });
      return;
    }
    const { id } = user.merchant;

    const payload = {
      event_type: caReqEventType,
      event_properties: {
        interested_in_current_account: 1,
        pin_code: data.pin_code,
        name: data.name,
        email: data.email,
        contact_mobile: data.phone,
        average_monthly_balance: null,
        current_ca: null,
        use_case: null,
        product_name: 'Current_Account',
        source: 'PG-NEO',
      },
    };
    props.tracking.trackEvent(
      window.rzpQ.merchantActions().clicked('dashboard.neopricing_tracker', {
        clicked_on: 'apply',
        status: 'application_form',
      }),
    );
    const hubspot_payload = {
      fields: [
        {
          name: 'full_name',
          value: data.name,
        },
        {
          name: 'company',
          value: user.business_name,
        },
        {
          name: 'email',
          value: data.email,
        },
        {
          name: 'phone',
          value: data.phone,
        },
        {
          name: 'pincode__c',
          value: data.pin_code,
        },
      ],
    };
    setFormDisabled(true);
    sendDataToHubspot(hubspot_payload);
    merchantFetch({
      url: `merchant/${id}/salesforce_event`,
      mode: 'live',
      method: 'post',
      data: payload,
      headers: {
        'Content-Type': 'application/json',
      },
    }).then(() => {
      setFormDisabled(false);
      onSuccess();
    });
  };

  const { business_name } = user;
  const { name, email, contact_mobile } = user.user;
  return (
    <div className={`ca-apply ${formDisabled && 'disable-ca-form'}`}>
      <div className="top-action">
        <div className="title">Start your CA process</div>
        <div className="cross-btn" onClick={onClose}>
          <i class="i i-close" />
        </div>
      </div>
      <div className="info">
        {showDeadlineExtentionMessage
          ? 'Application deadline has been extended!'
          : 'Please submit the following details'}
      </div>
      <Form onSubmit={onSubmit}>
        <Input name="name" label="Name" defaultValue={name} required />
        <Input name="email" type="email" label="E-mail" defaultValue={email} required />
        <Input name="business_name" label="Business Name" value={business_name} disabled required />
        <Input
          name="phone"
          type="number"
          label="Phone number"
          defaultValue={contact_mobile}
          required
        />
        <Input name="pin_code" label="Pincode" type="number" required />
        <Input.Check
          name="terms_condition"
          fieldLabel={
            <div className="terms_condition">
              I understand the
              <a
                className="btn btn-link"
                href="https://razorpay.com/links/neo-plan-terms-conditions"
                target="_blank"
                rel="noreferrer noopener"
              >
                terms and conditions
              </a>
              of neo plan
            </div>
          }
        />
        <Button.Primary type="submit" className="full-width">
          Apply
        </Button.Primary>
      </Form>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});
const mapDispatchToProps = (dispatch) => ({
  showNotification: bindActionCreators(showNotification, dispatch),
});

// eslint-disable-next-line babel/new-cap
export default RTracking({ page: 'RXNeoCaApply' })(
  connect(mapStateToProps, mapDispatchToProps)(CaApplyForm),
);
