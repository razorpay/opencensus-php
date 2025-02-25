import toTitleCase from '../utils/titleCase';
import readCookie from '../utils/readCookie';
import { signUpSrc } from '../screens/screenHelpers';

export const VERSION_ONE_TAP_ENABLED = '2.2';
export const VERSION_WEBSITE_HOMEPAGE = '2.3';
export const VERSION_MOBILE_SIGNUP = '3.0';
export const DESKTOP_SCREEN_PX = 769;

export const GTAG_KEYS = {
  signupStart: 'AW-928471290/3WVnCJWR4PABEPqx3boD',
  defaultSignupComplete: 'AW-928471290/9KxkCP-1vIYBEPqx3boD',
  signupCompleteReg: 'AW-928471290/IttWCMz07OEBEPqx3boD',
  signupCompleteUnreg: 'AW-928471290/R8JfCOLV-OEBEPqx3boD',
  marketingCreateAccountSuccess: 'DC-11482329/plsign/signu0+unique',
  marketingSignupComplete: 'DC-11482329/signup/signu0+unique',
};

const stageKeys = {
  lumberjackApiKey: '6z9XZ6qi4zo6lBu082ptIMAHg6XlOHJS',
  lumberjackApiUrl: 'https://lumberjack.stage.razorpay.in/v1/track',
  lumberjackPageMetricUrl: 'https://lumberjack-metrics.stage.razorpay.in/v1/frontend-metrics',
  segmentApiKey: 'STNWyMUNH6L1Tzn4CBqOAlAuIbHzorJ4',
};

const productionKeys = {
  lumberjackApiKey: 'JjAL32wgY1QDhrSMApGOF7H5L67VTmEh',
  lumberjackApiUrl: 'https://lumberjack.razorpay.com/v1/track',
  lumberjackPageMetricUrl: 'https://lumberjack-metrics.razorpay.com/v1/frontend-metrics',
  segmentApiKey: 'genp9GSNtEPNdNgfWyKWaqUXb0Eaw9qp',
};

export const SEGMENT_KEYS = {
  lumberjackApiKey:
    SHIELD_STAGE === 'production' ? productionKeys.lumberjackApiKey : stageKeys.lumberjackApiKey,
  lumberjackApiUrl:
    SHIELD_STAGE === 'production' ? productionKeys.lumberjackApiUrl : stageKeys.lumberjackApiUrl,
  lumberjackPageMetricUrl:
    SHIELD_STAGE === 'production'
      ? productionKeys.lumberjackPageMetricUrl
      : stageKeys.lumberjackPageMetricUrl,
  segmentApiKey:
    SHIELD_STAGE === 'production' ? productionKeys.segmentApiKey : stageKeys.segmentApiKey,
};

// Used in data lake to segregate events by version
let signUpAnalyticsVersion = VERSION_ONE_TAP_ENABLED; // default version 2.2 represents google onetap version
export const setSignUpAnalyticsVersion = (v) => {
  signUpAnalyticsVersion = v;
};

let signUpSource = 'dashboard';
export const setSignUpSourceAnalytics = (source) => {
  signUpSource = source;
};

let signupCTASource = '';
export const setSignupCTASource = (ctaSource) => {
  signupCTASource = ctaSource;
};

let preSignupDisabled = false;
export const setIsRemovePreSignUpExperimentEnabled = (removePreSignupExperiment) => {
  preSignupDisabled = removePreSignupExperiment;
};

/**
 * Transform the events array to an object with metrics as key having array value
 * @returns {[]}
 * For e.g. (Remove after understanding)
 * Input: [{"name":"page.metrics","labels":[{"metric":"fe_api_latency","route":"/user","connectionType":"4g","time":10093.8}]}]}
 * Output: {"metrics":[{"name":"page.metrics","labels":[{"metric":"fe_api_latency","route":"/user","connectionType":"4g","time":10093.8}]}]}
 */
const transformMetrics = (events) => {
  let mergedEvents = [];

  events.forEach((event) => {
    let eventAdded = false;

    mergedEvents = mergedEvents.map((mergeEvent) => {
      if (mergeEvent.name === event.name) {
        mergeEvent.labels = mergeEvent.labels.concat(event.labels);
        eventAdded = true;
      }
      return mergeEvent;
    });

    if (!eventAdded) {
      mergedEvents.push(event);
    }
  });

  return mergedEvents;
};

function sendPageMetric(properties) {
  try {
    const userRequestedData = {
      type: 'metrics',
      properties: {
        name: 'page.metrics',
        labels: [
          {
            ...properties,
          },
        ],
      },
    };

    const transformedData = {
      metrics: transformMetrics([userRequestedData.properties]),
    };
    const base64Data = btoa(JSON.stringify(transformedData));

    const blob = new Blob(
      [
        JSON.stringify({
          key: SEGMENT_KEYS.lumberjackApiKey,
          data: base64Data,
        }),
      ],
      { 'content-type': 'application/json' },
    );

    navigator.sendBeacon(SEGMENT_KEYS.lumberjackPageMetricUrl, blob);
  } catch (err) {
    // Not part of core functionality so ignoring error silently
    // eslint-disable-next-line no-console
    console.error('[sendPageMetric]:', err);
  }
}

function getTrackingEvents() {
  const ga = (eventCategory, eventAction, eventLabel, eventValue, param) => {
    if (!window.rzpAnalytics) return;
    if (param) {
      window.ga(eventCategory, eventAction, eventLabel, eventValue, param);
      return;
    }
    window.rzpAnalytics({
      eventCategory,
      eventAction,
      eventLabel,
      eventValue,
    });
  };

  const prometheus = ({
    type,
    label,
    properties,
    isPageMetrics,
    errorDescription,
    isShieldEvent = false,
  }) => {
    if (!window.rzpQMetrics) return;

    window.rzpQMetrics.immediate = true;

    if (isPageMetrics) {
      sendPageMetric(properties);
    } else {
      window.rzpQMetrics.push({
        type: 'metrics',
        properties: {
          name: 'device.metrics',
          labels: [
            {
              type: `${isShieldEvent ? '' : 'dashboard_'}${type}`,
              source: label,
              route: errorDescription,
              ...properties,
            },
          ],
        },
      });
    }

    window.rzpQMetrics.immediate = false;
  };

  const hubspot = (data) => {
    if (!window.trackHubs) return;
    window.trackHubs(data);
  };

  /**
   *
   * @param {string} conversionCode
   * @param {Record<string, any>} gtagProps
   * @returns {void}
   */
  const gtag = (conversionCode, gtagProps) => {
    if (SHIELD_STAGE !== 'production' || !window.gtag) return;

    window.gtag('event', 'conversion', {
      ...gtagProps,
      send_to: conversionCode,
    });
  };

  // Criteo pixel signup event
  const criteo = ({ type, email }) => {
    window.criteo_q = window.criteo_q || [];
    if (type === 'view') {
      const deviceType = /iPad/.test(navigator.userAgent)
        ? 't'
        : /Mobile|iP(hone|od)| Android|BlackBerry|IEMobile|Silk/.test(navigator.userAgent)
        ? 'm'
        : 'd';
      window.criteo_q.push(
        { event: 'setAccount', account: 85314 },
        { event: 'setSiteType', type: deviceType },
        { event: 'viewItem', extra_data: 'Signup', item: '3' },
      );
    } else if (type === 'signup_complete') {
      window.criteo_q.push(
        { event: 'setEmail', email },
        { event: 'trackTransaction', id: '', item: [{ id: 1, price: 1, quantity: 1 }] },
      );
    }
  };

  /**
   * Invoke Bing for conversion tracking.
   */
  const bing = () => {
    if (SHIELD_STAGE !== 'production') return;

    window.uetq = window.uetq || [];
    window.uetq.push({
      ec: 'bing',
      ea: 'click',
      el: 'connecttobing',
      ev: 1,
    });
  };

  const social = (eventData) => {
    if (!window.rzpAnalytics) return;

    Object.keys(eventData).forEach((type) => {
      switch (type) {
        case 'fb':
          window.rzpAnalytics({
            name: 'facebook',
            event: eventData[type],
          });
          break;
        case 'quora':
          window.rzpAnalytics({
            name: 'quora',
            event: eventData[type],
          });
          break;
        case 'reddit':
          window.rzpAnalytics({
            name: 'reddit',
            event: eventData[type],
          });
          break;
        case 'linkedIn':
          window.rzpAnalytics({
            name: 'linkedIn',
            value: {
              conversionId: eventData[type],
            },
          });
          break;
        case 'twitter':
          window.rzpAnalytics({
            name: 'twitter',
            value: {
              txn_id: eventData[type],
            },
          });
          break;
        case 'twitterAgency':
          window.rzpAnalytics({
            name: 'twitterAgency',
            value: {
              txn_id: eventData[type],
            },
          });
          break;
        default:
          window.rzpAnalytics({
            name: 'facebook',
            event: eventData[type],
          });
      }
    });
  };

  const segment = ({ objectName, actionName, screen, properties = {}, toCleverTap = false }) => {
    if (!objectName) {
      throw new Error('[analytics]: objectName cannot be empty');
    }

    if (!actionName) {
      throw new Error('[analytics]: actionName cannot be empty');
    }

    if (!screen) {
      throw new Error('[analytics]: screen cannot be empty');
    }

    if (/_/g.test(objectName)) {
      throw new Error(`[analytics]: expected objectName: ${objectName} to not have '_'`);
    }

    const eventTimestamp = new Date().toISOString();
    const isMobile = window.innerWidth < DESKTOP_SCREEN_PX;
    const experiments = [
      signUpSource === signUpSrc.websitePaymentLink ? 'Signup_experiment_1' : 'none',
      preSignupDisabled ? 'Lead questions removed' : 'Lead questions added',
    ];

    const segmentProperties = {
      pageUrl: window.location.href,
      device_type: isMobile ? 'mweb' : 'dweb',
      experiment_ID: experiments,
      source: isMobile ? 'Mobile Dashboard' : 'Dashboard',
      slug: window.location.origin + window.location.pathname,
      mode: 'null',
      userRole: 'null',
      userId: 'null',
      merchantId: 'null',
      ...properties,
    };

    const eventName = toTitleCase(`${objectName} ${actionName}`);
    if (window.analytics) {
      // Check if Segment Analytics is present
      window.analytics.track(
        eventName,
        {
          ...segmentProperties,
          screen,
          eventTimestamp,
        },
        // Added flag to segment to send events to cleverTap since cleverTap has limit of 512 events.
        // segment sends events to the clevertap ( connection made on segment dashboard )
        // clevertap is used to send drip emails to the users
        {
          integrations: {
            CleverTap: toCleverTap,
          },
        },
      );
    }
  };

  /* Pass our backend userid to segment so that
     it can be mapped to their own userId property which
     can help in stitching all events to our unique value(userid)
  */
  const segmentIdentify = (id, properties) => {
    if (window.analytics) {
      window.analytics.identify(id, properties);
    }
  };

  // send events to datalake and segment by default
  const dataLake = ({
    type,
    eventName,
    data,
    prefix = 'signup',
    version = signUpAnalyticsVersion,
    isPushToSegment = true,
    toCleverTap = false,
  }) => {
    if (!window.rzpQ) return;

    /* The attributed utm property as per GA's logic.
       This UTM property will tell to which campaign
       the user is attributed to.
       The logic is written in static repo.
       https://github.com/razorpay/static/blob/master/src/analytics/js/getAttributedUtms.js
    */
    let attribUtm = null;

    try {
      attribUtm = JSON.parse(readCookie('lastAttribUtm'));
    } catch (e) {
      throw new Error('[analytics]: error parsing lastAttribUtm cookie');
    }
    const experiments = [
      signUpSource === signUpSrc.websitePaymentLink ? 'Signup_experiment_1' : 'none',
      preSignupDisabled ? 'Lead questions removed' : 'Lead questions added',
    ];

    const eventData = {
      mode: 'live',
      version,
      attrib_utm: attribUtm,
      experiment_ID: experiments,
      signup_cta_source: signupCTASource,
      app_host: signUpSource,
      ...data,
    };

    const segmentsEventName = `${eventName}_${type}`;

    if (isPushToSegment) {
      // push event to segment as well by simply replacing underscore with spaces
      // Added flag to segment to send events to cleverTap since cleverTap has limit of 512 events.
      // segment sends events to the clevertap ( connection made on segment dashboard )
      segment({
        objectName: prefix,
        actionName: segmentsEventName.split('_').join(' '),
        screen: prefix,
        properties: eventData,
        toCleverTap,
      });
    }

    // adding prefix before pushing to lake
    eventName = `${prefix}.${eventName}`;

    switch (type) {
      case 'success':
        // prettier-ignore
        window.rzpQ.push(
          // prettier-ignore
          window.rzpQ
            .now()
            .onbr()
            .success(eventName, eventData),
        );
        break;
      case 'failed':
        // prettier-ignore
        window.rzpQ.push(
          // prettier-ignore
          window.rzpQ
            .now()
            .onbr()
            .failed(eventName, eventData),
        );
        break;
      default:
        // prettier-ignore
        window.rzpQ.push(
          // prettier-ignore
          window.rzpQ
            .now()
            .onbr()
            .initiated(eventName, eventData),
        );
    }
  };

  return {
    ga,
    prometheus,
    dataLake,
    hubspot,
    gtag,
    bing,
    social,
    segment,
    segmentIdentify,
    criteo,
  };
}

const trackEvents = getTrackingEvents();

export const trackStepsAndNext = (stepName, sourceLabel) => {
  trackEvents.ga('Signup - Steps', `Step - ${stepName}`, sourceLabel);
};

export default trackEvents;
