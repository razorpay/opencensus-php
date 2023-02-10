import React from 'react';
import { checkIfComponentIsEmpty, render, screen, userEvent, waitFor } from 'test-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { LOADING_STATE } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { LOADING_STEP_DATA } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep/constants';

import LoadingStep from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/LoadingStep/LoadingStep';

describe('Bank Account Update - LoadingStep', () => {
  const modalsSpy = jest.spyOn(ModalActions, 'closeModal');

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test.each(Object.keys(LOADING_STATE))(
    'should render %s loading with correct details',
    async (loadingTypeKey) => {
      const loadingTypeData = LOADING_STATE[loadingTypeKey];
      const { title, subTitle, description, closeCTALabel } = LOADING_STEP_DATA[loadingTypeData];
      render(<LoadingStep type={loadingTypeData} />);

      expect(screen.getByText(title)).toBeInTheDocument();
      expect(screen.getByText(subTitle)).toBeInTheDocument();
      if (description) {
        expect(screen.getByText(description)).toBeInTheDocument();
      }
      if (closeCTALabel) {
        const closeCTA = await screen.getByRole('button', {
          name: closeCTALabel,
        });
        expect(closeCTA).toBeInTheDocument();
      }
    },
  );

  test('should render close modal on close CTA click', async () => {
    const { closeCTALabel } = LOADING_STEP_DATA[LOADING_STATE.PENNY_TESTING_SUCCESS];
    render(<LoadingStep type={LOADING_STATE.PENNY_TESTING_SUCCESS} />);

    const closeCTA = await screen.getByRole('button', {
      name: closeCTALabel,
    });
    expect(closeCTA).toBeInTheDocument();
    userEvent.click(closeCTA);
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalled();
    });
  });

  test('should render mobile subttitle on mobile screen', () => {
    const { mobileSubTitle } = LOADING_STEP_DATA[LOADING_STATE.PENNY_TESTING_SUCCESS];
    render(<LoadingStep type={LOADING_STATE.PENNY_TESTING_SUCCESS} />, {
      initialState: {
        app: {
          isMobileResolution: true,
        },
      },
    });
    if (mobileSubTitle) {
      expect(screen.getByText(mobileSubTitle)).toBeInTheDocument();
    }
  });

  test('should not render anything if step is not a LOADING_STATE', () => {
    render(<LoadingStep type="RANDOM_LOADING_STATE" />);
    checkIfComponentIsEmpty();
  });
});
