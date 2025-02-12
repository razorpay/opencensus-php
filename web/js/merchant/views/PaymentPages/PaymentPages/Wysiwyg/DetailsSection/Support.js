import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import { isEmail, isPhone } from 'common/utils/validators';
import { fetchSupportDetail } from 'merchant/reducers/support_detail';
import { prefillContactDetails } from 'merchant/reducers/wysiwyg';

import track from '../track';

const phoneIcon = (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
    <path d="M0 0h24v24H0z" fill="none" />
    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
  </svg>
);

const emailIcon = (
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 30">
    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
    <path d="M0 0h24v24H0z" fill="none" />
  </svg>
);

class WysiwygSupport extends React.PureComponent {
  componentDidMount() {
    if (!this.props.support_email || !this.props.support_contact) {
      // checking if global support details populated
      if (Object.keys(this.props.globalSupportDetails).length) {
        this.populateContactDetails();
      } else {
        this.props.fetchSupportDetail().then(() => {
          this.populateContactDetails();
        });
      }
    }
  }

  populateContactDetails() {
    const contactDetails = {
      support_email: this.props.support_email || this.props.globalSupportDetails.email,
      support_contact: this.props.support_contact || this.props.globalSupportDetails.phone,
    };
    this.props.prefillContactDetails(contactDetails);

    // if both values are present, we fire prefill event
    if (contactDetails.support_email && contactDetails.support_contact) {
      track.wysiwyg.supportEmail(contactDetails.support_email, true);
      track.wysiwyg.supportPhone(contactDetails.support_contact, true);
    }
  }

  onSupportFieldBlur = (e) => {
    this.props.updateData(e);

    const value = e.target.value;

    if (e.target.name === 'support_email') {
      track.wysiwyg.supportEmail(value);
    } else {
      track.wysiwyg.supportPhone(value);
    }
  };

  render() {
    const { support_email, support_contact, supportPhoneRef, supportEmailRef } = this.props;

    return (
      <div id="support-details">
        <label>Contact Us:</label>
        <SupportSubField
          name="support_email"
          ref={supportEmailRef}
          placeholder="Enter support email"
          icon={emailIcon}
          defaultValue={support_email}
          onBlur={this.onSupportFieldBlur}
          addButtonLabel="Add Support Email"
          validator={(val) => {
            if (val && !isEmail(val)) {
              return 'Invalid Email';
            }
            return '';
          }}
          autoRender
          info="Please add your support email"
        />

        <SupportSubField
          name="support_contact"
          type="tel"
          ref={supportPhoneRef}
          placeholder="Enter support phone"
          icon={phoneIcon}
          defaultValue={support_contact}
          onBlur={this.onSupportFieldBlur}
          addButtonLabel="Add Support Phone"
          validator={(val) => {
            if (val && !isPhone(val)) {
              return 'Invalid Number';
            }
            return '';
          }}
          autoRender
          info="Please add your support contact number"
        />
      </div>
    );
  }
}

const SupportSubFieldForwardRef = React.forwardRef((props, ref) => {
  const { icon, addButtonLabel, reference, ...rest } = props;

  return (
    <div className="sub-detail">
      {icon}
      <Input name={name} ref={ref} {...rest} />
    </div>
  );
});
const SupportSubField = React.memo(SupportSubFieldForwardRef);

export default connect(
  (state) => ({
    globalSupportDetails: state.supportdetails.merchantSupportDetail.data,
  }),
  {
    prefillContactDetails,
    fetchSupportDetail,
  },
)(WysiwygSupport);
