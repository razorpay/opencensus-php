import React from 'react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen, waitFor, server } from 'test-utils';

import PreAndPostMagic from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic';

import { preAndPostMagicRTORateHandlers } from 'merchant/views/MagicCheckout/RTOAnalytics/__tests__/mocks/handler';

import { NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

jest.mock('react-chartjs-2', () => ({
  Line: () => <div data-testid="mocked-line-chart" />,
}));

const INIT_STATE = {
  magicRTOAnalytics: {
    pre_vs_post_magic_rto_rate: {
      loading: false,
      data: null,
      updatedAt: null,
    },
    timedWidgetsFetching: false,
  },
};

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <PreAndPostMagic {...props} />
    </Provider>,
  );
};

describe('testing pre and post magic RTO rate widget', () => {
  test('should not any graph incase data is not available', async () => {
    server.use(preAndPostMagicRTORateHandlers[0]);

    renderApp();
    await waitFor(() => {
      const OverlayContainer = document.getElementsByClassName('overlay-content')[0];
      expect(OverlayContainer).toHaveTextContent(NO_GRAPH_DATA.postMagicSubtitle);
    });
  });

  test('should show graph if data is available', async () => {
    server.use(preAndPostMagicRTORateHandlers[1]);

    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/^Reduction in RTO rate post Magic.?/i)).toBeInTheDocument();
    });
  });
});
