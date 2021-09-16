import React from 'react';
import Alert from 'common/new-ui/Alert';
import { Link } from 'react-router-dom';

const NoticeMessage = ({
  isFormLocked,
  isFormSubmitted,
  isOnKYCTab,
  activeTab,
  activationStatus,
}) => {
  let icon, msg;
  let Component = Alert.Info;
  let secondaryMsg = 'For any clarifications, you can';
  const ticketLink = <Link to="#ticket">write to support</Link>;
  const showFormDisabledAlert = (isFormLocked || isFormSubmitted) && !isOnKYCTab();
  if (!showFormDisabledAlert) {
    return '';
  }
  if (activationStatus === 'activated') {
    // **1. Alert: Account Activated

    icon = 'i-done-all';
    msg = 'Your account is activated.';
    secondaryMsg = <>For any changes, please {ticketLink}.</>;
  } else if (activationStatus === 'needs_clarification' && activeTab !== 2) {
    icon = 'i-warning';
    Component = Alert.Warning;
    msg = `There are issues with your activation form. Please check your mail and respond at the earliest.`;
    secondaryMsg = '';
  } else if (activationStatus === 'rejected') {
    // **3. Alert: Form Rejected

    icon = 'i-close';
    Component = Alert.Error;
    msg =
      'Your activation form has been rejected by our partner banks. Hence, we would not be able support your business at this moment.';
    secondaryMsg = 'We have sent you an email with the details.';
  } else if (isFormLocked && isFormSubmitted) {
    // **4. Alert: Form is Locked (for reasons other than above)
    // 'locked' status has more priority than 'submitted'
    // If admins locked form before submiddion, then this alert is not shown

    icon = 'i-outline-lock';
    msg =
      'Your activation form is under review. We will let you know once your account gets activated.';
    secondaryMsg = '';
  } else if (isFormSubmitted) {
    // **5. Alert: Form is Submitted

    icon = 'i-check';
    msg = 'Our team will review the form and submitted documents.';
    secondaryMsg = 'We will reach out on your contact email for all updates.';
  }

  if (msg) {
    return (
      <Component iconBefore={icon}>
        {msg}
        <div className="side-description">{secondaryMsg}</div>
      </Component>
    );
  }
  return '';
};

export default NoticeMessage;
