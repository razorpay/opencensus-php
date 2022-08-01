import { setTrackData } from 'common/utils/googleAnalytics';
import { EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT } from 'merchant/views/Settlements/Settlements/ga';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const pageTitle = 'Dashboard - Home V2';

export const track = setTrackData({ eventCategory: pageTitle });

export const trackError = (error) => {
  const eventLabel = typeof error === 'object' ? JSON.stringify(error) : String(error);

  track({
    eventAction: 'Error Triggered',
    eventLabel,
  });
};

export const trackPresetChange = (preset) => {
  track({
    eventAction: 'Select - Date Dropdown',
    eventLabel: preset.name,
    eventValue: preset.value,
  });
};

export const trackDatesChange = (from, to) => {
  const seconds = to.unix() - from.unix();
  const fromDateString = from.toLocaleString();
  const toDateString = to.toLocaleString();

  track({
    eventAction: 'Select - Start Date',
    eventLabel: fromDateString,
  });

  track({
    eventAction: 'Select - End Date',
    eventLabel: toDateString,
  });

  track({
    eventAction: 'Select - Date Range',
    eventLabel: `${fromDateString} - ${toDateString}`,
    eventValue: seconds,
  });
};

export const trackGoToLinks = (toText, fromText) => {
  track({
    eventAction: `Go to - ${toText}`,
    eventLabel: `From ${fromText}`,
  });
};

export const trackSettlementsClick = () => {
  trackGoToLinks('Settlements', pageTitle);
};

export const trackPlatformAnalyticsHidden = (percent) => {
  track({
    eventAction: 'Data Hide - Platform',
    eventLabel: `${percent}%`,
    eventValue: percent,
  });
};

export const trackForceOldDashboard = () => {
  track({
    eventAction: 'Force Old Dashboard on Mobile',
    eventLabel: `Resolution - ${window.innerWidth}x${window.outerHeight}`,
  });
};

export const trackNoData = (description) => {
  track({
    eventAction: 'No Data Found',
    eventLabel: description,
  });
};

export const trackViewTour = () => {
  track({
    eventAction: 'Click - View Tour on Banner',
  });
};

export default track;

export const trackES = setTrackData({
  eventCategory: EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
});

export const trackSettleNow = () => {
  trackES({
    eventAction: 'Click - Settle Now',
    eventLabel: 'Home',
  });
};

const iaWelcomeTrack = setTrackData({
  eventCategory: 'Dashboard - Instant Activations Welcome',
});

export const trackGoToActivationFromError = () => {
  iaWelcomeTrack({
    eventAction: 'Go To - L1 Form',
    eventLabel: 'From error banner',
  });
};

export const trackActivateAccount = () => {
  iaWelcomeTrack({
    eventAction: 'Click - Activate Account',
  });
};

export const trackTryDashboard = () => {
  iaWelcomeTrack({
    eventAction: 'Click - Try out the dashboard',
  });
};

export const trackIAClose = () => {
  iaWelcomeTrack({
    eventAction: 'Click - Close button',
  });
};

const iaActivationsTrack = setTrackData({
  eventCategory: 'Dashboard - Instant Activations Activate Account',
});

export const iaActivations = {
  trackGoToDashboard() {
    iaActivationsTrack({
      eventAction: 'Click - Go to Dashboard',
    });
  },
  trackClose(businessType) {
    iaActivationsTrack({
      eventAction: 'Click - Close',
      eventLabel: businessType,
    });
  },
  trackGiveKYCDetails() {
    iaActivationsTrack({
      eventAction: 'Click - Give details',
    });
  },
  trackCloseKYCDetails() {
    iaActivationsTrack({
      eventAction: 'Click - Give details close',
    });
  },
};

const supportDetailsTrack = setTrackData({
  eventCategory: 'Dashboard - Support Detail Data Collection',
});

export const trackSupportDetailPopupClose = () => {
  supportDetailsTrack({
    eventAction: 'Click - Close button',
    eventLabel: 'Support detail popup _cancel',
  });
};

export const trackSupportDetailSubmitAction = (value) => {
  supportDetailsTrack({
    eventAction: 'Click - Submit button',
    eventLabel: 'Support detail popup_submit',
    eventValue: {
      Email_Filled: value.email,
      Support_Url_Filled: value.url,
      Phone_Filled: value.phone,
    },
  });
};

export const trackSupportDetailPopupDisplay = () => {
  supportDetailsTrack({
    eventAction: 'Displayed',
    eventLabel: 'Support detail popup_displayed',
  });
};

export const selfServeSettleTracking = () =>
  selfServeTrackInitiate({
    selfServeAction: 'Settle Now Requested',
    page: 'Home',
    screen: 'Home',
  });

export const EVENT_CATEGORY_DASHBOARD_HOME = 'Dashboard - Home';
