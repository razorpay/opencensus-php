
PORT={{ appPort }}

UNIVERSE_PUBLIC_ASSETS_URL=https://dashboard-assets.razorpay.com/dashboard/core-bundles/{{ appName }}/

{{ integratedAppName }}_SENTRY_PROJECT={{ integratedAppSentryConfig.project.value }}
{{ integratedAppName }}_SENTRY_DSN={{ integratedAppSentryConfig.dsn.value }}
