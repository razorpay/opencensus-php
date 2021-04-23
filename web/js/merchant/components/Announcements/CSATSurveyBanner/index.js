import { useMemo, useState, useCallback } from 'react';
import { makePopup } from '@typeform/embed';
import AnnouncementBanner from 'common/ui/AnnouncementBanner';
import LocalStorageService from 'common/utils/localStorage';

export default function CSATSurveyBanner({ user }) {
  const { current: merchantId, email } = user;
  const bannerKey = `csat-survey-banner-${user.current}`;
  const [showSurvey, setShowSurvey] = useState(() => !LocalStorageService.getItem(bannerKey));
  const surveyURL = `https://razorpay.typeform.com/to/Kzw8bOUb?mid=${merchantId}&email=${email}`;

  const closeSurvey = useCallback(() => {
    setShowSurvey(false);
    LocalStorageService.setItem(bannerKey, 1);
  }, [bannerKey]);

  const SurveyForm = useMemo(
    () =>
      makePopup(surveyURL, {
        mode: 'popup',
        hideHeaders: true,
        hideFooters: true,
        onSubmit: closeSurvey,
      }),
    [surveyURL, closeSurvey],
  );

  const openSurvey = useCallback(() => {
    SurveyForm.open();
  }, [SurveyForm]);

  const shouldShowBannerToUser = user.showCSATSurvey(); // check user mid to show banner

  if (shouldShowBannerToUser && showSurvey) {
    return (
      <div className="announcement-banner-container">
        <AnnouncementBanner
          className="Announcement_Banner"
          onClose={closeSurvey}
          title="Your Feedback Matters"
          theme="primary"
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
