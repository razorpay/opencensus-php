import React from 'react';

import { D2C_WIDGET_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { renderWithSuspense, screen, waitFor } from 'test-utils';

import PostPitch from '../PostPitch';

const components = D2C_WIDGET_MOCK_RESPONSE.components[1].components;

const renderApp = () => renderWithSuspense(<PostPitch components={components} />);

describe('Pitch', () => {
  it('should render the post pitch component', async () => {
    renderApp();
    await waitFor(() => {
      const pitchComponent = screen.getByTestId('d2c-post-pitch-component');
      expect(pitchComponent).toBeInTheDocument();
    });
  });
});
