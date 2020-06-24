import React, { Component } from 'react';
import { connect } from 'react-redux';
import DocumentsUpload from '../components/DocumentsUpload';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  uploadPreVerificationDocuments,
  uploadBankStatement,
  fetchLoanApplicationMeta,
  getNetBankingLink,
  changeActiveState,
} from 'merchant/reducers/capital';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import FormSectionLoadingSkeleton from '../components/FormSectionLoadingSkeleton';
import { APPLICATION_STATES } from '../constants';
import { isPreceedingState } from '../utils';

const createFormData = (form = {}) => {
  let formData = new FormData();

  Object.keys(form).map(key => {
    formData.append(key, form[key]);
  });
  return formData;
};

export const toBase64 = file =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = error => reject(error);
  });

@connect(
  state => ({
    user: state.session.user,
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    uploadPreVerificationDocuments,
    uploadBankStatement,
    fetchLoanApplicationMeta,
    changeActiveState,
    ...NotificationActions,
  }
)
class PreVerificationUpload extends Component {
  constructor(props) {
    super(props);
    this.state = {
      activeTabIndex: 0,
      documents: [],
    };

    this.tabs = [
      {
        index: 0,
        title: 'Address Proof',
        value: 'address_proof',
      },
      {
        index: 1,
        title: 'Business Proof',
        value: 'business_proof',
      },
      {
        index: 2,
        title: 'Bank Statement',
        value: 'financial_proof',
      },
    ];

    this.uploadModesMeta = {
      native_upload: {
        title: 'Upload from this Device',
        description: 'Drag and upload the document here',
        disabled: false,
      },
      native_xml_upload: {
        title: 'Upload from this Device',
        description: 'Drag or upload last 6 months bank statements',
        disabled: false,
      },
      perfios: {
        title: 'Use Netbanking',
        disabled: true,
        description: 'Currently Unservicable',
      },
    };
  }

  componentDidMount() {
    this.deriveFormData();
  }

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
    this.setState(prevState => ({
      activeTabIndex: prevState.activeTabIndex + indexChangeBy,
    }));
  };

  handleTabChange = targetTab => {
    this.setState({
      activeTabIndex: targetTab,
    });
  };

  getDocumentProperty = (documentId, master_document_id, property) => {
    const { meta, document_groups } = this.props.loanApplicationDetails;
    const documentGroups = document_groups.data.document_groups;
    const documentGroupId = meta.data.application.documents.find(
      doc => doc.id === documentId
    ).document_group_id;
    if (documentGroupId) {
      const selectedDocGroup = documentGroups.find(
        docGroup => docGroup.document_group.id === documentGroupId
      );
      return selectedDocGroup.master_documents.find(
        master_doc => master_doc.id === master_document_id
      )[property];
    }
  };

  getStoreType = (docId, masterDocId) => {
    const externalServiceType = this.getDocumentProperty(
      docId,
      masterDocId,
      'external_service_name'
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
        console.error(
          'Unknown external service type detected: ',
          externalServiceType
        );
    }
  };

  uploadToUfh = (documentId, file, progressTracker) => {
    const document = this.state.documents.find(doc => doc.id === documentId);

    const documentType = this.getDocumentProperty(
      documentId,
      document.document_masters_id,
      'type'
    );

    const {
      promoter_details,
      business_details,
    } = this.props.loanApplicationDetails;

    const isAadhaarDocument = documentType === 'aadhaar';

    const data = {
      file,
      display_name: file.name,
      name: file.name,
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
        .then(response => {
          if (response.errors) {
            reject(response);
          }
          const storeType = this.getStoreType(
            documentId,
            document.document_masters_id
          );
          this.props.uploadPreVerificationDocuments({
            application_id: this.props.loanApplicationDetails.meta.data
              .application.id,
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
        .catch(e => {
          this.props.showNotification({
            type: 'error',
            message: 'Occurred a problem while uploading the document.',
          });
          console.log('Error', e);
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
      .then(response => {
        if (!response.errors) {
          return this.props.fetchLoanApplicationMeta(
            this.props.loanApplicationDetails.meta.data.application.id
          );
        }
      })
      .catch(e => {
        this.props.showNotification({
          type: 'error',
          message: 'Occurred a problem while uploading the document.',
        });
        console.log('Error', e);
        reject(e);
      });
  };

  handleFileChange = (documentId, file, progressTracker) => {
    const document = this.state.documents.find(doc => doc.id === documentId);
    const externalServiceType = this.getDocumentProperty(
      document.id,
      document.document_masters_id,
      'external_service_name'
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
        console.error(
          'Unknown external service type detected: ',
          externalServiceType
        );
    }
  };

  handleRemoveFile = documentId => {
    this.setState(prevState => ({
      documents: prevState.documents.map(doc => {
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

  isValidTab = tabIndex => {
    const documents = this.getEntityDocuments(tabIndex);
    return documents
      .map(doc => doc.store_id)
      .every(valid_store_id => !!valid_store_id);
  };

  getEntityDocuments = tabIndex => {
    switch (this.tabs.find(tab => tab.index === tabIndex).value) {
      case 'address_proof':
        return this.state.documents.filter(document => {
          if (
            document.master_documents
              .map(masterDoc => masterDoc.external_service_name)
              .includes('KYC')
          ) {
            return document.entity_type === 'APPLICANT';
          } else {
            return false;
          }
        });
      case 'business_proof':
        return this.state.documents.filter(document => {
          if (
            document.master_documents
              .map(masterDoc => masterDoc.external_service_name)
              .includes('KYC')
          ) {
            return document.entity_type === 'BUSINESS';
          } else {
            return false;
          }
        });
      case 'financial_proof':
        return this.state.documents.filter(document =>
          document.master_documents
            .map(masterDoc => masterDoc.external_service_name)
            .includes('FDS')
        );
    }
  };

  deriveFormData = () => {
    if (!this.props.loanApplicationDetails.document_groups.data.document_groups)
      return;

    if (!this.props.loanApplicationDetails.meta.data.application.documents)
      return;

    const {
      document_groups,
    } = this.props.loanApplicationDetails.document_groups.data;

    const applicationDocuments = this.props.loanApplicationDetails.meta.data
      .application.documents;
    const documents = applicationDocuments.reduce(
      (acc, applicationDocument) => {
        const documentGroup = document_groups.find(
          docGroup =>
            docGroup.document_group.id === applicationDocument.document_group_id
        );
        if (
          documentGroup.master_documents &&
          documentGroup.master_documents.length > 0 &&
          !documentGroup.master_documents
            .map(d => d.external_service_name)
            .includes('BUREAU')
        ) {
          const isFDSDocument = documentGroup.master_documents
            .map(d => d.external_service_name)
            .includes('FDS');
          return [
            ...acc,
            {
              store_id: applicationDocument.store_id,
              document_masters_id: applicationDocument.document_masters_id
                ? applicationDocument.document_masters_id
                : documentGroup.master_documents[0].id,
              id: applicationDocument.id,
              acceptDocumentTypes: isFDSDocument
                ? ['pdf']
                : ['png', 'jpg', 'jpeg', 'pdf'],
              maxDocumentSize: isFDSDocument ? '5242880' : null,
              entity_type: applicationDocument.entity_type,
              document_group: documentGroup.document_group,
              master_documents: documentGroup.master_documents,
              documentUploadOptions: isFDSDocument
                ? ['perfios', 'native_upload']
                : ['native_upload'],
            },
          ];
        }
        return acc;
      },
      []
    );
    this.setState({
      documents,
      selectedUploadModes: documents.reduce((acc, curr) => {
        return {
          ...acc,
          [curr.id]: curr.documentUploadOptions.slice(-1)[0],
        };
      }, {}),
    });
  };

  canUpload = () => {
    return (
      this.props.loanApplicationDetails.meta.data.application.status ===
        APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING ||
      this.props.loanApplicationDetails.meta.data.application.status ===
        APPLICATION_STATES.PREVERIFICATION_FAILED
    );
  };

  getTabContent = tabIndex => {
    const entityDocuments = this.getEntityDocuments(tabIndex);

    return (
      <DocumentsUpload
        documents={entityDocuments}
        nextSection={this.handleNext}
        handleFileChange={this.handleFileChange}
        onRemoveFile={this.handleRemoveFile}
        canUpload={this.canUpload()}
        uploadModesMeta={this.uploadModesMeta}
        selectedUploadModes={this.state.selectedUploadModes}
        handleUploadModeChange={this.handleUploadModeChange}
        handleDocumentTypeChange={this.handleDocumentTypeChange}
      />
    );
  };

  handleDocumentTypeChange = (verificationDocument, masterDocumentType) => {
    this.setState(prevState => ({
      documents: prevState.documents.map(document => {
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
    this.setState(
      prevState => ({
        selectedUploadModes: {
          ...prevState.selectedUploadModes,
          [document.id]: selectedMode,
        },
      }),
      () => {
        if (selectedMode === 'perfios') {
          //TODO:show modal first for confirmation before redirection
          //TODO:write in switch case
          const { user } = this.props;

          const { meta, promoter_details } = this.props.loanApplicationDetails;

          getNetBankingLink({
            entity_id: promoter_details.data.applicant.id,
            entity_type: 'APPLICANT',
            email: user.email,
            return_url: window.location.href,
            destination: 'netbankingFetch',
            application_id: meta.data.application.id,
            document_masters_id: document.master_documents[0].id,
            document_master_id: document.master_documents[0].id,
            document_group_id: document.document_group.id,
          })
            .then(response => {
              if (response && !response.errors) {
                // ajax(
                //   {
                //     url: response.data.location,
                //     method: 'GET',
                //     headers: {
                //
                //     },
                //   },
                //   {},
                //   '/merchant/api'
                // )
                window.document.cookie = response.data.set_cookie;
                window.open(response.data.location, '_blank');
              }
            })
            .catch(e => {
              //TODO: handle errors
            });
        }
        //TODO:Handle external actions like redirecting to perfios
      }
    );
  };

  render() {
    const { activeTabIndex } = this.state;

    if (
      this.props.loanApplicationDetails.document_groups.loading ||
      !this.props.loanApplicationDetails.meta.data.application.documents
    ) {
      return <FormSectionLoadingSkeleton />;
    }

    const { status } = this.props.loanApplicationDetails.meta.data.application;

    return (
      <tabbed-container class="documents-upload-container">
        <span class="title">Documents Upload</span>
        <header>
          {this.tabs.map(tab => (
            <a
              className={activeTabIndex === tab.index && 'active'}
              onClick={() => this.handleTabChange(tab.index)}
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
          {this.tabs.map(tab => (
            <div
              className={`documents-upload-tab ${
                this.state.activeTabIndex === tab.index ? 'active' : 'inactive'
              }`}
            >
              {this.getTabContent(tab.index)}
            </div>
          ))}
          <div className="actions pull-right">
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
                    APPLICATION_STATES.CREDIT_PULL_PENDING
                  );
                  this.props.changeActiveState(
                    APPLICATION_STATES.CREDIT_PULL_PENDING
                  );
                }}
              >
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
            )}
            {this.state.activeTabIndex < this.tabs.length - 1 && (
              <AsyncBtn.Primary
                type="submit"
                class="m-l"
                onClick={() => this.handleFooterActions()}
              >
                Next
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            )}
            {this.state.activeTabIndex === this.tabs.length - 1 &&
              !isPreceedingState(
                status,
                APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS
              ) && (
                <AsyncBtn.Primary
                  type="submit"
                  class="m-l"
                  onClick={() => {
                    this.props.changeActiveState(
                      APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS
                    );
                    this.props._trackNavigationActions(
                      'NEXT',
                      APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS
                    );
                  }}
                >
                  Next
                  <i className="i i-chevron-right" />
                </AsyncBtn.Primary>
              )}
          </div>
        </div>
      </tabbed-container>
    );
  }
}

export default PreVerificationUpload;
