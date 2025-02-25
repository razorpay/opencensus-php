
import { getCookie } from './cookies';

/**
 * Utility function to log out user's Google account from Razorpay.
 * Only for users who logged in via Google.
 */
export const logoutGoogleAccount = (): void => {
  const loginMode = getCookie('loginMode');
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

