const i18nLinterReport = require('./.github/danger/i18nLinterCheck');

const stepValue = process.argv[2];

if (stepValue) {
  i18nLinterReport({
    step: stepValue,
  });
} else {
  console.error(
    `Required arguments are not available . Currently arguments coming as ${stepValue}. Please send the "pr or master" to ensure the dangerjs to run at required step`,
  );
}
