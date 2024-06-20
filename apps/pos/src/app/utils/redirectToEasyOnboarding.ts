declare global {
  interface Window {
    EASY_ONBOARDING_URL: string;
  }
}

const redirectToEasyOnboarding = (): void => {
  const url = window.EASY_ONBOARDING_URL;
  window.location.assign(url);
};

export default redirectToEasyOnboarding;
