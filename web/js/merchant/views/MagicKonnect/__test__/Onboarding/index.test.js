import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { getMagicKonnectSlideDetails } from '../../constants';

import MagicKonnectOnboarding from 'merchant/views/MagicKonnect/Onboarding';

const INIT_PROPS = {
  onClickNextCtaAction: jest.fn(),
  slideLabels: getMagicKonnectSlideDetails(''),
  isLoading: false,
  primaryCta: 'Login to Magic Konnect',
  secondaryCta: '',
  isExistingUser: true,
};

jest.mock('common/new-ui/Slider', () => ({
  ...jest.requireActual('common/new-ui/Slider'),
  SliderDots: (props) => {
    const { goTo } = props;
    return (
      <button type="button" onClick={() => goTo(1)}>
        Click me
      </button>
    );
  },
}));

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <MagicKonnectOnboarding {...INIT_PROPS} {...props} />
    </Provider>,
  );
};

describe('Magic konnect onboarding', () => {
  test('first slide should be visible', () => {
    renderApp();
    expect(screen.getByText('Login to Magic Konnect')).toBeInTheDocument();
  });

  test('should show second slide if dot is clicked', async () => {
    renderApp();
    const cta = screen.getByRole('button', {
      name: 'Click me',
    });

    await userEvent.click(cta);

    expect(screen.getByText('Native WhatsApp Payments')).toBeInTheDocument();
  });

  test('should be able to navigate back and forth', async () => {
    renderApp();

    const readMoreCta = screen.getByRole('button', {
      name: 'Read more',
    });

    await userEvent.click(readMoreCta);

    const backCta = screen.getByTestId('back-cta');

    await userEvent.click(backCta);

    expect(screen.getByText('Login to Magic Konnect')).toBeInTheDocument();
  });
});
