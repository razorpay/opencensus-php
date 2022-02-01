import analyticsService from '@razorpay/commander-services/analytics';
import _refiner from 'refiner-js';
import { initAnalytics } from 'common/utils/analytics';
import moment from 'moment';
import { merchantFetch } from 'merchant/utils/ajax';

export const initSegment = (app, user, callback) => {
  // eslint-disable-next-line consistent-return
  initAnalytics().then(() => {
    window.segment_loaded = true;
    if (window.analytics?.identify) {
      const mode = localStorage.getItem(`rzp_mode--${user.id}`);
      const kycStatus = user.activated ? 'activated' : 'not activated';
      const activatedAt = user.activated_at;

      const segmentIdentiyCall = (props) =>
        window.analytics.identify(user?.user?.id, {
          id: user?.user?.id,
          userId: user?.user?.id,
          emailId: user?.email,
          email: user?.email,
          activatedAt: moment.unix(activatedAt),
          mode,
          userRole: user?.role,
          name: user?.name,
          kycStatus,
          merchantId: user?.current,
          businessCategory: user?.businessCategory,
          phone: `+91${user?.contact_mobile}`,
          app,
          ...props,
        });

      let dataFromAPI = {};
      return (
        window.rzp_user &&
        merchantFetch('merchant/data_for_segment')
          .then((res) => {
            if (res.data) {
              dataFromAPI = res.data;
              if (callback) {
                callback(res.data);
              }
            }
            segmentIdentiyCall(dataFromAPI);
            updateWhatAppOptinIdentity(user);
          })
          .catch(() => segmentIdentiyCall(dataFromAPI))
      );
    }
  });
};

async function updateWhatAppOptinIdentity(user) {
  const whatsappOptStatus = await fetchWhatsappOptin();
  window.analytics.identify(user?.user?.id, {
    'MSG-whatsapp': whatsappOptStatus,
  });
}
async function fetchWhatsappOptin(data = { source: 'pg.onboarding.presignup' }) {
  try {
    const response = await merchantFetch({
      url: `users/whatsapp/opt_in_status`,
      method: 'get',
      data,
    });
    if (response && response.data && response.data.consent_status)
      return response.data.consent_status;
    else return false;
  } catch (error) {
    return false;
  }
}

export const initLumberjack = () => {
  analyticsService.init({
    lumberjackAppName: 'pg-dashboard',
    lumberjackApiKey: window.LUMBERJACK_API_KEY,
    lumberjackApiUrl: window.LUMBERJACK_API_URL,
  });
};

export const initRefiner = (user) => {
  if (window.REFINER_PROJECT_ID && user && user.user) {
    _refiner('setProject', window.REFINER_PROJECT_ID);
    _refiner('identifyUser', {
      id: user.user?.id,
      merchant_id: user.current,
      created_at: user.user?.created_at,
    });
  }
};
