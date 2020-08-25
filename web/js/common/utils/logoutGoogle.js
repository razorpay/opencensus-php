/**
 * Utility function to logout user's google account from Razorpay
 * Only for users who logined via google
 */
import { getCookie } from './cookies';

const logoutGoogleAccount = () => {
  let loginMode = getCookie('loginMode');
  if (loginMode === 'google') {
    let googleAuthInstance = window.gapi.auth2.getAuthInstance();
    if (!googleAuthInstance) {
      window.gapi.load('auth2', () => {
        window.gapi.auth2
          .init({
            client_id: window.OAUTH_CLIENT_ID,
          })
          .then(() => {
            googleAuthInstance = window.gapi.auth2.getAuthInstance();
            googleAuthInstance.signOut();
            googleAuthInstance.disconnect();
          });
      });
    } else {
      googleAuthInstance.signOut();
      googleAuthInstance.disconnect();
    }
  }
};

export default logoutGoogleAccount;
