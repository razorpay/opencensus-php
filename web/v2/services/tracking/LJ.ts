let utm = null;
let gclid = null;
let browser_details = {};

if (typeof window.razorpayAnalytics !== 'undefined') {
  utm = window.razorpayAnalytics.utils.getLandingParams();
  gclid = window.razorpayAnalytics.utils.getCookie('gclid');
  if (typeof window.razorpayAnalytics.utils.getBrowserDetails !== 'undefined') {
    browser_details = window.razorpayAnalytics.utils.getBrowserDetails();
  }
}

const commonProperties = {
  utm_params: utm,
  gclid,
  mode: 'live',
  reffering_url: document.referrer,
  url: document.location.href,
  session_id: window.session_id,
};

Object.assign(commonProperties, browser_details);

interface EventData {
  eventGroup: string;
  eventAction: string;
  eventContext: unknown;
  eventName: string;
}

export default function pushEvents({
  eventGroup,
  eventAction,
  eventContext = {},
  eventName,
}: EventData): void {
  let properties = { ...commonProperties };
  if (typeof eventContext === 'object') {
    properties = { ...commonProperties, ...eventContext };
  }
  const eventQ = window.rzpQ[eventGroup]();
  switch (eventAction) {
    case 'initiated':
      eventQ.initiated(eventName);
      break;
    case 'success':
      eventQ.success(eventName);
      break;
    case 'failed':
      eventQ.failed(eventName);
      break;
    case 'dropped':
      eventQ.dropped(eventName);
      break;
    default:
      break;
  }
  window.rzpQ.push(properties);
}
