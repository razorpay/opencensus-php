import React from 'react';

import { D2C_WIDGET_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { renderWithSuspense, screen, userEvent, waitFor } from 'test-utils';

import Pitch from '../Pitch';

const components = D2C_WIDGET_MOCK_RESPONSE.components[0].components;

const renderApp = () => renderWithSuspense(<Pitch components={components} />);

describe('Pitch', () => {
  it('should render the pitch component', async () => {
    renderApp();
    await waitFor(() => {
      const pitchComponent = screen.getByTestId('d2c-pitch-component');
      expect(pitchComponent).toBeInTheDocument();
    });
  });

  it('should render the problem content of the pitch component initially', async () => {
    renderApp();
    await waitFor(() => {
      const problemContentDisplayText = components[0].components[1].data.cross_sell_widget_data
        ?.text_content.display as string;
      expect(screen.getByText(problemContentDisplayText)).toBeInTheDocument();
      expect(screen.getByText(problemContentDisplayText)).toBeVisible();

      const solutionContentDisplayText = components[1].components[1].data.cross_sell_widget_data
        ?.text_content.display as string;
      expect(screen.getByText(solutionContentDisplayText)).toBeInTheDocument();
      expect(screen.getByText(solutionContentDisplayText)).not.toBeVisible();
    });
  });

  it('should render the solution content of the pitch component when clicked on problem content cta', async () => {
    renderApp();
    const problemContentCta =
      components[0].components[1].data.cross_sell_widget_data?.actions?.title;

    userEvent.click(screen.getByRole('button', { name: problemContentCta }));
    await waitFor(() => {
      const problemContentDisplayText = components[0].components[1].data.cross_sell_widget_data
        ?.text_content.display as string;
      expect(screen.getByText(problemContentDisplayText)).toBeInTheDocument();
      expect(screen.getByText(problemContentDisplayText)).not.toBeVisible();

      const solutionContentDisplayText = components[1].components[1].data.cross_sell_widget_data
        ?.text_content.display as string;
      expect(screen.getByText(solutionContentDisplayText)).toBeInTheDocument();
      expect(screen.getByText(solutionContentDisplayText)).toBeVisible();
    });
  });

  it.skip('should redirect to the correct url when clicked on solution content cta', async () => {
    const openSpy = jest.spyOn(window, 'open').mockImplementation(() => null);

    renderApp();

    const problemContentCta =
      components[0].components[1].data.cross_sell_widget_data?.actions?.title;

    userEvent.click(screen.getByRole('button', { name: problemContentCta }));

    await waitFor(() => {
      const solutionContentDisplayText = components[1].components[1].data.cross_sell_widget_data
        ?.text_content.display as string;
      expect(screen.getByText(solutionContentDisplayText)).toBeInTheDocument();
      expect(screen.getByText(solutionContentDisplayText)).toBeVisible();

      const solutionContentCta =
        components[1].components[1].data.cross_sell_widget_data?.actions?.title;

      userEvent.click(screen.getByRole('button', { name: solutionContentCta }));

      const redirectUrl = components[1].components[1].data.cross_sell_widget_data?.actions?.action;
      expect(openSpy).toHaveBeenCalledWith(redirectUrl, '_blank');
    });

    openSpy.mockRestore();
  });
});
