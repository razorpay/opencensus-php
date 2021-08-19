import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const SURVEY_LINKS = {
  PL: 'https://razorpay.typeform.com/to/FKihHgEY',
  PP: 'https://razorpay.typeform.com/to/qtLFcZbq',
  PG_1m: 'https://razorpay.typeform.com/to/ndlvP2XX',
  PG_6m: 'https://razorpay.typeform.com/to/YFrkZOZp',
};

const getLink = (user) => {
  if (user.isNPSAnnouncementPL) {
    return { link: SURVEY_LINKS.PL, cohort: 'pl' };
  }

  if (user.isNPSAnnouncementPP) {
    return { link: SURVEY_LINKS.PP, cohort: 'pp' };
  }

  if (user.isNPSAnnouncementPG1m) {
    return { link: SURVEY_LINKS.PG_1m, cohort: 'pg_1m' };
  }

  if (user.isNPSAnnouncementPG6m) {
    return { link: SURVEY_LINKS.PG_6m, cohort: 'pg_6m' };
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
      bannerKey={`nps-banner-mar-21-${survey.cohort}-${user.current}`}
      canBeClosed={true}
      card_id="nps-banner-mar-21"
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
