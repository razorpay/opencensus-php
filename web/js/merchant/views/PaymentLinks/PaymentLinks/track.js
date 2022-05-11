import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';

const FAILED = 'FAILED';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(eventName, event, data) {
    if (event === FAILED) {
      lumberjackTrack(
        window.rzpQ.paymentLinks().failed(eventName, {
          data,
        }),
      );
      return;
    }
    // default case: success
    lumberjackTrack(
      window.rzpQ.paymentLinks().success(eventName, {
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, screen, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        origin: 'dashboard',
      },
    });
  }

  return {
    onNeedHelp: (featureName) => {
      sendToLumberjack(`${featureName}.need_help.clicked`);
      sendToSegment(`${titleCase(featureName)} need help`, 'clicked', `payment link`);
    },

    onDocumentClick: (featureName) => {
      sendToLumberjack(`${featureName}.documentation.clicked`);
      sendToSegment(`${titleCase(featureName)} doucmentation`, 'clicked', `payment link`);
    },

    onReminderSettingClick: (featureName) => {
      sendToLumberjack(`${featureName}.reminder_setting.clicked`);
      sendToSegment(`${titleCase(featureName)} reminder setting`, 'clicked', `payment link`);
    },

    onShareLinkSuccess: () => {
      const properties = {
        mweb: true,
      };
      sendToLumberjack(`pl.share_link.success.clicked`, properties);
      sendToSegment(`pl share link success`, 'clicked', `payment link`, properties);
    },

    searchStatus: (params) => {
      const prop = {
        params,
      };
      sendToSegment(`pl search status`, 'clicked', `payment link`, prop);
    },

    searchSubmit: () => {
      sendToLumberjack(`pl.search.submit`);
      sendToSegment(`pl search submit`, 'clicked', `payment link`);
    },

    searchCurrency: () => {
      sendToLumberjack(`pl.search.currency`);
      sendToSegment(`pl search currency`, 'clicked', `payment link`);
    },

    searchCount: () => {
      sendToLumberjack(`pl.search.count`);
      sendToSegment(`pl search count`, 'clicked', `payment link`);
    },

    clearSearch: () => {
      sendToLumberjack(`pl.search.clear`);
      sendToSegment(`pl search clear`, 'clicked', `payment link`);
    },

    paginate: (type, page) => {
      const prop = {
        page,
        type,
      };
      sendToLumberjack(`pl.browse.${type}`, prop);
      sendToSegment(`pl browse ${type}`, 'clicked', `payment link`, prop);
    },

    searchError: (response) => {
      const prop = {
        response,
      };
      sendToLumberjack(`pl.search.error`, prop);
      sendToSegment(`pl search error`, 'clicked', `payment link`, prop);
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
