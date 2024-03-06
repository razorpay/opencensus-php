import {
  CommonInstrumentRequestInfo,
  ICEnablementWorkflowInfo,
  OpenModalType,
  User,
} from 'common/typings';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { isVisible } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { LeafListItemHeader } from 'merchant/views/AccountAndSettings/PaymentMethods/components/LeafListItem';
import Banner from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner';
import DisabledInternationalCardsSection from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/DisabledInternationalCardsSection';
import HeaderButton from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/HeaderButton';
import ICProductsInfo from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/ICProducts';
import UpdateBusinessDetailsModal from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/UpdateBusinessDetailsModal';
import {
  getIsInternationalCardsDisabledReason,
  getProductState,
  getRejectionInfo,
  getWorkflowUnderReviewBannerAndEta,
  isNCState,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils';
import {
  BannerType,
  CommonICProductsState,
  ICProductStates,
  MerchantICProductStatus,
  ProductWorkflowStatesInBackend,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import Questionnaire from 'merchant/views/Settings/Configuration/Questionnaire';
import { openModal as openModalFn } from 'merchant_common/reducers/modals';
import React, { useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
export interface Props {
  instrument: CommonInstrumentRequestInfo;
  user: User;
  openModal: OpenModalType;
  workflowInfo: ICEnablementWorkflowInfo;
  productStatus: MerchantICProductStatus | null;
  onQuestionnaireSubmitSuccess: () => void;
}

const InternationalCards = ({
  instrument,
  user,
  openModal,
  workflowInfo,
  productStatus,
  onQuestionnaireSubmitSuccess,
}: Props) => {
  const internationalCardsDisabledReason = getIsInternationalCardsDisabledReason({ user });
  const openQuestionnaire = (triggerSource = '') => {
    let modalOptions: Parameters<OpenModalType>[0] = {
      component: (
        <Questionnaire
          triggerSource={triggerSource}
          onQuestionnaireSubmitSuccess={onQuestionnaireSubmitSuccess}
          productStatus={productStatus}
          isRevampFlow
        />
      ),
      overlayStyles: { display: 'flex', justifyContent: 'center', alignItems: 'center' },
    };
    if (triggerSource === 'pg' && !user?.business_website) {
      modalOptions = {
        component: <UpdateBusinessDetailsModal />,
        overlayStyles: { alignItems: 'flex-start' },
      };
    }

    openModal(modalOptions);
  };

  const commonProductsState: CommonICProductsState = useMemo(() => {
    let isAnyProductApproved = false;
    let isAnyProductRequested = false;
    let hasUserDisabledInternationalCards = false;
    let isAnyProductInReview = false;
    let isAnyProductRejected = false;
    let isNoProductApprovedOrInReview = false;
    if (productStatus) {
      isAnyProductApproved =
        productStatus.payment_gateway === ProductWorkflowStatesInBackend.APPROVED ||
        productStatus.invoices === ProductWorkflowStatesInBackend.APPROVED;

      isAnyProductRequested =
        productStatus.payment_gateway !== ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED ||
        productStatus.invoices !== ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED;

      isAnyProductInReview =
        productStatus.payment_gateway === ProductWorkflowStatesInBackend.IN_REVIEW ||
        productStatus.invoices === ProductWorkflowStatesInBackend.IN_REVIEW;

      isAnyProductRejected =
        productStatus.payment_gateway === ProductWorkflowStatesInBackend.REJECTED ||
        productStatus.invoices === ProductWorkflowStatesInBackend.REJECTED;

      isNoProductApprovedOrInReview = !(isAnyProductApproved || isAnyProductInReview);

      if (isAnyProductApproved) {
        hasUserDisabledInternationalCards = !user?.international;
      }
    }
    return {
      isAnyProductApproved,
      isAnyProductRequested,
      hasUserDisabledInternationalCards,
      isAnyProductInReview,
      isAnyProductRejected,
      isNoProductApprovedOrInReview,
    };
  }, [productStatus, user?.international]);

  const {
    isAnyProductApproved,
    isAnyProductRequested,
    hasUserDisabledInternationalCards,
    isAnyProductInReview,
    isAnyProductRejected,
    isNoProductApprovedOrInReview,
  } = commonProductsState;

  const { bannerType, eta, bannerMessage, isRequestRejectedFor90Days } = useMemo(() => {
    let eta: string | null = null,
      bannerType: BannerType | null = null,
      bannerMessage: string | null = null,
      isRequestRejectedFor90Days = false;

    if (isAnyProductRequested && workflowInfo && productStatus) {
      const { workflow_created_at } = workflowInfo;

      if (isAnyProductInReview) {
        if (isNCState(workflowInfo)) {
          bannerType = BannerType.NEEDS_CLARIFICATION;
        } else if (workflow_created_at) {
          const underReviewInfo = getWorkflowUnderReviewBannerAndEta(workflow_created_at);
          bannerType = underReviewInfo.banner;
          eta = underReviewInfo.eta;
        }
      } else if (
        isAnyProductRejected &&
        workflowInfo?.rejection_reason_message &&
        workflowInfo?.workflow_rejected_at
      ) {
        const rejectionInfo = getRejectionInfo(
          workflowInfo.rejection_reason_message,
          workflowInfo.workflow_rejected_at,
        );
        if (
          rejectionInfo.isRequestRejectedFor90Days ||
          isVisible(true, user.id, WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI)
        ) {
          bannerType = BannerType.REJECTED;
          bannerMessage = rejectionInfo.reason;
          isRequestRejectedFor90Days = rejectionInfo.isRequestRejectedFor90Days;
        }
      } else if (
        isAnyProductApproved &&
        isVisible(true, user.id, WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI)
      ) {
        bannerType = BannerType.APPROVED;
        bannerMessage = 'You can now collect international card payments on ';
        if (productStatus.payment_gateway === ProductWorkflowStatesInBackend.APPROVED) {
          bannerMessage += 'payment gateway';

          if (productStatus.invoices === ProductWorkflowStatesInBackend.APPROVED) {
            bannerMessage += ', payment pages, payment links, and invoices';
          }
        } else if (productStatus.invoices === ProductWorkflowStatesInBackend.APPROVED) {
          bannerMessage += 'payment pages, payment links, and invoices';
        }
        bannerMessage += '.';
      }
    }
    return { bannerType, eta, bannerMessage, isRequestRejectedFor90Days };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [
    isAnyProductRequested,
    workflowInfo,
    productStatus,
    isAnyProductInReview,
    isAnyProductRejected,
    isAnyProductApproved,
  ]);

  const { pgProductState, ppliProductState } = useMemo(() => {
    let pgProductState: ICProductStates | null = null,
      ppliProductState: ICProductStates | null = null;
    if (productStatus && workflowInfo) {
      pgProductState = getProductState({
        backendProductState: productStatus.payment_gateway,
        workflowInfo,
        isRequestRejectedFor90Days,
      });
      ppliProductState = getProductState({
        backendProductState: productStatus.invoices,
        workflowInfo,
        isRequestRejectedFor90Days,
      });
    }
    return {
      pgProductState,
      ppliProductState,
    };
  }, [productStatus, workflowInfo, isRequestRejectedFor90Days]);

  const isInternationalDisabledDueToFailedChecks =
    !!internationalCardsDisabledReason && isNoProductApprovedOrInReview;

  return (
    <>
      <LeafListItemHeader
        name={instrument.name}
        description={instrument.description}
        actionComponent={
          !isInternationalDisabledDueToFailedChecks ? (
            <HeaderButton
              {...commonProductsState}
              isRequestRejectedFor90Days={isRequestRejectedFor90Days}
              openQuestionnaire={openQuestionnaire}
            />
          ) : undefined
        }
      />
      {isInternationalDisabledDueToFailedChecks ? (
        <DisabledInternationalCardsSection reason={internationalCardsDisabledReason} />
      ) : (
        <>
          {bannerType && pgProductState && ppliProductState ? (
            <Banner
              type={bannerType}
              workflowEta={eta}
              bannerMessage={bannerMessage}
              pgProductState={pgProductState}
              ppliProductState={ppliProductState}
              isRequestRejectedFor90Days={isRequestRejectedFor90Days}
            />
          ) : null}
          {isAnyProductApproved && !hasUserDisabledInternationalCards ? (
            <ICProductsInfo
              pgProductState={pgProductState}
              ppliProductState={ppliProductState}
              onRequestAccessClick={openQuestionnaire}
            />
          ) : null}
        </>
      )}
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  workflowInfo: state.workflows[WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI],
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: openModalFn,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(InternationalCards);
