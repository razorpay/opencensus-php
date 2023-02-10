import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { stepConfigMocks } from './mocks/fixtures';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import { stepConfig } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/utils/stepConfig';
import { render, screen, userEvent } from 'test-utils';
import Layout from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/ModalLayout';
import * as ModalActions from 'merchant_common/reducers/modals';

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/ModalLayout/ModalLayout.tsx',
);
const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

beforeEach(() => {
  Layout.mockImplementation(
    jest.requireActual(
      'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/ModalLayout/ModalLayout.tsx',
    ).default,
  );
  closeModalSpy.mockClear();
  Layout.mockClear();
});

describe('BankAccountUpdateFlow', () => {
  const renderApp = ({ userInfo, props } = {}) => {
    return render(<BankAccountUpdateFlow {...props} />, {
      initialState: {
        session: {
          user: {
            ...userInfo,
          },
        },
      },
    });
  };

  test.each(Object.keys(stepConfigMocks))('should render components based on views', (steps) => {
    const {
      header: { title },
    } = stepConfig[steps];
    renderApp({
      props: {
        defaultView: steps,
      },
    });
    expect(screen.getByText(stepConfigMocks[steps])).toBeInTheDocument();
    if (title) {
      expect(screen.getByText(title)).toBeInTheDocument();
    }
  });

  describe('BankAccountUpdateStepsAction', () => {
    test('should change the step view on change action', async () => {
      renderApp({
        props: {
          defaultView: BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM,
        },
      });
      expect(
        screen.getByText(stepConfigMocks[BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM]),
      ).toBeInTheDocument();
      const changeView = screen.getByRole('button', {
        name: 'Change View',
      });
      await userEvent.click(changeView);
      expect(
        screen.getByText(stepConfigMocks[BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW]),
      ).toBeInTheDocument();
    });
    test('should render back step view on go back action', async () => {
      renderApp({
        props: {
          defaultView: BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM,
        },
      });
      expect(
        screen.getByText(stepConfigMocks[BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM]),
      ).toBeInTheDocument();
      const changeView = screen.getByRole('button', {
        name: 'Change View',
      });
      await userEvent.click(changeView);
      expect(
        screen.getByText(stepConfigMocks[BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW]),
      ).toBeInTheDocument();
      const goBackAction = screen.getByRole('button', {
        name: 'Go Back',
      });
      await userEvent.click(goBackAction);
      expect(
        screen.getByText(stepConfigMocks[BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM]),
      ).toBeInTheDocument();
    });
  });
  describe('cross icon for closing modal', () => {
    beforeEach(() => {
      Layout.mockImplementation(({ closeModal }) => (
        <div>
          <button onClick={closeModal}>Cross Icon</button>
        </div>
      ));
    });

    test.each([
      BANK_ACCOUNT_UPDATE_STEPS.LOADING_VIEW,
      BANK_ACCOUNT_UPDATE_STEPS.BANK_ACCOUNT_FORM,
      BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF,
    ])('should close modal on cross click', async (view) => {
      renderApp({
        props: {
          defaultView: view,
        },
      });
      const closeCrossIcon = screen.getByRole('button', {
        name: 'Cross Icon',
      });
      await userEvent.click(closeCrossIcon);
      expect(closeModalSpy).toHaveBeenCalled();
    });
  });
});
