import { useMemo, useState, useCallback } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import LocalStorageService from 'common/utils/localStorage';
import getSurveyForm from './getSurveyForm';

function AppStoreIntentBanner({ user, title, content, cardId, surveyUrl }) {
  const bannerKey = `${cardId}-${user.current}`;
  const [showSurvey, setShowSurvey] = useState(() => !LocalStorageService.getItem(bannerKey));

  const closeSurvey = useCallback(() => {
    setShowSurvey(false);
    LocalStorageService.setItem(bannerKey, 1);
  }, [bannerKey]);

  const SurveyForm = useMemo(() => getSurveyForm(user, closeSurvey, surveyUrl), [
    user,
    closeSurvey,
    surveyUrl,
  ]);

  const openSurvey = useCallback(() => {
    SurveyForm.open();
  }, [SurveyForm]);

  if (showSurvey) {
    return (
      <AnnouncementBanner
        title={title}
        theme="primary"
        card_id={cardId}
        canBeClosed={true}
        onClose={closeSurvey}
      >
        <span class="display-inline">
          {content}{' '}
          <button
            className="Button--primary Button scheduled-btn-act btn-border"
            onClick={openSurvey}
          >
            Get early access
          </button>
        </span>
      </AnnouncementBanner>
    );
  }

  return null;
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    null,
  ),
)(AppStoreIntentBanner);
