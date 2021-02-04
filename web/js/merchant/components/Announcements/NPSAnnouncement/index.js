import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const SURVEY_LINKS = {
  PL: 'https://razorpay.typeform.com/to/S3DutR3C',
  PP: 'https://razorpay.typeform.com/to/lwY1vvb8',
  PG_1m: 'https://razorpay.typeform.com/to/xuZmfJ64',
};

const getLink = (user) => {
  if (user.isFeatureEnabled('nps_survey_payment_links')) {
    return { link: SURVEY_LINKS.PL, cohort: 'pl' };
  }

  if (user.isFeatureEnabled('nps_survey_payment_pages')) {
    return { link: SURVEY_LINKS.PP, cohort: 'pp' };
  }

  if (user.isFeatureEnabled('nps_survey_pg_1m')) {
    return { link: SURVEY_LINKS.PG_1m, cohort: 'pg_1m' };
  }

  return null;
};

const NPSAnnouncement = ({ user }) => {
  const survey = getLink(user);

  if (!survey) {
    return null;
  }

  return (
    <AnnouncementBanner
      title="Your Feedback Matters"
      theme="success"
      bannerKey={`nps-banner-feb-21-${survey.cohort}-${user.current}`}
      canBeClosed={true}
    >
      Hello! Request you to fill in this quick feedback survey about your experience with Razorpay.
      <span className="big-dot-separator" />
      <a
        href={`${survey.link}?mid=${user.current}&source=dashboard&email=${user.user.email}`}
        target="_blank"
        rel="noopener noreferrer"
      >
        Click here
      </a>
    </AnnouncementBanner>
  );
};

export default NPSAnnouncement;
