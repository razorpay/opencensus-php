import { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { withRouter } from 'common/deprecated/withRouter';
import { compose } from 'redux';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import * as EventsActions from 'merchant/reducers/trackEvents';

import KycForm from 'merchant/containers/Activation/new';
import { setInstantActivationsTracking } from 'merchant/containers/Activation/ga_new';
import User from 'merchant/models/User';
import errorService from '@razorpay/universe-utils/errorService';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { formatBusinessTypeOptions } from 'merchant/components/Activation/ActivationUtils';

const SubmerchantActivationContainer = ({
  user,
  submerchantId: submerchantIdWithSuffix,
  current_tab_name,
  tracking,
  trackEvents,
  onClose,
}) => {
  const [data, setData] = useState();
  const [categories, setCategories] = useState();
  const [aovRange, setAovRange] = useState();
  const [businessTypeOptions, setBusinessTypeOptions] = useState();
  const [clarificationReasons, setClarificationReasons] = useState();
  const [gstinDetails, setGstinDetails] = useState();
  const [submerchantUser, setSubmerchantUser] = useState(user);
  const submerchantId = submerchantIdWithSuffix && submerchantIdWithSuffix.replace('acc_', '');
  const [additionalModalClassName, setAdditionalModalClassName] = useState('Activation--wizard');
  const [isActivationFormLoading, setIsActivationFormLoading] = useState(false);

  useEffect(() => {
    if (user.showInstantActivation) {
      setInstantActivationsTracking();
    }
  }, []);

  const setAdditionalModalClass = (newModalClass) => {
    if (additionalModalClassName !== newModalClass) {
      setAdditionalModalClassName(newModalClass);
    }
  };

  const updateSubmerchantUser = (activationData) => {
    const {
      activation_progress,
      activated,
      activation_status,
      submitted,
      business_website,
      contact_email,
      activation_form_milestone,
      dedupe,
      poi_verification_status,
      business_type,
      merchant,
      isHardLimitReached,
      company_pan_verification_status,
      business_name,
      company_pan,
      promoter_pan,
      promoter_pan_name,
      bank_details_verification_status,
      bank_branch_ifsc,
      bank_account_name,
      bank_account_number,
    } = activationData;
    const getSubmerchantUser = (prevUser) => {
      const updatedUser = new User({
        ...prevUser,
        activation_progress,
        activated,
        activation_status,
        submitted: submitted ? 1 : 0,
        business_website,
        contact_email,
        activation_form_milestone,
        dedupe,
        poi_verification_status,
        business_type,
        merchant,
        isHardLimitReached,
        company_pan_verification_status,
        business_name,
        company_pan,
        promoter_pan,
        promoter_pan_name,
        bank_details_verification_status,
        bank_branch_ifsc,
        bank_account_name,
        bank_account_number,
        current: submerchantId,
        isSubMerchant: true,
      });
      return updatedUser;
    };
    setSubmerchantUser((prevUser) => getSubmerchantUser(prevUser));
  };

  const fetchActivationDetails = () => {
    const isLiteOnboarding = user?.isLiteOnboarding;
    return Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        accountId: submerchantId,
      }),
      merchantFetch('merchant/activation/business_categories'),
      !isLiteOnboarding && merchantFetch('merchant/aov-config'),
      merchantFetch('merchant/onboarding/business_types'),
    ]).then(async ([formData, newCategories, aov_list, businessTypeOptions]) => {
      const newData = formData.data;
      newCategories = newCategories && newCategories.data;
      aov_list = aov_list && aov_list.data;

      let gst_details;
      let newGstinDetails = null;

      try {
        // call only if L1 form fill up is completed
        if (user.isGstinAutoPopulate && newData?.activation_form_milestone) {
          gst_details = await merchantFetch({
            url: 'merchant/activation/gst_details',
            accountId: submerchantId,
          });
          gst_details = gst_details?.data;
        }
      } catch (error) {
        errorService.captureError(error, {
          tags: {
            team: Teams.PARTNERSHIP,
          },
          rank: Ranks.P2,
        });
      }

      if (gst_details?.results && gst_details.results.length) {
        newGstinDetails = {
          gstinList: gst_details.results,
          defaultGstin: gst_details.results[0],
        };
      }

      if (
        newData.activation_status === 'needs_clarification' &&
        newData.kyc_clarification_reasons
      ) {
        try {
          let newClarificationReasons = await merchantFetch(
            'merchant/activation/clarification_reasons',
          );
          newClarificationReasons = newClarificationReasons && newClarificationReasons.data;
          setClarificationReasons(newClarificationReasons);
        } catch (error) {
          errorService.captureError(error, {
            tags: {
              team: Teams.PARTNERSHIP,
            },
            rank: Ranks.P2,
          });
        }
      } else {
        setClarificationReasons({});
      }
      updateSubmerchantUser(newData);
      setCategories(newCategories);
      setAovRange(aov_list);
      setBusinessTypeOptions(formatBusinessTypeOptions(businessTypeOptions, newData.business_type));
      setGstinDetails(newGstinDetails);
      setData(newData);
      return [newData, newCategories];
    });
  };

  const updateActivationData = (activationData) => {
    setData(activationData);
    updateSubmerchantUser(activationData);
  };

  const handleNewData = (newData) => {
    setData(newData);
    updateSubmerchantUser(newData);
  };

  const handleCloseActivationForm = () => {
    const isL1Submitted = submerchantUser.instantActivation.isL1Submitted;
    let eventName = 'act.form_fill';
    if (isL1Submitted) {
      eventName = 'kyc.form_fill';
    }
    tracking.trackEvent(
      window.rzpQ.onbr().dropped(eventName, {
        clickSource: current_tab_name,
      }),
    );
  };

  const sendSegmentEvents = (isFormCloseAction) => {
    const isL1Submitted = user.instantActivation.isL1Submitted;
    const objectName = isL1Submitted ? 'L2 form' : 'L1 form';
    const actionName = isFormCloseAction ? 'Closed' : 'Loaded';

    trackEvents({
      objectName,
      actionName,
      screen: 'home page',
      properties: {
        submerchant_id: submerchantId,
      },
      toFacebook: true,
    });
  };

  const setActivationFormLoadingState = () => {
    setIsActivationFormLoading((loading) => !loading);
  };

  useEffect(() => {
    fetchActivationDetails();
    let isFormCloseAction = false;
    sendSegmentEvents(isFormCloseAction);
    return () => {
      isFormCloseAction = true;
      sendSegmentEvents(isFormCloseAction);
    };
  }, []);

  const commonProps = {
    submerchantId,
    fetchActivationDetails,
    updateActivationData,
    data,
    clarificationReasons,
    categories,
    aovRange,
    gstinDetails,
    setActivationFormLoadingState,
    isActivationFormLoading,
    businessTypeOptions,
  };
  const isLoading = !data;
  // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
  const isModal = !!onClose;

  let content = null;
  let modalClasses = ['animate-down'];

  if (isLoading) {
    modalClasses = ['spinner', 'transparent'];

    content = (
      <div className="spinner-container">
        <div className={classList('spin-btn large page-center visible', isModal && 'gray')} />
      </div>
    );
  } else {
    content = (
      <KycForm
        {...commonProps}
        onNewData={handleNewData}
        setAdditionalModalClass={setAdditionalModalClass}
        isModalView={isModal}
        submerchantUser={submerchantUser}
      />
    );
  }

  if (additionalModalClassName) {
    modalClasses.push(additionalModalClassName);
  }

  if (isModal) {
    return (
      <div>
        <Modal
          className={classList(...modalClasses)}
          onClose={onClose}
          onCloseCB={handleCloseActivationForm}
          canDisableCloseBtn={isActivationFormLoading}
        >
          <ModalContent>{content}</ModalContent>
        </Modal>
      </div>
    );
  }

  return (
    <div>
      <div className="ActivationContainer kyc">{content}</div>
    </div>
  );
};

export default compose(
  rTracking(() => window.rzpQ.component('SubmerchantActivationContainer')),
  withRouter,
  connect(
    (state) => ({
      user: state.session.user,
      session: state.session,
      current_tab_name: state.activationWizard.current_tab_name,
    }),
    { ...EventsActions },
  ),
)(SubmerchantActivationContainer);
