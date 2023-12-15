import GenericEntity from 'merchant/models/GenericEntity';
import { merchantFetch } from 'merchant/utils/ajax';
import { isLoanProduct } from 'merchant/views/Capital/utils';

export default class BaseOriginationEntity extends GenericEntity {
  request = (url, data, progressTracker) => {
    return merchantFetch({
      url,
      mode: 'live',
      method: 'post',
      data,
      headers: {
        'Content-Type': 'application/json',
      },
      onUploadProgress: progressTracker,
    });
  };

  resourceUrlPrefix(domain, entity, endpoint) {
    return `los/service/twirp/rzp.capital.los.${domain}.v1.${entity}/${endpoint}`;
  }

  fetchSeedData() {
    return this.request(
      `${this.resourceUrlPrefix('origination', 'ApplicationAPI', 'GetSeedInfo')}`,
      {},
    );
  }

  fetchProducts() {
    return this.request(`${this.resourceUrlPrefix('admin', 'ProductAPI', 'GetProducts')}`, {});
  }

  fetchLoanApplicationMeta(applicationId) {
    return this.request(
      `${this.resourceUrlPrefix('origination', 'ApplicationAPI', 'GetApplication')}`,
      {
        application_id: applicationId,
      },
    );
  }

  fetchLoanApplicationByParam({ application_id }) {
    return this.request(
      `${this.resourceUrlPrefix('origination', 'ApplicationAPI', 'GetApplicationNew')}`,
      {
        application_id,
      },
    );
  }

  saveApplicationDetails(payload) {
    const applicationExists = Boolean(payload.id);
    return this.request(
      `${this.resourceUrlPrefix(
        'origination',
        'ApplicationAPI',
        applicationExists ? 'UpdateApplication' : 'CreateApplication',
      )}`,
      payload,
    );
  }

  fetchBusinessDetails(data) {
    return this.request(
      `${this.resourceUrlPrefix('client', 'BusinessAPI', 'GetBusinessDetails')}`,
      data,
    );
  }

  getBusinessDetailsByMerchantId(data) {
    return this.request(
      `${this.resourceUrlPrefix('client', 'BusinessAPI', 'GetBusinessDetailsByReferenceID')}`,
      data,
    );
  }
  saveBusinessDetails(payload) {
    const businessExists = Boolean(payload.business.id);

    return this.request(
      `${this.resourceUrlPrefix(
        'client',
        'BusinessAPI',
        businessExists ? 'UpdateBusinessDetails' : 'CreateBusiness',
      )}`,
      payload,
    );
  }

  fetchApplicantDetails(data) {
    return this.request(
      `${this.resourceUrlPrefix('client', 'ApplicantAPI', 'GetApplicant')}`,
      data,
    );
  }

  saveApplicantDetails(payload) {
    const applicantExists = Boolean(payload.applicant.id);
    return this.request(
      `${this.resourceUrlPrefix(
        'client',
        'ApplicantAPI',
        applicantExists ? 'UpdateApplicant' : 'CreateApplicant',
      )}`,
      payload,
    );
  }

  fetchDocumentGroups(isProprietorshipBusiness) {
    return this.request(
      `${this.resourceUrlPrefix('admin', 'DocumentsAPI', 'GetDocumentGroups')}`,
    ).then((data) => {
      if (isProprietorshipBusiness) {
        data.data.document_groups.forEach((d) => {
          if (d.document_group.name === 'business_registration_proof') {
            d.master_documents = d.master_documents.filter(
              (doc) =>
                doc.type !== 'partnership_deed' &&
                doc.type !== 'llp_certificate' &&
                doc.type !== 'certificate_of_incorporation',
            );
          }
        });
      }
      return data;
    });
  }

  getApplications(data) {
    const payload = {
      limit: 10,
      ...data,
      active_product_id: data.product_id,
    };
    payload.product_id = undefined;
    return this.request(
      `${this.resourceUrlPrefix('origination', 'ApplicationAPI', 'ListOrSearch')}`,
      payload,
    );
  }

  uploadPreVerificationDocuments(data) {
    return this.request(
      `${this.resourceUrlPrefix('origination', 'ApplicationAPI', 'UploadDocuments')}`,
      data,
    );
  }

  uploadBankStatement(data, progressTracker) {
    return this.request(
      `${this.resourceUrlPrefix('fds', 'FDSBankStatementAPI', 'UploadXML')}`,
      data,
      progressTracker,
    );
  }

  fetchCreditOffers(data, product) {
    return this.request(
      `${this.resourceUrlPrefix(
        'admin',
        isLoanProduct(product) ? 'CreditOfferAPI' : 'LocCreditOfferAPI',
        isLoanProduct(product) ? 'GetAllOffers' : 'GetAllLocOffers',
      )}`,
      data,
    );
  }

  acceptCreditOffer = (data, product) => {
    return this.request(
      `${this.resourceUrlPrefix(
        'admin',
        isLoanProduct(product) ? 'CreditOfferAPI' : 'LocCreditOfferAPI',
        isLoanProduct(product) ? 'AcceptOffer' : 'AcceptLocOffer',
      )}`,
      data,
    );
  };

  getAcceptedOffer = (data) => {
    return this.request(
      `${this.resourceUrlPrefix('contracts', 'ContractsAPI', 'GetApplicationContracts')}`,
      data,
    );
  };

  getAgreementStatus = (data) => {
    return this.request(
      `${this.resourceUrlPrefix('contracts', 'DocSignAPI', 'CheckAgreementStatus')}`,
      data,
    );
  };

  getLegalAgreementUrl = (data) => {
    return this.request(
      `${this.resourceUrlPrefix('contracts', 'DocSignAPI', 'FetchLegalAgreement')}`,
      data,
    );
  };

  getNach = (data) => {
    return this.request(
      `${this.resourceUrlPrefix('nach', 'NachAPI', 'GetNachByApplication')}`,
      data,
    );
  };

  uploadNach = (payload) => {
    return this.request(`${this.resourceUrlPrefix('nach', 'NachAPI', 'UploadNachForm')}`, payload);
  };

  getNetBankingLink(data) {
    return this.request(
      `${this.resourceUrlPrefix('fds', 'FDSBankStatementAPI', 'GetNetBankingPayload')}`,
      data,
    );
  }

  processBankStatement(data) {
    return this.request(
      `${this.resourceUrlPrefix('fds', 'FDSBankStatementAPI', 'ProcessBankStatement')}`,
      data,
    );
  }

  submitOtp(data) {
    return this.request(
      `${this.resourceUrlPrefix('origination.d2c', 'D2CBureauAPI', 'SubmitOtp')}`,
      data,
    );
  }

  fetchD2cReport(data) {
    return this.request(
      `${this.resourceUrlPrefix('origination.d2c', 'D2CBureauAPI', 'GetBureauReport')}`,
      data,
    );
  }

  createNach(payload) {
    return this.request(`${this.resourceUrlPrefix('nach', 'NachAPI', 'CreateNach')}`, payload);
  }

  scheduleVerification = (payload) => {
    return this.request(
      `${this.resourceUrlPrefix('admin', 'OfferVerificationAPI', 'ScheduleMerchantVerification')}`,
      payload,
    );
  };

  getLender = (data) => {
    return this.request(`${this.resourceUrlPrefix('admin', 'LenderAPI', 'GetLender')}`, data);
  };

  getDisbursalDetails = (data) => {
    return this.request(`${this.resourceUrlPrefix('admin', 'DisbursalAPI', 'GetDisbursal')}`, data);
  };

  getScheduleDetails = (data) => {
    return this.request(
      `${this.resourceUrlPrefix('admin', 'OfferVerificationAPI', 'GetScheduleDetail')}`,
      data,
    );
  };

  getOfferVerificationTasks = (data) => {
    return this.request(
      `${this.resourceUrlPrefix('admin', 'OfferVerificationAPI', 'GetOfferVerificationTasks')}`,
      data,
    );
  };
}
