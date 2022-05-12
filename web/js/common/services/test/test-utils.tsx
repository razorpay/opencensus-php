// test-utils.js
import React, { ReactElement } from 'react';
import { render, waitForElementToBeRemoved, screen, waitFor } from '@testing-library/react';
import { Router, Route } from 'react-router-dom';
// eslint-disable-next-line import/no-extraneous-dependencies
import { createMemoryHistory } from 'history';
import { Provider } from 'react-redux';
import { server } from '../../../../mocks/node';
import { errorHandlers } from '../../../../mocks/errorHandlers';
import { storeWithInitialState } from '../../../merchant/store';
import ModalDialog from 'common/ui/ModalDialog';
import Notifications from 'common/ui/Notifications';
import Wrapper from '../../components/Bootstrap/Wrapper';

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const customRender = (
  ui,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  {
    initialState,
    customerReducers,
    // Remove after updating snapshots
    showModal,
    reduxStore = storeWithInitialState(initialState, customerReducers),
    ...restOptions
  }: any = {},
) => {
  const AllTheProviders: React.FC<{
    children: ReactElement<any, any> | null;
  }> = ({ children }) => {
    const mockRazorXExp = {
      isInstantActivationEnabled: true,
      canSkipPoiValidation: false,
      canGenerateTnCPage: true,
      isBDAndAovEnabled: true,
      isAadharEkycMandatory: true,
      isSyncBankVerificationEnabled: true,
      isEmailMandatoryOnL1: true,
      isEmailNonMandatoryOnL1: false,
      isEmailNonMandatoryOnL2Form: false,
      isActivationFormFullView: true,
      isGstinSyncFlowEnabled: true,
      isLlpinSyncFlowEnabled: true,
      isCinSyncFlowEnabled: true,
      isActivationMccPendingProgressbarDisabled: true,
      isMsmeDisabled: true,
      isAdharEkycRequiredForTrustSocietyNgo: true,
    };
    return (
      <Provider store={reduxStore}>
        <Wrapper
          context={{
            mode: 'test',
            org: { id: '123' },
            user: { contact_name: 'prashant' },
            experiments: mockRazorXExp,
          }}
        >
          <Notifications />

          <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
            <>
              {showModal && <ModalDialog />}

              <Route path="/" component={() => children} />
            </>
          </Router>
        </Wrapper>
      </Provider>
    );
  };

  return render(ui, { wrapper: AllTheProviders, ...restOptions });
};

const waitForLoadingToFinish = (): Promise<void> =>
  waitForElementToBeRemoved(screen.queryAllByTestId('spinner'));

const delay = (time = 1000): Promise<void> => new Promise((r) => setTimeout(r, time));
// re-export everything
export * from '@testing-library/react';

// override render method
export { customRender as render, waitForLoadingToFinish, server, errorHandlers, delay, waitFor };
