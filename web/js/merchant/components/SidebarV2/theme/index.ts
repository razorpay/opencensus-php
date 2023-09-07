import { overrideTheme, paymentTheme } from '@razorpay/blade/tokens';

const sidebarTheme = overrideTheme({
  baseThemeTokens: paymentTheme,
  overrides: {
    colors: {
      onLight: {
        surface: {
          text: {
            normal: {
              lowContrast: '#b4b6bc',
            },
          },
        },
      },
    },
  },
});

export default sidebarTheme;
