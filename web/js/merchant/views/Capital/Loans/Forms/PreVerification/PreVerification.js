import React, { useEffect, useMemo, useReducer } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import { withRouter } from 'react-router-dom';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { showNotification } from 'merchant_common/reducers/notifications';
import FormSectionLoadingSkeleton from 'merchant/views/Capital/components/FormSectionLoadingSkeleton';
import Header from 'merchant/views/Capital/components/Header';
import NetBanking from './NetBanking';
import NativeUpload from './NativeUpload';
import ProcessedState from './ProcessedState';
import NetbankingRedirectConfirmation from './NetbankingRedirectConfirmation';
import {
  APPLICATION_STATES,
  PREVERIFICATION_VIEW_STATES,
  CAPITAL_PRODUCT_NAME_CODE_MAP,
  HOTJAR_TRIGGERS,
  PREVERIFICATION_FILE_UPLOAD_LIMIT,
  PREVERIFICATION_OPTIONS,
} from 'merchant/views/Capital/Loans/constants';
import { isPreceedingState, postToUrl, createFormData } from 'merchant/views/Capital/utils';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  fetchLoanApplicationMeta,
  getNetBankingLink,
  processBankStatement,
  uploadBankStatement,
  uploadPreVerificationDocuments,
  changePseudoState,
  changeActiveState,
} from 'merchant/reducers/capital';
import useQuery from 'merchant/views/Capital/customHooks/useQuery';
import { reducer, initialState } from './StateHelpers';
import {
  setMerchantId,
  trackOptionChange,
  trackStatementUploadSuccess,
  trackFileRemoval,
  trackTotalFilesCount,
  trackManualFileUploadFailure,
  trackNativeSubmission,
  trackNetbankingSubmission,
  trackingNetbankingSuccess,
  trackingNetbankingFailure,
  trackNetbankingRetry,
} from './ga';

const PreVerification = (props) => {
  const {
    documentGroups,
    application,
    context,
    navigation,
    user,
    product,
    applicantId,
    history,
    _trackNavigationActions,
    isUploadAllowed,
  } = props;
  const [state, dispatch] = useReducer(reducer, initialState);
  const queryParams = useQuery();
  const isNetbankingOptionActive = state.option === PREVERIFICATION_OPTIONS.NETBANKING;
  const isNativeUploadOptionActive = state.option === PREVERIFICATION_OPTIONS.NATIVE_UPLOAD;
  const isNativeUploadOptionDisabled =
    isNetbankingOptionActive && state.view === PREVERIFICATION_VIEW_STATES.PROCESSED;
  const hasDocumentsData = useMemo(
    () => !!(documentGroups.data && Object.keys(documentGroups).length),
    [documentGroups],
  );

  // documentConfig contains combined FDS group data from document_groups (Documents API) & application.documents
  const documentConfig = useMemo(() => {
    if (!application || !documentGroups.data || !application.documents) return null;

    const { documents: applicationDocuments = [] } = application;

    const FDSGroup = documentGroups.data.find((documentGroup) => {
      return (
        documentGroup.master_documents &&
        documentGroup.master_documents.length &&
        documentGroup.master_documents[0].external_service_name.toLowerCase() === 'fds'
      );
    });

    if (!FDSGroup) return {};

    const FDSDocumentFromApplication = applicationDocuments.find(
      (applicationDocument) => applicationDocument.document_group_id === FDSGroup.document_group.id,
    );

    if (!FDSDocumentFromApplication) return {};

    return {
      store_id: FDSDocumentFromApplication.store_id,
      document_masters_id: FDSDocumentFromApplication.document_masters_id
        ? FDSDocumentFromApplication.document_masters_id
        : FDSGroup.master_documents[0].id,
      id: FDSDocumentFromApplication.id,
      acceptDocumentTypes: ['pdf'],
      maxDocumentSize: '5242880',
      entity_type: FDSDocumentFromApplication.entity_type,
      document_group: FDSGroup.document_group,
      master_documents: FDSGroup.master_documents,
      isAlreadyUploaded: FDSDocumentFromApplication.status !== 'UPLOAD_PENDING',
      files: FDSDocumentFromApplication.files || [],
      isNetbankingUpload: !!(
        FDSDocumentFromApplication.source &&
        FDSDocumentFromApplication.source.toLowerCase() !== 'bankstatementupload'
      ),
    };
  }, [application, documentGroups]);

  // Effect that set data on component mount
  useEffect(() => {
    setMerchantId(user.current);
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_DOCUMENT_UPLOAD);
  }, [user]);

  // To update BE about Perfios Submission on success
  useEffect(() => {
    async function processPerfios() {
      const { id, success, txnId } = queryParams;

      if (id === undefined || success === undefined) return;

      const parsedSuccess = success && decodeURI(success).trim();
      const applicationIdFromNetbanking = id && decodeURI(id).trim();
      const storeId = txnId && decodeURI(txnId).trim();
      const isSuccessful = parsedSuccess === 'true';
      const isNetbankingFailure = !(
        applicationIdFromNetbanking === application.id &&
        isSuccessful &&
        !!documentGroups.data
      );

      // call API in case of both success & failure
      try {
        await processBankStatement({
          entity_type: 'applicant',
          entity_id: applicantId,
          application_id: application.id,
          document_group_id: documentConfig.document_group.id,
          document_master_id: documentConfig.document_masters_id,
          store_id: storeId,
        });
      } catch (err) {
        props.showNotification({
          type: 'error',
          message: 'Bank Statement Processing failed',
        });
        return;
      }

      // in case of failure
      if (isNetbankingFailure) {
        trackingNetbankingFailure();
        dispatch({
          type: 'DISABLE_NETBANKING',
        });
        dispatch({
          type: 'UPDATE_OPTION',
          payload: PREVERIFICATION_OPTIONS.NATIVE_UPLOAD,
        });
        return;
      }

      // in case of success
      trackingNetbankingSuccess();
      dispatch({
        type: 'UPDATE_VIEW',
        payload: PREVERIFICATION_VIEW_STATES.PROCESSED,
      });
      props.fetchLoanApplicationMeta(application.id);
      history.push('#');
    }

    processPerfios();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hasDocumentsData, application, queryParams]);

  // handle native upload file change
  const handleFileChange = async (file, progressTracker) => {
    if (state.files.length >= PREVERIFICATION_FILE_UPLOAD_LIMIT) {
      props.showNotification({
        type: 'error',
        message: `Max ${PREVERIFICATION_FILE_UPLOAD_LIMIT} files upload allowed. Please remove last file.`,
      });
      return null;
    }

    const fileIdentifier = `${user.current}-${Date.now()}-${file.name}`;

    const data = {
      file,
      display_name: file.name,
      name: fileIdentifier,
      store: 's3',
      type: 'bank_statement',
      'entity[type]': 'APPLICANT',
      'entity[id]': applicantId,
      'metadata[]': '',
    };

    try {
      const { data: { file_id } = {} } = await merchantFetch({
        url: 'ufh/files/upload',
        method: 'post',
        mode: 'live',
        data: createFormData(data),
        onUploadProgress: progressTracker,
      });

      dispatch({
        type: 'ADD_FILE',
        payload: {
          id: fileIdentifier,
          store_id: file_id,
          store_type: 'UFH',
          file_type: 'pdf',
          name: file.name,
        },
      });
      return trackStatementUploadSuccess();
    } catch (err) {
      props.showNotification({
        type: 'error',
        message: 'Occurred a problem while uploading the document.',
      });
      trackManualFileUploadFailure();
      return err;
    }
  };

  // update state when file is removed from selection
  const handleFileRemoval = (fileIndex) => {
    trackFileRemoval();
    dispatch({
      type: 'REMOVE_FILE',
      payload: fileIndex,
    });
  };

  // updates current selection
  const handleOptionClick = (option) => {
    trackOptionChange(option === PREVERIFICATION_OPTIONS.NETBANKING);
    dispatch({
      type: 'UPDATE_OPTION',
      payload: option,
    });
  };

  const handleNetbankingSubmission = async () => {
    const returnUrlParams = new URLSearchParams({
      action: 'open',
      id: application.id,
      txnId: '%s',
      success: '%s',
    });
    const productCode = Object.keys(CAPITAL_PRODUCT_NAME_CODE_MAP).find(
      (key) => CAPITAL_PRODUCT_NAME_CODE_MAP[key] === product,
    );
    const returnUrl = new URL(
      `${window.location.protocol}//${
        window.location.host
      }/app/capital/${productCode}/apply?${returnUrlParams.toString()}`,
    );

    try {
      trackNetbankingSubmission();
      const { data: { url, payload, signature } = {} } = await getNetBankingLink({
        entity_id: applicantId,
        entity_type: 'APPLICANT',
        email: user.email,
        return_url: returnUrl,
        destination: 'netbankingFetch',
        application_id: application.id,
        document_master_id: documentConfig.document_masters_id,
        document_group_id: documentConfig.document_group.id,
      });
      postToUrl(url, {
        payload,
        signature,
      });
    } catch (err) {
      // increase error count
      dispatch({ type: 'UPDATE_NETBANKING_ERROR_COUNT' });

      // disable netbanking, when error count >= 2
      if (state.netbankingErrorCount >= 2) {
        dispatch({
          type: 'DISABLE_NETBANKING',
        });
      }
    }
  };

  const moveToNextStep = () => {
    return props.fetchLoanApplicationMeta(application.id).then((res) => {
      if (res?.data?.application?.status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS) {
        props.changeActiveState(APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS);
      } else {
        navigation.next();
      }
    });
  };

  const handleNativeUploadSubmission = async () => {
    if (!state.files.length) {
      return props.showNotification({
        type: 'error',
        message: 'Please upload bank statements to continue forward',
      });
    }

    try {
      trackNativeSubmission();
      trackTotalFilesCount(state.files.length);
      await props.uploadPreVerificationDocuments({
        application_id: application.id,
        documents: [
          {
            document_masters_id: documentConfig.document_masters_id,
            store_type: 'FDS',
            processed: false,
            entity_type: 'APPLICANT',
            entity_id: applicantId,
            files: state.files,
          },
        ],
      });
      return moveToNextStep();
    } catch (err) {
      return props.showNotification({
        type: 'error',
        message: 'Statements submission failed',
      });
    }
  };

  const handleSubmission = () => {
    _trackNavigationActions('NEXT', APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS);

    // if already submitted then move to next
    if (!isPreceedingState(application.status, context.activeState)) {
      return moveToNextStep();
    }

    if (isNetbankingOptionActive) {
      return dispatch({
        type: 'TOGGLE_MODAL_VISIBILITY',
        payload: true,
      });
    }

    return handleNativeUploadSubmission();
  };

  // loading screen till document groups & application both are fetched
  if (documentGroups.loading || !application.documents) {
    return <FormSectionLoadingSkeleton />;
  }

  const isStatementAlreadyUploaded =
    (documentConfig && documentConfig.isAlreadyUploaded) || !isUploadAllowed;

  let isSubmitDisabled = true;
  let ctaText = 'Continue with Netbanking';

  if (isStatementAlreadyUploaded) {
    ctaText = 'Continue';
    isSubmitDisabled = false;
  } else if (isNativeUploadOptionActive) {
    ctaText = 'Submit all files';
    if (state.files && state.files.length) isSubmitDisabled = false;
  } else if (isNetbankingOptionActive) {
    ctaText = 'Continue with Netbanking';
    isSubmitDisabled = false;
  }

  if (state.isModalOpen) {
    return (
      <NetbankingRedirectConfirmation
        onClick={handleNetbankingSubmission}
        onClose={() =>
          dispatch({
            type: 'TOGGLE_MODAL_VISIBILITY',
            payload: false,
          })
        }
      />
    );
  }

  return (
    <div className="preverification-container">
      <Header
        heading="Bank Statement"
        subHeading={
          <>
            Please share the last <strong>6 months</strong>
            {'  '}
            bank statements (current month included) of your business&apos;s{' '}
            <strong>Primary Bank account</strong>.{' '}
            <strong>Savings account statements are not accepted</strong>.
          </>
        }
      />
      <div class="preverification-content">
        <div className="preverification-content__options">
          <div className="flex">
            <div className="left">
              <p>Provide Bank Statement</p>
            </div>
            <div className="right">
              {isStatementAlreadyUploaded ? (
                <ProcessedState
                  files={documentConfig.files}
                  isNetbankingUpload={documentConfig.isNetbankingUpload}
                />
              ) : (
                <div class="preverification-content__option">
                  <NetBanking
                    selected={isNetbankingOptionActive}
                    disabled={state.netbankingDisabled}
                    view={state.view}
                    errorCount={state.netbankingErrorCount}
                    onClick={() => handleOptionClick(PREVERIFICATION_OPTIONS.NETBANKING)}
                    onRetry={() => {
                      trackNetbankingRetry();
                      handleNetbankingSubmission();
                    }}
                    showAltText={isNativeUploadOptionActive}
                  />
                  <NativeUpload
                    selected={isNativeUploadOptionActive}
                    disabled={isNativeUploadOptionDisabled}
                    hasFiles={!!state.files.length}
                    onClick={() => handleOptionClick(PREVERIFICATION_OPTIONS.NATIVE_UPLOAD)}
                    documentConfig={documentConfig}
                    onFileChange={handleFileChange}
                    onRemoveFile={handleFileRemoval}
                  />
                </div>
              )}
            </div>
          </div>
        </div>
        <div className="preverification-content__actions pull-right flex">
          <Button.Transparent
            onClick={() => {
              _trackNavigationActions('BACK', APPLICATION_STATES.CREDIT_PULL_PENDING);
              navigation.back();
            }}
          >
            <i className="i i-chevron-left" />
            Back
          </Button.Transparent>
          <AsyncBtn.Primary class="m-l" onClick={handleSubmission} disabled={isSubmitDisabled}>
            {ctaText}
            <i className="i i-chevron-right" />
          </AsyncBtn.Primary>
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  const {
    session: { user = {} } = {},
    loanApplicationDetails: {
      document_groups: { loading, data: { document_groups } = {} } = {},
      meta: { data: { application = {} } = {} } = {},
      promoter_details: { data: { applicant: { id: applicantId } = {} } = {} } = {},
      context,
    },
  } = state;

  const isUploadAllowed = !!(
    application.status === APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING ||
    application.status === APPLICATION_STATES.PREVERIFICATION_FAILED
  );

  return {
    user,
    documentGroups: {
      loading,
      data: document_groups,
    },
    application,
    applicantId,
    context,
    isUploadAllowed,
  };
};

const mapDispatchToProps = (dispatch) => {
  return {
    uploadPreVerificationDocuments: bindActionCreators(uploadPreVerificationDocuments, dispatch),
    uploadBankStatement: bindActionCreators(uploadBankStatement, dispatch),
    fetchLoanApplicationMeta: bindActionCreators(fetchLoanApplicationMeta, dispatch),
    changePseudoState: bindActionCreators(changePseudoState, dispatch),
    showNotification: bindActionCreators(showNotification, dispatch),
    changeActiveState: bindActionCreators(changeActiveState, dispatch),
  };
};

export default compose(connect(mapStateToProps, mapDispatchToProps), withRouter)(PreVerification);
