import React, { useEffect } from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  Box,
  Text,
  ModalFooter,
  Button,
  CheckIcon,
} from '@razorpay/blade/components';
import { useFormikContext } from 'formik';
import { connect } from 'react-redux';

import { createVCipLink } from 'merchant/reducers/unlockIntlPaymentMethods/actions';
import {
  setIsMethodEnablementFormOpen,
  setKycDocumentStatus,
  setVkycStatus,
} from 'merchant/reducers/unlockIntlPaymentMethods/reducer';
import { V_KYC_STATUS } from 'merchant/reducers/videoKYCBanner';
import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import {
  trackPrerequisiteClicked,
  trackSubmitForVerificationClicked,
  trackVideoKycLinkGeneration,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/analytics';
import {
  TABS,
  FORM_INITIAL_VALUES,
  FORMIK_FORM_KEYS,
  KYC_BUSSINESS_TYPES,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import useFormContext from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useFormContext';
import {
  fetchAdditionalDocumentFormData,
  submitAdditionalDocumentFormData,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/services';
import {
  ApiDataType,
  FormikValues,
  ModalContainerProps,
  VcipLinkGenerationResponse,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';
import { getFormData } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

const ModalContainer = ({
  user,
  isOpen,
  defaultTab = 0,
  vKycStatus,
  kycDocumentStatus,
  onDismiss,
  showNotification,
  createVCipLink,
  setIsMethodEnablementFormOpen,
  setKycDocumentStatus,
}: ModalContainerProps) => {
  const {
    selectedTab,
    apiData,
    isLoading,
    onTabClick,
    setInitialValues,
    setApiData,
    setIsLoading,
  } = useFormContext();

  const { validateForm, errors, values } = useFormikContext<FormikValues>();

  const activeTab = TABS[selectedTab];
  const ActiveTabComponent = activeTab.component;

  const onButtonClick = async () => {
    setIsLoading(true);
    try {
      if (selectedTab === 0) {
        trackPrerequisiteClicked(user.business_type);
      }
      if (selectedTab === 1) {
        trackSubmitForVerificationClicked(user.business_type);
        await submitAdditionalDocumentFormData(apiData as ApiDataType, values, user);
        setKycDocumentStatus(ICProductStates.UNDER_REVIEW);
        if (
          [V_KYC_STATUS.APPROVED, V_KYC_STATUS.UNDER_REVIEW].includes(
            vKycStatus as 'under_review' | 'approved',
          )
        ) {
          setIsMethodEnablementFormOpen({ isOpen: false });
          return;
        }
      }
      if (selectedTab === 2 && values[FORMIK_FORM_KEYS.SIGNATORY] === '1') {
        const vcipLink: VcipLinkGenerationResponse = await createVCipLink(user?.promoter_pan_name);
        if (vcipLink.error) {
          const errorMessage = JSON.parse(vcipLink.error.message ?? '');
          trackVideoKycLinkGeneration(
            user.business_type,
            true,
            errorMessage?.statusCode,
            errorMessage?.message,
          );
          showNotification({
            type: 'error',
            message: errorMessage?.message as string,
          });
        }
        if (vcipLink?.payload?.details?.weblink) {
          trackVideoKycLinkGeneration(user.business_type, true);
          window.open(vcipLink?.payload?.details?.weblink, '_blank');
          setIsMethodEnablementFormOpen({ isOpen: false });
        }
      }
      if (selectedTab === 2 && values[FORMIK_FORM_KEYS.SIGNATORY] === '0') {
        setIsMethodEnablementFormOpen({ isOpen: false });
      }
      if (selectedTab < 2) onTabClick(selectedTab + 1);
    } catch {
      showNotification({
        type: 'error',
        message: 'Something went wrong! Please try again later',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const getButtonText = () => {
    if (selectedTab === 2 && values[FORMIK_FORM_KEYS.SIGNATORY] === '0') {
      return 'Close';
    }
    return activeTab.buttonText;
  };

  const isTabValid = (tabIndex) => {
    if (tabIndex === 2 && vKycStatus === V_KYC_STATUS.APPROVED) return true;
    return selectedTab > tabIndex;
  };

  const isTabActive = (tabIndex) => {
    return tabIndex === selectedTab;
  };

  const checkIfNoKycIsRequired = async (apiData) => {
    if (
      !KYC_BUSSINESS_TYPES.includes(Number(user.business_type)) &&
      [ICProductStates.NOT_ACTIVATED, ICProductStates.REJECTED].includes(
        kycDocumentStatus as ICProductStates,
      )
    ) {
      await submitAdditionalDocumentFormData(apiData, values, user);
      setKycDocumentStatus(ICProductStates.UNDER_REVIEW);
    }
  };

  const initFormRequest = async () => {
    try {
      setIsLoading(true);
      onTabClick(defaultTab);
      const formData = await fetchAdditionalDocumentFormData();
      await checkIfNoKycIsRequired(formData);
      const documents = getFormData(formData);
      setInitialValues({ ...FORM_INITIAL_VALUES, documents });
      setApiData(formData);
      setTimeout(() => validateForm(), 100);
    } finally {
      setIsLoading(false);
    }
  };

  const isButtonDisabled = () => {
    let isDisabled = false;
    activeTab?.validateKeys?.forEach((key) => {
      if (errors[key]) isDisabled = true;
    });
    return isDisabled || isLoading;
  };

  useEffect(() => {
    if (isOpen) initFormRequest();
  }, [isOpen]);

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} size="large">
      <ModalHeader
        title="Request to activate other international payment methods"
        subtitle="Share the details listed below for our team to verify "
      />
      <ModalBody padding="spacing.0" overflow-y="hidden">
        <Box display="flex" flexDirection="row" overflow="hidden">
          <Box
            maxWidth="267px"
            width="100%"
            backgroundColor="surface.background.gray.moderate"
            padding={['spacing.7', 'spacing.5', 'spacing.7', 'spacing.5']}
            display={{ base: 'none', m: 'block' }}
          >
            <Box maxHeight={{ base: 'none', m: '400px' }} minHeight={{ base: 'none', m: '400px' }}>
              {TABS.map(({ name }, index) => (
                <Box
                  display="flex"
                  flexDirection="row"
                  alignItems="center"
                  padding="spacing.3"
                  paddingRight="spacing.0"
                  backgroundColor={
                    isTabActive(index) ? 'surface.background.primary.subtle' : 'transparent'
                  }
                  borderRadius="medium"
                  key={name}
                >
                  {isTabValid(index) && (
                    <CheckIcon marginRight="spacing.3" color="feedback.icon.positive.intense" />
                  )}
                  <Text
                    color={
                      isTabValid(index)
                        ? 'feedback.text.positive.intense'
                        : 'surface.text.gray.normal'
                    }
                  >
                    {name}
                  </Text>
                </Box>
              ))}
            </Box>
          </Box>
          <Box flex="1">
            <Box
              padding={{
                base: 'spacing.5',
                m: ['spacing.7', 'spacing.10', 'spacing.7', 'spacing.10'],
              }}
            >
              <ActiveTabComponent />
            </Box>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" flexDirection="row" justifyContent="flex-end">
          <Button isLoading={isLoading} isDisabled={isButtonDisabled()} onClick={onButtonClick}>
            {getButtonText()}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = ({ unlockIntlPaymentMethods }) => ({
  defaultTab: unlockIntlPaymentMethods.defaultTab,
  vKycStatus: unlockIntlPaymentMethods.vKycStatus,
  kycDocumentStatus: unlockIntlPaymentMethods.kycDocumentStatus,
});

export default connect(mapStateToProps, {
  showNotification,
  setKycDocumentStatus,
  setVkycStatus,
  setIsMethodEnablementFormOpen,
  createVCipLink,
})(ModalContainer);
