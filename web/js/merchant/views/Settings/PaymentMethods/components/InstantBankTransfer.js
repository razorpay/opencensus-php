import { useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { setFormData, setFormError, setFormFetching } from 'merchant/reducers/apmForm/actions';
import { videoKycBannerActions } from 'merchant/reducers/videoKYCBanner';
import { DISABLE_REQUEST_TOOLTIP } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import { REQUESTABLE, GREYED } from 'merchant/views/Settings/PaymentMethods/constants';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import ApmOnboarding from './ApmOnboarding';
import {
  trackInstrumentsRequested,
  trackDataSaving,
  trackDataSaveError,
  trackDataSaveSuccess,
} from './ApmOnboarding/analytics';
import { INSTRUMENTS, tabs } from './ApmOnboarding/constants';
import { saveForm, fetchFormData } from './ApmOnboarding/services';
import { refreshEntries } from './ApmOnboarding/utils';
import InstrumentContainer from './InstrumentContainer/index';

//Functions
const track = ({ properties, ...args }) => {
  analyticsTrack({
    objectName: 'apm onboarding',
    screen: 'settings',
    ...args,
    properties: {
      location: 'Payment Methods',
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

const InstantBankTransfer = ({
  leafList,
  formData,
  history,
  openModal,
  setFormData,
  setFormFetching,
  setFormError,
  showNotification,
  setInstantBankAccountsActivated,
  showMorePaymentMethodsSection,
}) => {
  const { submitted } = formData ?? {};
  const isSubmitted = submitted === '1' || submitted === true;

  const isDisableInternationalPaymentMethods = showMorePaymentMethodsSection;

  const trackActivateClicked = (formSubmitted, containerButtonClicked, listButtonClicked) => {
    track({
      objectName: 'apm onboarding instruments',
      actionName: 'click',
      properties: {
        formSubmitted,
        containerButtonClicked,
        listButtonClicked,
      },
    });
  };

  const fetchApmForm = async () => {
    try {
      setFormFetching();
      const response = await fetchFormData();
      response?.success && setFormData(response?.data);
    } catch (error) {
      setFormError(error);
    }
  };

  const onRequest = () => {
    trackActivateClicked(false, true);
    fetchApmForm();
    openModal({
      component: <ApmOnboarding instrumentList={leafList?.list} />,
      overlayStyles: { display: 'flex', justifyContent: 'center', alignItems: 'center' },
    });
  };

  const transformApiBody = (instrumentName) => {
    const data = tabs.reduce((prev, current) => {
      prev[current?.dataKey] = formData?.[current?.dataKey];
      if (current?.dataKey === INSTRUMENTS) {
        prev[current?.dataKey] = [...formData?.[current?.dataKey], instrumentName];
      } else {
        prev[current?.dataKey] = formData?.[current?.dataKey];
      }
      return prev;
    }, {});
    data.submitted = true;
    return data;
  };

  const onInstrumentButtonClick = async (instrument) => {
    if (isSubmitted) {
      try {
        trackActivateClicked(true, false, true);
        trackDataSaving(true, null, true);
        const data = transformApiBody(instrument?.name?.toLowerCase());
        await saveForm(data, setFormData);
        showNotification({
          type: 'success',
          message: 'Request has been successfully created!',
        });
        refreshEntries(history);
        trackDataSaveSuccess(null, null, true);
        trackInstrumentsRequested([instrument?.name]);
      } catch (error) {
        showNotification({
          type: 'error',
          message: error?.errors,
        });
        trackDataSaveError(null, true, error?.errors);
      }
    } else {
      onRequest();
    }
  };

  const showRequestButton = useCallback(() => {
    let showButton = false;
    let someRequestable = false;
    if (isSubmitted && !isDisableInternationalPaymentMethods) {
      showButton = false;
    } else {
      leafList.list.forEach((instrument) => {
        if ([REQUESTABLE, GREYED].includes(instrument.status)) {
          someRequestable = true;
        }
      });
      showButton = someRequestable;
    }
    return !isDisableInternationalPaymentMethods ? showButton : true;
  }, [isSubmitted, leafList, isDisableInternationalPaymentMethods]);

  const showListAction = () => {
    let showAction = false;
    if (isSubmitted) {
      showAction = true;
    } else {
      leafList.list.forEach((instrument) => {
        if (![REQUESTABLE, GREYED].includes(instrument.status)) {
          showAction = true;
        }
      });
    }
    return showAction;
  };

  useEffect(() => {
    !formData.instruments && fetchApmForm();
  }, [formData]);

  useEffect(() => {
    setInstantBankAccountsActivated(!showRequestButton());
  }, [setInstantBankAccountsActivated, showRequestButton]);

  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CROSS_BORDER} resetOnProps>
      <div className="instant-bank-transfer">
        <InstrumentContainer
          onButtonClick={onRequest}
          buttonText={formData?.instruments?.length ? 'Edit Draft' : 'Request'}
          showAction={showRequestButton()}
          leafList={leafList}
          showListAction={showListAction()}
          onInstrumentRequest={onInstrumentButtonClick}
          isRequestButtonDisabled={isDisableInternationalPaymentMethods}
          requestTooltipText={isDisableInternationalPaymentMethods ? DISABLE_REQUEST_TOOLTIP : ''}
        />
      </div>
    </ErrorBoundary>
  );
};

const mapStateToProps = ({ apmForm, unlockIntlPaymentMethods }) => ({
  formData: apmForm.data,
  isLoading: apmForm.isLoading,
  showMorePaymentMethodsSection: unlockIntlPaymentMethods.showMorePaymentMethodsSection,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setFormFetching,
      setFormData,
      setFormError,
      openModal,
      showNotification,
      setInstantBankAccountsActivated: videoKycBannerActions.setInstantBankAccountsActivated,
    },
    dispatch,
  );

export default compose(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
)(InstantBankTransfer);
