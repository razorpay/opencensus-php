import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import DocumentsUpload from '../../components/DocumentsUpload';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  fetchLoanApplicationMeta,
  getNetBankingLink,
  processBankStatement,
  uploadBankStatement,
  uploadPreVerificationDocuments,
  changePseudoState,
} from 'merchant/reducers/capital';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import FormSectionLoadingSkeleton from '../../components/FormSectionLoadingSkeleton';
import { NetbankingMeta, NativeUploadMeta, NetbankingHint } from './PerVerificationComponents';
import { APPLICATION_STATES, CAPITAL_PRODUCT_NAME_CODE_MAP, HOTJAR_TRIGGERS } from '../constants';
import { withRouter } from 'react-router-dom';
import { isPreceedingState, postToUrl } from '../../utils';

const createFormData = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map((key) => {
    formData.append(key, form[key]);
  });
  return formData;
};

export const toBase64 = (file) =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = (error) => reject(error);
  });

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    uploadPreVerificationDocuments,
    uploadBankStatement,
    fetchLoanApplicationMeta,
    changePseudoState,
    ...NotificationActions,
  },
)
class PreVerificationUpload extends Component {
  constructor(props) {
    super(props);

    this.uploadModesMeta = {
      native_upload: {
        title: 'Upload from this Device',
        description: "Drag or upload last 6 month's statement till today",
        disabled: false,
        meta: <NativeUploadMeta />,
      },
      perfios: {
        title: 'Use Netbanking',
        disabled: false,
        hint: <NetbankingHint />,
        description: 'We will be redirecting you to Netbanking',
        meta: <NetbankingMeta />,
      },
    };

    this.state = {
      activeTabIndex: 0,
      documents: [],
      tabs: [],
      uploadModesMeta: this.uploadModesMeta,
      perfiosRedirectErrorCount: 0,
    };
  }

  getBankStatementDetails = () => {
    const { meta, document_groups } = this.props.loanApplicationDetails;
    if (!document_groups) return;

    const documentGroups = document_groups.data.document_groups;

    const incomeProofDocumentGroup = documentGroups
      .filter((docGroup) => docGroup.master_documents && docGroup.master_documents.length > 0)
      .find((docGroup) =>
        docGroup.master_documents.find((masterDoc) => masterDoc.type === 'bank_statement'),
      );
    const bankStatementMasterDoc = incomeProofDocumentGroup.master_documents.find(
      (masterDoc) => masterDoc.type === 'bank_statement',
    );
    return {
      document_group_id: incomeProofDocumentGroup.document_group.id,
      document_master_id: bankStatementMasterDoc.id,
      document_id: meta.data.application.documents.find(
        (doc) => doc.document_group_id === incomeProofDocumentGroup.document_group.id,
      ).id,
    };
  };

  showProcessingBankStatementFeedback = () => {
    this.setState((prevState) => ({
      processingPerfiosBankStatement: true,
      uploadModesMeta: {
        ...prevState.uploadModesMeta,
        perfios: {
          ...prevState.uploadModesMeta.perfios,
          title: 'Processing Bank Statement',
          disabled: false,
          description: 'Please wait till we process your bank statement',
          loading: true,
        },
        native_upload: {
          ...prevState.uploadModesMeta.native_upload,
          title: 'Upload from this Device',
          description: "Drag or upload last 6 month's statement till today",
          disabled: true,
          showRadioInput: false,
        },
      },
    }));
  };

  componentDidMount() {
    this.deriveFormData();
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_DOCUMENT_UPLOAD);
    const { promoter_details, meta } = this.props.loanApplicationDetails;
    const searchParams = this.props.history.location.search;
    if (searchParams) {
      const {
        document_group_id: documentGroupId,
        document_master_id: documentMasterId,
      } = this.getBankStatementDetails();
      const params = new URLSearchParams(searchParams);
      const loanId = params.get('id');
      const applicantId = promoter_details.data.applicant.id;
      const success = params.get('success') ? params.get('success').trim() === 'true' : false;

      this.props.history.push('#');
      if (documentGroupId && documentMasterId && loanId === meta.data.application.id) {
        this.processBankSubmissionCallback({
          applicantId,
          loanId,
          documentMasterId,
          documentGroupId,
          success,
        });
      }
    }
  }

  disablePerfios = () => {
    this.props.changePseudoState('BANK_STATEMENT_PROCESSING_FAILED');
    this.setState({
      processingPerfiosBankStatement: false,
    });
    this.setState((prevState) => ({
      processingPerfiosBankStatement: false,
      uploadModesMeta: {
        ...prevState.uploadModesMeta,
        perfios: {
          ...prevState.uploadModesMeta.perfios,
          title: 'Use Netbanking',
          disabled: true,
          description: 'Previous upload failed',
        },
        native_upload: {
          ...prevState.uploadModesMeta.native_upload,
          disabled: false,
        },
      },
      selectedUploadModes: {
        ...prevState.selectedUploadModes,
        [this.getBankStatementDetails().document_id]: 'native_upload',
      },
    }));
  };

  showBankStatementProcessedFeedback = () => {
    this.setState((prevState) => ({
      processingPerfiosBankStatement: false,
      uploadModesMeta: this.uploadModesMeta,
    }));
  };

  processBankSubmissionCallback = async ({
    loanId,
    documentGroupId,
    documentMasterId,
    applicantId,
    success,
  }) => {
    // set active tab to bank statement to show processing/failed feedback
    this.handleTabChange(0);
    try {
      if (success) {
        this.showProcessingBankStatementFeedback();
        await processBankStatement({
          entity_type: 'applicant',
          entity_id: applicantId,
          application_id: loanId,
          document_group_id: documentGroupId,
          document_master_id: documentMasterId,
        });
        this.showBankStatementProcessedFeedback();
        this.props.history.push('#');
        this.props.fetchLoanApplicationMeta(
          this.props.loanApplicationDetails.meta.data.application.id,
        );
      } else {
        this.disablePerfios();
      }
    } catch (e) {
      console.error('error while processing bank statement', e);
      this.disablePerfios();
    }
  };

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (
      prevProps.loanApplicationDetails.document_groups !==
        this.props.loanApplicationDetails.document_groups ||
      prevProps.loanApplicationDetails.meta.data.application.documents !==
        this.props.loanApplicationDetails.meta.data.application.documents
    ) {
      this.deriveFormData();
    }
  }

  handleFooterActions = (indexChangeBy = 1) => {
    this.setState(
      (prevState) => ({
        activeTabIndex: prevState.activeTabIndex + indexChangeBy,
      }),
      () => {
        const tab = this.state.tabs.filter((tab) => tab.index === this.state.activeTabIndex);
        const title = tab.length ? tab[0].title : null;

        this.props._trackNavigationActions(
          'NEXT',
          APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
          title,
        );
      },
    );
  };

  handleTabChange = (targetTab, title) => {
    this.setState({
      activeTabIndex: targetTab,
    });
    this.props._trackNavigationActions(
      'NEXT',
      APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
      title,
    );
  };

  getDocumentProperty = (documentId, master_document_id, property) => {
    const { meta, document_groups } = this.props.loanApplicationDetails;
    const documentGroups = document_groups.data.document_groups;
    const documentGroupId = meta.data.application.documents.find((doc) => doc.id === documentId)
      .document_group_id;
    if (documentGroupId) {
      const selectedDocGroup = documentGroups.find(
        (docGroup) => docGroup.document_group.id === documentGroupId,
      );
      return selectedDocGroup.master_documents.find(
        (master_doc) => master_doc.id === master_document_id,
      )[property];
    }
  };

  getStoreType = (docId, masterDocId) => {
    const externalServiceType = this.getDocumentProperty(
      docId,
      masterDocId,
      'external_service_name',
    );
    switch (externalServiceType) {
      case 'KYC':
      case 'UFH':
        return 'UFH';
      //This may change in future, so keeping open for the change.
      case 'FDS':
        // return 'FDS';
        return 'UFH';
      default:
        console.error('Unknown external service type detected: ', externalServiceType);
    }
  };

  uploadToUfh = (documentId, file, progressTracker) => {
    const document = this.state.documents.find((doc) => doc.id === documentId);

    const documentType = this.getDocumentProperty(documentId, document.document_masters_id, 'type');

    const { promoter_details, business_details } = this.props.loanApplicationDetails;

    const isAadhaarDocument = documentType === 'aadhaar';

    if (documentId === this.getBankStatementDetails().document_id) {
      this.props.changePseudoState(null);
    }
    const data = {
      file,
      display_name: file.name,
      name: `${this.props.user.current}-${Date.now()}-${file.name}`,
      store: 's3',
      type: documentType,
      'entity[type]': isAadhaarDocument ? 'MERCHANT' : 'applicant',
      'entity[id]': isAadhaarDocument
        ? business_details.data.business.reference_id
        : promoter_details.data.applicant.id,
      'metadata[]': '',
    };

    return new Promise((resolve, reject) => {
      merchantFetch({
        url: 'ufh/files/upload',
        method: 'post',
        mode: 'live',
        data: createFormData(data),
        onUploadProgress: progressTracker,
      })
        .then((response) => {
          if (response.errors) {
            reject(response);
          }
          const storeType = this.getStoreType(documentId, document.document_masters_id);
          this.props.uploadPreVerificationDocuments({
            application_id: this.props.loanApplicationDetails.meta.data.application.id,
            documents: [
              {
                document_masters_id: document.document_masters_id,
                store_type: storeType,
                processed: true,
                store_id: response.data.file_id,
                entity_type: document.entity_type,
              },
            ],
          });
          resolve(response);
        })
        .catch((e) => {
          this.props.showNotification({
            type: 'error',
            message: 'Occurred a problem while uploading the document.',
          });
          console.error(e);
          reject(e);
        });
    });
  };

  resourceUrlPrefix(domain, entity, endpoint) {
    return `los/service/twirp/rzp.capital.los.${domain}.v1.${entity}/${endpoint}`;
  }

  uploadBankStatement = async (masterDocumentId, file, progressTracker) => {
    const { meta, promoter_details } = this.props.loanApplicationDetails;
    //todo: add this common utils
    const encodedFile = await toBase64(file);
    const data = {
      //todo:take the applicant id
      entity_id: promoter_details.data.applicant.id,
      entity_type: 'APPLICANT',
      application_id: meta.data.application.id,
      file_content_type: 'base64',
      document_master_id: masterDocumentId,
      // we want to strip of the first 21 charecters which is
      // "data:text/xml;base64," which gets preprended to the encoded file
      file: encodedFile.substring(21),
    };
    return this.props
      .uploadBankStatement(data, progressTracker)
      .then((response) => {
        if (!response.errors) {
          return this.props.fetchLoanApplicationMeta(
            this.props.loanApplicationDetails.meta.data.application.id,
          );
        }
      })
      .catch((e) => {
        this.props.showNotification({
          type: 'error',
          message: 'Occurred a problem while uploading the document.',
        });
        console.error(e);
        reject(e);
      });
  };

  handleFileChange = (documentId, file, progressTracker) => {
    const document = this.state.documents.find((doc) => doc.id === documentId);
    const externalServiceType = this.getDocumentProperty(
      document.id,
      document.document_masters_id,
      'external_service_name',
    );
    switch (externalServiceType) {
      case 'KYC':
      case 'UFH':
      //In future, this might change when we want to store FDS related
      // documents other than UFH.
      case 'FDS':
        return this.uploadToUfh(documentId, file, progressTracker);
      // case 'FDS':
      //   return this.uploadBankStatement(
      //     document.document_masters_id,
      //     file,
      //     progressTracker
      //   );
      default:
        console.error('Unknown external service type detected: ', externalServiceType);
    }
  };

  handleRemoveFile = (documentId) => {
    this.setState((prevState) => ({
      documents: prevState.documents.map((doc) => {
        if (doc.id === documentId) {
          return {
            ...doc,
            store_id: null,
          };
        }
        return doc;
      }),
    }));
  };

  isValidTab = (tabIndex) => {
    const documents = this.getEntityDocuments(tabIndex);
    return documents.map((doc) => doc.store_id).every((valid_store_id) => !!valid_store_id);
  };

  getEntityDocuments = (tabIndex) => {
    switch (this.state.tabs.find((tab) => tab.index === tabIndex).value) {
      case 'address_proof':
        return this.state.documents.filter((document) => {
          if (
            document.master_documents
              .map((masterDoc) => masterDoc.external_service_name)
              .includes('KYC')
          ) {
            return document.entity_type === 'APPLICANT';
          } else {
            return false;
          }
        });
      case 'business_proof':
        return this.state.documents.filter((document) => {
          if (
            document.master_documents
              .map((masterDoc) => masterDoc.external_service_name)
              .includes('KYC')
          ) {
            return document.entity_type === 'BUSINESS';
          } else {
            return false;
          }
        });
      case 'financial_proof':
        return this.state.documents.filter((document) =>
          document.master_documents
            .map((masterDoc) => masterDoc.external_service_name)
            .includes('FDS'),
        );
    }
  };

  getConfiguration = () => {
    const { loanApplicationDetails } = this.props;
    if (!loanApplicationDetails.meta) return [];

    return loanApplicationDetails.meta.configuration;
  };

  getTabs = () => this.getConfiguration().getRequiredDocumentEntities();

  deriveFormData = () => {
    const { loanApplicationDetails } = this.props;
    if (!loanApplicationDetails || !loanApplicationDetails.document_groups.data.document_groups)
      return;

    if (!loanApplicationDetails.meta.data.application.documents) return;

    const { document_groups } = loanApplicationDetails.document_groups.data;

    const applicationDocuments = this.props.loanApplicationDetails.meta.data.application.documents;
    const documents = applicationDocuments.reduce((acc, applicationDocument) => {
      const documentGroup = document_groups.find(
        (docGroup) => docGroup.document_group.id === applicationDocument.document_group_id,
      );
      if (
        documentGroup.master_documents &&
        documentGroup.master_documents.length > 0 &&
        !documentGroup.master_documents.map((d) => d.external_service_name).includes('BUREAU')
      ) {
        const isFDSDocument = documentGroup.master_documents
          .map((d) => d.external_service_name)
          .includes('FDS');
        return [
          ...acc,
          {
            store_id: applicationDocument.store_id,
            document_masters_id: applicationDocument.document_masters_id
              ? applicationDocument.document_masters_id
              : documentGroup.master_documents[0].id,
            id: applicationDocument.id,
            acceptDocumentTypes: isFDSDocument ? ['pdf'] : ['png', 'jpg', 'jpeg', 'pdf'],
            maxDocumentSize: isFDSDocument ? '5242880' : null,
            entity_type: applicationDocument.entity_type,
            document_group: documentGroup.document_group,
            master_documents: documentGroup.master_documents,
            documentUploadOptions: isFDSDocument ? ['perfios', 'native_upload'] : ['native_upload'],
          },
        ];
      }
      return acc;
    }, []);
    this.setState((prevState) => ({
      documents,
      selectedUploadModes: documents.reduce((acc, curr) => {
        return {
          ...acc,
          [curr.id]:
            this.getBankStatementDetails().document_id === curr.id
              ? this.getConfiguration().ui.product.allowPerfios
                ? curr.documentUploadOptions[0]
                : curr.documentUploadOptions[1]
              : curr.documentUploadOptions[0],
        };
      }, {}),
      tabs: this.getTabs(),
      uploadModesMeta: {
        ...prevState.uploadModesMeta,
        perfios: this.getConfiguration().ui.product.allowPerfios
          ? {
              ...prevState.uploadModesMeta.perfios,
              title: 'Use Netbanking',
              disabled: false,
              hint: <NetbankingHint />,
              description: 'We will be redirecting you to Netbanking',
            }
          : {
              ...prevState.uploadModesMeta.perfios,
              title: 'Use Netbanking',
              disabled: true,
              hint: '',
              description: 'Currently Unserviceable',
              meta: '',
            },
      },
    }));
  };

  canUpload = () => {
    return (
      this.props.loanApplicationDetails.meta.data.application.status ===
        APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING ||
      this.props.loanApplicationDetails.meta.data.application.status ===
        APPLICATION_STATES.PREVERIFICATION_FAILED
    );
  };

  getTabContent = (tabIndex) => {
    const entityDocuments = this.getEntityDocuments(tabIndex);

    return (
      <DocumentsUpload
        documents={entityDocuments}
        nextSection={this.handleNext}
        handleFileChange={this.handleFileChange}
        onRemoveFile={this.handleRemoveFile}
        canUpload={this.canUpload()}
        uploadModesMeta={this.state.uploadModesMeta}
        selectedUploadModes={this.state.selectedUploadModes}
        handleUploadModeChange={this.handleUploadModeChange}
        handleDocumentTypeChange={this.handleDocumentTypeChange}
        businessType={this.props.user.business_type}
      />
    );
  };

  handleDocumentTypeChange = (verificationDocument, masterDocumentType) => {
    this.setState((prevState) => ({
      documents: prevState.documents.map((document) => {
        if (document.id === verificationDocument.id) {
          return {
            ...document,
            document_masters_id: masterDocumentType,
          };
        }
        return document;
      }),
    }));
  };

  handleUploadModeChange = (document, selectedMode) => {
    this.setState((prevState) => ({
      selectedUploadModes: {
        ...prevState.selectedUploadModes,
        [document.id]: selectedMode,
      },
    }));
  };

  startPerfiosProcess = (redirectLinkPayload) => {
    if (redirectLinkPayload.data) {
      const {
        data: { url, payload, signature },
      } = redirectLinkPayload;
      postToUrl(url, {
        payload,
        signature,
      });
    }
  };

  getPerfiosRedirectLink = () => {
    const { user, match, product } = this.props;

    const { meta, promoter_details } = this.props.loanApplicationDetails;

    const {
      document_master_id: documentMasterId,
      document_group_id: documentGroupId,
    } = this.getBankStatementDetails();

    const loanId = meta.data.application.id;
    const applicantId = promoter_details.data.applicant.id;

    const returnUrlParams = new URLSearchParams({
      action: 'open',
      id: loanId,
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
    return getNetBankingLink({
      entity_id: applicantId,
      entity_type: 'APPLICANT',
      email: user.email,
      return_url: returnUrl,
      destination: 'netbankingFetch',
      application_id: loanId,
      document_master_id: documentMasterId,
      document_group_id: documentGroupId,
    })
      .then((response) => {
        if (response && !response.errors) {
          this.startPerfiosProcess(response);
        }
      })
      .catch((e) => {
        this.setState(
          (prevState) => ({
            perfiosRedirectErrorCount: prevState.perfiosRedirectErrorCount + 1,
          }),
          this.handlePerfiosRedirectError,
        );
      });
  };

  handlePerfiosRedirectError = () => {
    if (this.state.perfiosRedirectErrorCount >= 3) {
      this.setState((prevState) => ({
        uploadModesMeta: {
          perfios: {
            title: 'Use Netbanking',
            disabled: true,
            description: 'Currently Unserviceable',
          },
          native_upload: {
            ...prevState.uploadModesMeta.native_upload,
            disabled: false,
          },
        },
        selectedUploadModes: {
          ...prevState.selectedUploadModes,
          [this.getBankStatementDetails().document_id]: 'native_upload',
        },
      }));
    }
  };

  render() {
    const { activeTabIndex, tabs } = this.state;
    const { navigation, loanApplicationDetails } = this.props;

    if (
      loanApplicationDetails.document_groups.loading ||
      !loanApplicationDetails.meta.data.application.documents
    ) {
      return <FormSectionLoadingSkeleton />;
    }

    const { meta, context } = loanApplicationDetails;

    const { status } = meta.data.application;

    return (
      <tabbed-container class="documents-upload-container">
        <span class="title">Documents Upload</span>
        <header>
          {tabs.map((tab) => (
            <a
              key={tab.index}
              className={activeTabIndex === tab.index && 'active'}
              onClick={() => this.handleTabChange(tab.index, tab.title)}
            >
              {this.isValidTab(tab.index) ? (
                <i className="i i-check text-success" />
              ) : status === APPLICATION_STATES.PREVERIFICATION_FAILED ? (
                <i className="i i-close text-danger" />
              ) : null}
              {tab.title}
            </a>
          ))}
        </header>
        <div class="documents-upload-tabs-wrapper">
          {tabs.map((tab) => (
            <div
              key={tab.index}
              className={`documents-upload-tab ${
                this.state.activeTabIndex === tab.index ? 'active' : 'inactive'
              }`}
            >
              {this.getTabContent(tab.index)}
            </div>
          ))}
          <div className="actions pull-right flex">
            {this.state.activeTabIndex > 0 ? (
              <Button.Transparent onClick={() => this.handleFooterActions(-1)}>
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
            ) : (
              <Button.Transparent
                onClick={() => {
                  this.props._trackNavigationActions(
                    'BACK',
                    APPLICATION_STATES.CREDIT_PULL_PENDING,
                  );
                  navigation.back();
                }}
              >
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
            )}
            {this.state.activeTabIndex < tabs.length - 1 && (
              <AsyncBtn.Primary
                type="submit"
                class="m-l"
                onClick={() => this.handleFooterActions()}
              >
                Next
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            )}
            {this.state.activeTabIndex === tabs.length - 1 &&
              !isPreceedingState(status, context.activeState) && (
                <AsyncBtn.Primary
                  type="submit"
                  class="m-l"
                  onClick={() => {
                    this.props._trackNavigationActions(
                      'NEXT',
                      APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
                    );
                    navigation.next();
                  }}
                >
                  Next
                  <i className="i i-chevron-right" />
                </AsyncBtn.Primary>
              )}
            {this.state.activeTabIndex === this.state.tabs.length - 1 &&
              isPreceedingState(status, context.activeState) &&
              Object.values(this.state.selectedUploadModes).includes('perfios') && (
                <div class="m-l">
                  <AsyncBtn.Primary
                    type="submit"
                    disabled={this.state.processingPerfiosBankStatement}
                    class="no-margin"
                    onClick={() => {
                      this.props._trackNavigationActions(
                        'NEXT',
                        APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
                      );
                      return this.getPerfiosRedirectLink();
                    }}
                  >
                    Next
                    <i className="i i-chevron-right" />
                  </AsyncBtn.Primary>
                </div>
              )}
          </div>
        </div>
      </tabbed-container>
    );
  }
}

export default PreVerificationUpload;
