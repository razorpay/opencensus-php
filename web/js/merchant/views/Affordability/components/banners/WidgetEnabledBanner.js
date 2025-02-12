import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { useEffect } from 'react';
import './affordability-banner.styl';
import track from './track';

const WidgetEnabledBanner = ({ trialDays }) => {
  useEffect(() => {
    track.widgetLiveBanner();
    if (trialDays) {
      track.trialPeriod({
        trial_days: trialDays,
      });
    }
  }, []);

  return (
    <AnnouncementBanner
      className="widget-live-banner"
      title={trialDays ? `🎁 ${trialDays} Days free Trial` : 'Widget Live'}
      theme="success"
    >
      <span className="display-inline">
        Affordability Widget is now enabled on your website.
        {trialDays ? (
          <>
            &nbsp;You have been given <span className="trial-days">{trialDays}-day free</span>{' '}
            trial!
          </>
        ) : null}
      </span>
    </AnnouncementBanner>
  );
};

// eslint-disable-next-line babel/new-cap
export default WidgetEnabledBanner;
