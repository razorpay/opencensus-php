import AccountDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v1';
import { render, screen, waitFor, userEvent, getByTestId, server } from 'test-utils';
import { titleCase } from 'common/utils/rzp-utils';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import * as analytics from 'common/utils/analytics';
import * as selfServeTrack from 'common/utils/selfServeAnalytics';
import {
  updateMerchantConfigHandler,
  updateMerchantConfigErrorHandler,
} from 'merchant/views/AccountAndSettings/BusinessSettings/__test__/fixtures/handlers';
import User from 'merchant/models/User';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { storeWithInitialState } from 'merchant/store';
import { testNewStylesUsingFlowRevamped } from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import 'jest-location-mock';
import { Modules } from 'common/constant/enums';

jest.mock('merchant/views/Account/Profile/components/UserContactMobile', () => ({
  __esModule: true,
  default: () => <>UserContactMobile</>,
}));

jest.mock('common/ui/Popover', () => ({
  __esModule: true,
  default: ({ children }) => <>{children}</>,
  PopoverBody: ({ children }) => <>{children}</>,
}));

jest.mock('merchant/views/Account/Profile/components/MerchantConfigForm', () => ({
  __esModule: true,
  default: ({ attribute, label, desc, value, updateMerchantConfig }) => (
    <div data-testid="merchant-config-form">
      <div data-testid="attribute">{attribute}</div>
      <div data-testid="label">{label}</div>
      <div data-testid="desc">{desc}</div>
      <div data-testid="value">{value}</div>
      <button
        type="button"
        onClick={() => updateMerchantConfig({ display_name: 'John updated display name' })}
      >
        Update
      </button>
    </div>
  ),
}));

const displayName = 'John display name';
const updatedDisplayName = 'John updated display name';
const defaultUserInfo = {
  contact_name: 'john Doe',
  email: 'jane.doe@razorpay.com',
};

let reduxStore;

const renderApp = ({ user, isFlowRevamped } = {}) => {
  reduxStore = storeWithInitialState({
    session: {
      user: new User({
        merchants: {
          test: {
            role: user?.isAdminOrOwner ? rolesList.OWNER : rolesList.MANAGER,
          },
          org: {},
        },
        current: 'test',
        ...defaultUserInfo,
        display_name: user?.displayName,
      }),
      org: {},
    },
  });
  return render(<AccountDetails isFlowRevamped={isFlowRevamped} />, {
    reduxStore,
    showModal: true,
  });
};

describe('Contact Details', () => {
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');
  const selfServeTrackInitiateMock = jest.spyOn(selfServeTrack, 'selfServeTrackInitiate');

  beforeEach(() => {
    analyticsTrackMock.mockClear();
    selfServeTrackInitiateMock.mockClear();
    window.location.assign.mockClear();
  });

  test('should render contact details', () => {
    renderApp();
    expect(screen.getByText('Contact Name')).toBeInTheDocument();
    expect(screen.queryByText(defaultUserInfo.contact_name)).not.toBeInTheDocument();
    expect(screen.getByText(titleCase(defaultUserInfo.contact_name))).toBeInTheDocument();
    expect(screen.queryByText('Display Name')).not.toBeInTheDocument();

    // Contact Email Section
    expect(screen.getByText('Contact Email')).toBeInTheDocument();
    const emailLink = screen.getByRole('link', { name: defaultUserInfo.email });
    expect(emailLink).toBeInTheDocument();
    expect(emailLink).toHaveAttribute('href', `mailto:${defaultUserInfo.email}`);

    expect(screen.getByText('UserContactMobile')).toBeInTheDocument();
  });

  describe('When user is admin or owner', () => {
    test('should show display name section', () => {
      renderApp({ user: { isAdminOrOwner: true } });
      expect(screen.getByText('Display Name')).toBeInTheDocument();
      expect(screen.getByText(ATTR_DETAILS.display_name.desc)).toBeInTheDocument();
    });

    describe('When user has display name', () => {
      beforeEach(async () => {
        renderApp({
          user: { isAdminOrOwner: true, displayName },
        });
        const editDisplayNameLink = screen.getByTestId('Edit Display Name');
        expect(editDisplayNameLink).toBeInTheDocument();
        expect(screen.queryByTestId('Set Display Name')).not.toBeInTheDocument();

        await userEvent.click(editDisplayNameLink);
      });

      test('should call analytics on clicking edit display name', async () => {
        await waitFor(() => {
          expect(analyticsTrackMock).toHaveBeenCalled();
          expect(analyticsTrackMock).toHaveBeenCalledWith({
            objectName: 'dispay name edit',
            actionName: 'clicked',
            screen: 'my account',
            properties: {
              action: 'reset',
            },
          });
        });

        expect(selfServeTrackInitiateMock).toHaveBeenCalled();
        expect(selfServeTrackInitiateMock).toHaveBeenCalledWith({
          selfServeAction: 'Display Name Updated',
          page: Modules.Profile,
          screen: Modules.MyAccount,
        });
      });

      test('should show merchant config modal on edit display name', async () => {
        const merchantConfigFormElement = await screen.findByTestId('merchant-config-form');
        expect(merchantConfigFormElement).toBeInTheDocument();
        const attr = 'display_name';
        const { label, desc } = ATTR_DETAILS[attr];
        [
          ({
            key: 'attribute',
            value: attr,
          },
          {
            key: 'label',
            value: label,
          },
          {
            key: 'desc',
            value: desc,
          },
          {
            key: 'value',
            value: displayName,
          }),
        ].forEach(({ key, value }) => {
          // as elements of merchant config form
          const element = getByTestId(merchantConfigFormElement, key);
          expect(element).toBeInTheDocument();
          expect(element).toHaveTextContent(value);
        });
        expect(screen.getByRole('button', { name: 'Update' })).toBeInTheDocument();
      });

      test('should show success notification and close modal on clicking update in show merchant config modal', async () => {
        server.use(updateMerchantConfigHandler());
        const updateButton = await screen.findByRole('button', { name: 'Update' });
        await userEvent.click(updateButton);

        await waitFor(() => {
          expect(analyticsTrackMock).toHaveBeenLastCalledWith({
            objectName: 'display name update',
            actionName: 'status',
            screen: 'my account',
            properties: {
              status: 'success',
              newDisplayName: updatedDisplayName,
            },
          });
        });

        expect(screen.getByText('Display name changed successfully.')).toBeInTheDocument();
        // new display name is updated in store
        expect(reduxStore.getState().session.user.display_name).toBe(updatedDisplayName);
        // modal is closed
        expect(screen.queryByTestId('merchant-config-form')).not.toBeInTheDocument();
      });

      test('should show error notification on clicking update in show merchant config modal', async () => {
        server.use(updateMerchantConfigErrorHandler());
        const updateButton = await screen.findByRole('button', { name: 'Update' });
        await userEvent.click(updateButton);

        await waitFor(() => {
          expect(analyticsTrackMock).toHaveBeenLastCalledWith({
            objectName: 'display name update',
            actionName: 'status',
            screen: 'my account',
            properties: {
              status: 'failure',
              newDisplayName: updatedDisplayName,
              failureReason: 'error in updating display name',
            },
          });
        });

        expect(screen.getByText('error in updating display name')).toBeInTheDocument();
      });
    });

    describe('When user does not have display name', () => {
      beforeEach(() => {
        renderApp({ user: { isAdminOrOwner: true } });
      });

      test('should show set display name link', () => {
        const setDisplayNameLink = screen.getByTestId('Set Display Name');
        expect(setDisplayNameLink).toBeInTheDocument();
        expect(screen.queryByTestId('Edit Display Name')).not.toBeInTheDocument();
      });

      test('should call analytics and open merchant config modal on clicking set display name', async () => {
        const setDisplayNameLink = screen.getByTestId('Set Display Name');
        await userEvent.click(setDisplayNameLink);

        expect(analyticsTrackMock).toHaveBeenCalled();
        expect(analyticsTrackMock).toHaveBeenCalledWith({
          objectName: 'display name edit',
          actionName: 'clicked',
          screen: 'my account',
          properties: {},
        });

        expect(screen.getByTestId('merchant-config-form')).toBeInTheDocument();
      });
    });
  });

  test('should call analytics on clicking contact email', async () => {
    renderApp();
    const emailLink = screen.getByRole('link', { name: defaultUserInfo.email });
    await userEvent.click(emailLink);

    expect(analyticsTrackMock).toHaveBeenCalled();
    expect(analyticsTrackMock).toHaveBeenCalledWith({
      objectName: 'contact email',
      actionName: 'clicked',
      screen: 'my account',
      properties: {},
    });
  });

  testNewStylesUsingFlowRevamped(renderApp, 'contact-details-section');
});
