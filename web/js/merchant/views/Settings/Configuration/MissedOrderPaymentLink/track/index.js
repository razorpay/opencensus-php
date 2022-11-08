function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, data = {}) {
    lumberjackTrack(
      window.rzpQ &&
        window.rzpQ.merchantActions().initiated(`dashboard.settings.mopl.${event}`, { data }),
    );
  }

  return {
    getStarted: () => {
      sendToLumberjack('get_started');
    },

    viewInsights: () => {
      sendToLumberjack('view_insights');
    },

    manageSettings: () => {
      sendToLumberjack('manage_setting');
    },

    cancel: {
      deactivation: () => {
        sendToLumberjack('cancel_plan_confirm', { status: 'success' });
      },
    },

    insights: {
      conversionChannel: (eventName, channel) => {
        sendToLumberjack(eventName, channel);
      },
    },

    plans: {
      planDetails: (type) => {
        sendToLumberjack('plan_details', { type, hover: 'yes' });
      },
      viewAllFeatures: (type) => {
        sendToLumberjack('view_all_features', { type });
      },
      selectPlan: (type) => {
        sendToLumberjack('select_plan', { type });
      },
    },

    planSelection: {
      confirmPlan: (name) => {
        sendToLumberjack('confirm_plan', { type: name });
      },
      goBack: (name) => {
        sendToLumberjack('go_back', { type: name });
      },
      termsAndConditions: (name) => {
        sendToLumberjack('terms_conditions', { type: name });
      },
    },

    settings: {
      cancelPlan: () => {
        sendToLumberjack('cancel_plan');
      },
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
