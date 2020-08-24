import { addDecorator } from '@storybook/react';
import { withInfo } from '@storybook/addon-info';
import { addParameters } from '@storybook/client-api';
import { INITIAL_VIEWPORTS } from '@storybook/addon-viewport';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade/src/tokens/theme.web';
import React from 'react';
if (typeof global.process === 'undefined') {
  const { worker } = require('../mocks/browser');
  worker.start();
  addDecorator(
    withInfo({
      styles: {
        header: {
          h1: {
            marginRight: '20px',
            fontSize: '16px',
            display: 'inline',
          },
          body: {
            paddingTop: 0,
            paddingBottom: 0,
          },
          h2: {
            fontSize: '14px',
            display: 'inline',
            color: '#999',
          },
        },
        infoBody: {
          backgroundColor: '#eee',
          padding: '0px 5px',
          lineHeight: '2',
        },
      },
      inline: true,
      source: true,
    }),
  );
}

addParameters({
  viewport: {
    viewports: INITIAL_VIEWPORTS, // newViewports would be an ViewportMap. (see below for examples)
    defaultViewport: 'galaxys5',
  },
});

addDecorator((storyFn) => <ThemeProvider theme={theme}>{storyFn()}</ThemeProvider>);

//export const decorators = [addDecorator];
