import React from 'react';

import Announcement from 'merchant/components/Announcement';

const getAnnouncement = user => {
  let title = 'Your Feedback Matters',
    theme = 'success';
  let content = (
    <React.Fragment>
      {user.business_dba}, your opinion is important to us. Please fill this
      short survey to let us know how we’re doing.
      <span class="big-dot-separator" />
      <a
        href={`https://razorpay.typeform.com/to/RUn0DJ?mid=${
          user.current
        }&email=${user.contact_email}`}
        target="_blank"
      >
        Click here
      </a>
    </React.Fragment>
  );
  return {
    title,
    content,
    theme,
  };
};

const NPSAnnouncement = ({ user }) => {
  const announcement = getAnnouncement(user);
  return (
    <Announcement
      title={announcement.title}
      theme={announcement.theme}
      bannerKey={`nps-announcement-banner-${user.activation_status}-${
        user.current
      }`}
      canBeClosed={user.isAccepted}
    >
      {announcement.content}
    </Announcement>
  );
};

export default NPSAnnouncement;
