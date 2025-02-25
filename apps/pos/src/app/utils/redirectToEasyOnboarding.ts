const redirectToEasyOnboarding = (replace = false): void => {
  const url = window.EASY_ONBOARDING_URL;
  replace ? window.location.replace(url) : window.location.assign(url);
};

export default redirectToEasyOnboarding;
