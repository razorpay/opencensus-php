import React, { Component } from 'react';
import { connect } from 'react-redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import { states } from 'merchant/helpers/data';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';
import { showNotification } from 'merchant_common/reducers/notifications';

import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import { isPreceedingState } from '../../utils';
import { APPLICATION_STATES, BUSINESS_TYPES, VERIFICATION_TIME_SLOTS } from '../constants';

const DOCUMENT_TYPE_LABELS = {
  business: 'Business',
  personal: 'Personal',
};

class DocumentCollectionInformation extends Component {
  getMasterDocument = (masterDocId) => {
    const { loanApplicationDetails } = this.props;
    const { offer_tasks: offerVerificationTasks } = loanApplicationDetails.vnv_details.data;
    const { document_groups } = loanApplicationDetails;
    const taskExists = offerVerificationTasks.map((task) => task.entity_id).includes(masterDocId);

    if (!taskExists) return;

    const masterDocuments = document_groups.data.document_groups.reduce(
      (acc, docGroup) => (docGroup.master_documents ? [...acc, ...docGroup.master_documents] : acc),
      [],
    );

    return masterDocuments.find((doc) => doc.id === masterDocId);
  };

  getOtherVerificationDocument = (document) => {
    if (!document) return null;

    const otherDocumentsLabels = {
      dcol_nach: 'Nach Document',
    };
    return otherDocumentsLabels[document.task_code];
  };

  getDocuments = () => {
    const { loanApplicationDetails, user } = this.props;

    const { lender_details, meta } = loanApplicationDetails;

    const lenderDetails = lender_details.data.lender;
    const lenderProduct = lenderDetails.products.find(
      (product) => product.id === meta.data.application.product_id,
    );
    const productVerificationRequirements =
      lenderProduct.attributes.required_document_groups.verification_requirements;
    const businessType = BUSINESS_TYPES[parseInt(user.business_type)];
    const businessSpecificRequirements = productVerificationRequirements.find(
      (requirement) => requirement.deed_type === businessType,
    );
    const requiredDocuments = {
      personal: businessSpecificRequirements.applicant_verification_requirements,
      business: businessSpecificRequirements.business_verification_requirements,
    };

    return (
      <div className="documents-wrapper flex">
        {Object.entries(requiredDocuments).map(([documentType, documents]) => (
          <div className="section">
            {documents.map((document) => (
              <p>
                {this.getMasterDocument(document.document_master_id)
                  ? this.getMasterDocument(document.document_master_id).name ||
                    this.getMasterDocument(document.document_master_id).type
                  : this.getOtherVerificationDocument(document)}
              </p>
            ))}
          </div>
        ))}
      </div>
    );
  };

  getDocumentTypes = () => {
    const { loanApplicationDetails, user } = this.props;

    const { lender_details, meta } = loanApplicationDetails;

    const lenderDetails = lender_details.data.lender;
    const lenderProduct = lenderDetails.products.find(
      (product) => product.id === meta.data.application.product_id,
    );
    const productVerificationRequirements =
      lenderProduct.attributes.required_document_groups.verification_requirements;
    const businessType = BUSINESS_TYPES[parseInt(user.business_type)];
    const businessSpecificRequirements = productVerificationRequirements.find(
      (requirement) => requirement.deed_type === businessType,
    );
    const requiredDocuments = {
      personal: businessSpecificRequirements.applicant_verification_requirements,
      business: businessSpecificRequirements.business_verification_requirements,
    };

    return (
      <div className="document-types flex">
        {Object.entries(requiredDocuments).map(([documentType, documents]) => (
          <div className="section">
            <p className="text-faded">{DOCUMENT_TYPE_LABELS[documentType]}</p>
          </div>
        ))}
      </div>
    );
  };
  render() {
    const { loanApplicationDetails } = this.props;

    const { lender_details, meta, schedule_details, vnv_details } = loanApplicationDetails;

    if (lender_details.loading || schedule_details.loading || vnv_details.loading) {
      return <FormLoader />;
    }

    const applicationStatus = meta.data.application.status;

    const scheduleDetails = schedule_details.data;
    const slotAddress = scheduleDetails.addresses[0].address;

    const hasDocumentsCollected = !isPreceedingState(
      applicationStatus,
      APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
    );

    return (
      <div className="verification-schedule-details">
        <div className="panel panel-default slot-details">
          <div className="panel-body no-margin full-width">
            <div className="document-types flex">
              <div className="section">
                <strong>{hasDocumentsCollected ? 'Picked at' : 'Date & Time'}</strong>
              </div>
              <div className="section">
                <strong>{hasDocumentsCollected ? 'From' : 'Address'}</strong>
              </div>
            </div>
            <div className="documents-wrapper flex slot-details-wrapper">
              <div className="section">
                <p>
                  {
                    VERIFICATION_TIME_SLOTS.find(
                      (slot) => slot.value === scheduleDetails.slot_timing,
                    ).text
                  }
                </p>
                <p>{moment(scheduleDetails.slot_date).format('ll')}</p>
              </div>
              <div className="section">
                {`${slotAddress.line_1}, ${slotAddress.city}, ${states[slotAddress.state]} - ${
                  slotAddress.pin_code
                }`}
              </div>
            </div>
          </div>
        </div>

        <div className="panel panel-default required-documents-info-container">
          <div className="panel-body no-margin full-width">
            <strong>
              {hasDocumentsCollected ? 'Documents Collected' : 'Documents To be Ready with'}
            </strong>
            {this.getDocumentTypes()}
            {this.getDocuments()}
          </div>
        </div>

        <div className="actions pull-right">
          <Button.Transparent
            onClick={() => {
              // this.props._trackNavigationActions('BACK', APPLICATION_STATES.SLOT_SELECTION_PENDING);
              this.props._trackNavigationActions('BACK', APPLICATION_STATES.CREDIT_OFFER_GENERATED);
              this.props.navigation.back();
            }}
          >
            <i className="i i-chevron-left" />
            Back
          </Button.Transparent>
          {hasDocumentsCollected && (
            <AsyncBtn.Primary
              type="submit"
              onClick={() => {
                this.props._trackNavigationActions(
                  'NEXT',
                  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
                );
                this.props.navigation.next();
              }}
            >
              Next
              <i className="i i-chevron-right" />
            </AsyncBtn.Primary>
          )}
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
    user: state.session.user,
  }),
  {
    fetchLoanApplicationMeta,
    showNotification,
  },
)(DocumentCollectionInformation);
