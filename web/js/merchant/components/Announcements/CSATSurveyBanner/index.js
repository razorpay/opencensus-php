import { useMemo, useState, useCallback } from 'react';
import AnnouncementBanner from 'common/ui/AnnouncementBanner';
import LocalStorageService from 'common/utils/localStorage';
import getSurveyForm from './getSurveyForm';

export default function CSATSurveyBanner({ user }) {
  const bannerKey = `csat-survey-banner-${user.current}`;
  const [showSurvey, setShowSurvey] = useState(() => !LocalStorageService.getItem(bannerKey));

  const closeSurvey = useCallback(() => {
    setShowSurvey(false);
    LocalStorageService.setItem(bannerKey, 1);
  }, [bannerKey]);

  const SurveyForm = useMemo(() => getSurveyForm(user, closeSurvey), [user, closeSurvey]);

  const openSurvey = useCallback(() => {
    SurveyForm.open();
  }, [SurveyForm]);

  if (showSurvey) {
    return (
      <div className="announcement-banner-container">
        <AnnouncementBanner
          className="Announcement_Banner"
          onClose={closeSurvey}
          title="Your Feedback Matters"
          theme="primary"
          card_id="csat-survey-banner"
        >
          Hello, Developer! Would you like to take a few seconds to help us improve your experience
          with Razorpay?
          <span className="big-dot-separator" />
          <button className="btn-link no-padding" onClick={openSurvey}>
            Click here
          </button>
        </AnnouncementBanner>
      </div>
    );
  }
  return null;
}
