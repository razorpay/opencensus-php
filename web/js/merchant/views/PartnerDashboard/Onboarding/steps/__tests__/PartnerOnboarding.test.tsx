import React from 'react';
import { render, screen, userEvent } from 'common/services/test/test-utils';
import '@testing-library/jest-dom/extend-expect';
import PartnerOnboarding from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import { getUser } from 'merchant/store';

// TODO : covered only conditional steps case, have to cover others later

const userData = getUser();

const location = {
  search: '',
  pathname: '/app/partners',
};

describe('PartnerOnboarding', () => {
  beforeAll(() => {
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });

  const renderApp = ({ isOnboardAsResellers }) => {
    return render(
      <div className="partner-onboarding-base-screen new-screen">
        <PartnerOnboarding
          disableClose={false}
          location={location}
          tracking={{
            trackEvent: () => {},
          }}
        />
      </div>,
      {
        initialState: {
          session: {
            org: {
              custom_code: 'rzp',
            },
            user: {
              ...userData,
              role: 'owner',
              isUnregisteredBusiness: true,
              isOrgCurlec: false,
              findTag: () => false,
              isOnboardAsResellers,
            },
          },
        },
      },
    );
  };

  test('Should not render partner type screen for isOnboardAsResellers = true', async () => {
    renderApp({ isOnboardAsResellers: true });
    const nextButton = screen.getByRole('button', { name: 'Next' });
    expect(nextButton).toBeInTheDocument();
    // click next
    await userEvent.click(nextButton);

    const getStartedButton = screen.getByRole('button', { name: 'Get Started' });
    expect(getStartedButton).toBeInTheDocument();

    expect(screen.queryByTestId('tnc-footer')).not.toBeNull();
  });

});
