import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const SURVEY_LINKS = {
  PL: 'https://razorpay.typeform.com/to/LOb8O1a1',
  PP: 'https://razorpay.typeform.com/to/rC2nqalJ',
  PG_1m: 'https://razorpay.typeform.com/to/FtXjOpIi',
  // PG_6m: 'https://razorpay.typeform.com/to/RvgxGRGM',
  // PG_12m: 'https://razorpay.typeform.com/to/mZcTfb8R',
  // other_products: 'https://razorpay.typeform.com/to/suSnn21l',
};

const getLink = (user) => {
  if (user.isFeatureEnabled('nps_survey_payment_links')) {
    return { link: SURVEY_LINKS['PL'], cohort: 'pl' };
  }

  if (user.isFeatureEnabled('nps_survey_payment_pages')) {
    return { link: SURVEY_LINKS['PP'], cohort: 'pp' };
  }

  if (user.isFeatureEnabled('nps_survey_pg_1m')) {
    return { link: SURVEY_LINKS['PG_1m'], cohort: 'pg_1m' };
  }

  // if (user.isFeatureEnabled('nps_survey_pg_6m')) {
  //   return { link: SURVEY_LINKS['PG_6m'], cohort: 'pg_6m' };
  // }

  // if (user.isFeatureEnabled('nps_survey_pg_12m')) {
  //   return { link: SURVEY_LINKS['PG_12m'], cohort: 'pg_12m' };
  // }

  // if (user.isFeatureEnabled('nps_survey_other_products')) {
  //   return { link: SURVEY_LINKS['other_products'], cohort: 'other_products' };
  // }

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
      bannerKey={`nps-banner-dec-20-${survey.cohort}-${user.current}`}
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
