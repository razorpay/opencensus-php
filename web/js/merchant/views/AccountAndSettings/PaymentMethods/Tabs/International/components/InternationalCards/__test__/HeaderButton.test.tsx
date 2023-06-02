import HeaderButton from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/HeaderButton';
import * as sessionActions from 'merchant/reducers/session';
import { checkIfComponentIsEmpty, render, screen, server, userEvent, waitFor } from 'test-utils';
import React from 'react';
import * as selfServeActions from 'common/utils/selfServeAnalytics';
import * as trackActions from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import { updateInternationalStatusHandler } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/__test__/mocks/handlers';

jest.mock('common/ui/Forms/SwitchField', () => ({
  __esModule: true,
  default: ({ defaultChecked, onChange }) => (
    <button onClick={() => onChange(defaultChecked ? 0 : 1, jest.fn())} type="button">
      Switch field toggle
    </button>
  ),
}));

jest.mock('@razorpay/blade/components', () => ({
  ...(jest.requireActual('@razorpay/blade/components') as Record<string, unknown>),
  Button: (props) => {
    const { Button } = jest.requireActual('@razorpay/blade/components');
    return (
      <div>
        {`${props.children}: Size - ${props.size}`}
        <Button {...props} />
      </div>
    );
  },
}));

const selfServeTrackInitiateSpy = jest.spyOn(selfServeActions, 'selfServeTrackInitiate');
const selfServeTrackSuccessSpy = jest.spyOn(selfServeActions, 'selfServeTrackSuccess');
const trackIEEventSpy = jest.spyOn(trackActions, 'trackIEEvent');
const updateSessionSpy = jest.spyOn(sessionActions, 'updateSession');

const defaultProps = {
  isAnyProductApproved: false,
  isAnyProductRequested: false,
  isAnyProductRejected: false,
  openQuestionnaire: jest.fn(),
  isRequestRejectedFor90Days: false,
};

const renderApp = ({ props = {}, initialState = {} } = {}) =>
  render(<HeaderButton {...defaultProps} {...props} />, { initialState });
const getRequestForInternationalCardsBtn = () =>
  screen.getByRole('button', { name: 'Request for international cards' });

const getInternationalFieldToggle = () =>
  screen.getByRole('button', { name: 'Switch field toggle' });

const clickToggleAndValidate = async (nextState) => {
  const currentState = nextState === 'Enabled' ? 'Disabled' : 'Enabled';
  server.use(updateInternationalStatusHandler());
  expect(screen.getByText(currentState)).toBeInTheDocument();
  const internationalToggle = getInternationalFieldToggle();
  await userEvent.click(internationalToggle);
  expect(selfServeTrackInitiateSpy).toHaveBeenCalledWith({
    selfServeAction: 'International Payments Applied',
    page: 'Config',
    screen: 'Settings',
  });

  expect(trackIEEventSpy).toHaveBeenCalledWith({
    objectName: 'International Cards Toggle',
    actionName: 'Clicked',
    properties: {
      toggle_status: nextState.toLowerCase(),
    },
  });

  await waitFor(() => {
    expect(screen.getByText(nextState)).toBeInTheDocument();
  });

  expect(selfServeTrackSuccessSpy).toHaveBeenCalledWith({
    selfServeAction: 'International Payments Applied',
    page: 'Config',
    screen: 'Settings',
  });

  expect(updateSessionSpy).toHaveBeenCalled();
};

describe('HeaderButton', () => {
  describe('International Toggle', () => {
    test('should be shown only when any product is approved', () => {
      renderApp({ props: { isAnyProductApproved: true } });
      expect(getInternationalFieldToggle()).toBeInTheDocument();
      expect(screen.getByText('Disabled')).toBeInTheDocument();
    });

    test('should enable international on clicking switch field toggle', async () => {
      renderApp({ props: { isAnyProductApproved: true } });
      await clickToggleAndValidate('Enabled');
    });

    test('should disable international on clicking switch field toggle', async () => {
      renderApp({
        props: { isAnyProductApproved: true },
        initialState: { session: { user: { international: true }, org: {} } },
      });
      await clickToggleAndValidate('Disabled');
    });

    describe('On invalid response', () => {
      test("should show unable to process message when international response doesn't match request body", async () => {
        server.use(
          updateInternationalStatusHandler({
            status_code: 200,
            success: true,
            data: {
              international: 0,
            },
          }),
        );
        renderApp({ props: { isAnyProductApproved: true } });
        const internationalToggle = getInternationalFieldToggle();
        await userEvent.click(internationalToggle);

        await waitFor(() => {
          expect(
            screen.getByText(
              /We are unable to process this request. Please reach out to support@razorpay.com/,
            ),
          ).toBeInTheDocument();
        });
      });

      test.each([
        [
          {
            errors: ['error-text-1', 'error-text-2'],
          },
          'error-text-2',
        ],
        [{}, 'Something went wrong!'],
      ])(
        'should show error message when international api fails with %s',
        async (errorResponse, notification) => {
          server.use(
            updateInternationalStatusHandler({
              status_code: 200,
              success: false,
              ...errorResponse,
            }),
          );
          renderApp({ props: { isAnyProductApproved: true } });
          const internationalToggle = getInternationalFieldToggle();
          await userEvent.click(internationalToggle);

          await waitFor(() => {
            expect(screen.getByText(notification)).toBeInTheDocument();
          });
        },
      );
    });
  });

  describe('Request for international cards', () => {
    test('should be shown when no product is requested', () => {
      renderApp();
      expect(getRequestForInternationalCardsBtn()).toBeInTheDocument();
      expect(screen.queryByRole('button', { name: 'Switch field toggle' })).not.toBeInTheDocument();
    });

    test('should call openQuestionnaire and trackIEEvent on click', async () => {
      renderApp();
      const requestForInternationalCardsBtn = getRequestForInternationalCardsBtn();
      await userEvent.click(requestForInternationalCardsBtn);
      expect(defaultProps.openQuestionnaire).toHaveBeenCalled();
      expect(trackIEEventSpy).toHaveBeenCalledWith({
        objectName: 'Request For International Cards',
        actionName: 'Clicked',
      });
    });

    test('should be shown only when any products is rejected but not for 90 days', () => {
      renderApp({
        props: {
          isAnyProductRequested: true,
          isAnyProductRejected: true,
          isRequestRejectedFor90Days: false,
        },
      });
      expect(getRequestForInternationalCardsBtn()).toBeInTheDocument();
    });

    test('should use medium button when device is mobile', () => {
      renderApp({ initialState: { app: { isMobileResolution: true } } });
      expect(
        screen.getByText('Request for international cards: Size - medium'),
      ).toBeInTheDocument();
    });

    test('should use small button when device is not mobile', () => {
      renderApp({ initialState: { app: { isMobileResolution: false } } });
      expect(screen.getByText('Request for international cards: Size - small')).toBeInTheDocument();
    });
  });

  test('should not render anything when isAnyProductApproved,isAnyProductRejected are false and isAnyProductRequested is true', () => {
    renderApp({
      props: { isAnyProductRequested: true },
    });
    checkIfComponentIsEmpty();
  });
});
