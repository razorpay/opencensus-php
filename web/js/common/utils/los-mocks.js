export const mockCreateApplication = (data, loanId) => {
  console.log('Creating LOAN', data, loanId);
  return new Promise(resolve => {
    setTimeout(() => {
      const application_id = loanId || `loan-${Date.now()}`;
      const merchant_id = data.merchant_id;
      let applicationMeta = {};
      if (data.application) {
        applicationMeta = {
          ...data,
          application: {
            id: application_id,
            status: 'BUSINESS_INFO_PENDING',
            ...data.application,
          },
        };
      } else {
        applicationMeta = {
          application: {
            id: application_id,
            status: 'BUSINESS_INFO_PENDING',
            ...data,
          },
        };
      }

      localStorage.setItem(application_id, JSON.stringify(applicationMeta));

      resolve(applicationMeta);
    }, 2000);
  });
};

export const mockFetchBusinessDetails = ({ business_id }) => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve(JSON.parse(localStorage.getItem(business_id)));
    }, 2000);
  });
};

export const mockFetchPromoterDetails = ({ applicant_id }) => {
  return new Promise(resolve => {
    setTimeout(() => {
      const x = JSON.parse(localStorage.getItem(applicant_id));
      console.log('PROMTER_DETAILS', x);
      resolve(x);
    }, 2000);
  });
};

export const mockSaveBusinessDetails = data => {
  const businessId = data.business.id || `business-${Date.now()}`;
  const businessData = {
    ...data,
    business: {
      ...data.business,
      id: businessId,
    },
  };
  localStorage.setItem(businessId, JSON.stringify(businessData));
  return new Promise(resolve => {
    setTimeout(() => {
      resolve(businessData);
    }, 2000);
  });
};

export const mockSavePromoterDetails = data => {
  const applicantId = data.applicant.id || `promoter-${Date.now()}`;
  data['applicant']['id'] = applicantId;
  localStorage.setItem(applicantId, JSON.stringify(data));
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        ...data,
        applicant: {
          ...data.applicant,
          id: applicantId,
        },
      });
    }, 2000);
  });
};

export const mockSave = (loanId, dataType, data) => {
  const key = `${loanId}-${dataType}`;
  console.log('mock save', data, dataType, key);
  return new Promise((resolve, reject) => {
    localStorage.setItem(key, JSON.stringify(data));
    setTimeout(() => {
      resolve(data);
    }, 2000);
  });
};

export const mockGet = (loanId, dataType = undefined) => {
  return new Promise((resolve, reject) => {
    if (dataType) {
      const key = `${loanId}-${dataType}`;
      const loanStateData = localStorage.getItem(key);
      if (loanStateData) resolve(JSON.parse(loanStateData));
      setTimeout(() => {
        resolve({});
      }, 2000);
    } else {
      const loan = localStorage.getItem(loanId);
      setTimeout(() => {
        if (loan) resolve(JSON.parse(loan));

        resolve({
          status: 'business_info',
          application_id: null,
          applicant_id: null,
          business_id: null,
        });
      }, 2000);
    }
  });
};

export const mockSubmitOtp = data => {
  const id = `bureau-${Date.now()}`;
  const reportData = {
    d2c_bureau_report: {
      id: id,
      provider: 'experian',
      score: 752,
      report: {
        active_accounts: '1',
        closed_accounts: '1',
        count_of_accounts: '2',
        secured_account_outstanding_balance: '152000',
        total_outstanding_balance: '152000',
        un_secured_account_outstanding_balance: '0',
      },
      created_at: 1587506682,
    },
  };
  localStorage.setItem(id, JSON.stringify(reportData));
  return new Promise(resolve => {
    setTimeout(() => {
      resolve(reportData);
    }, 2000);
  });
};

export const mockFetchBureauReport = data => {
  const reportData = localStorage.getItem(data.d2c_bureau_report_id);

  return new Promise(resolve => {
    setTimeout(() => {
      resolve(JSON.parse(reportData));
    }, 2000);
  });
};

export const mock = data => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve(data);
    }, 2000);
  });
};

export const mockUFHUpload = (data, url) => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        file_store_id: Date.now(),
      });
    }, 2000);
  });
};

export const mockLOSUploadDocuments = data => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        documents: [
          {
            application_id: 'sampleAppId123',
            entity_id: 'sampleEntityId123',
            entity_type: 'Business',
            DocumentList: [
              {
                document_id: 'SampleDocId1',
                document_master_id: 'SampleDocMasterId1',
                document_name: 'AADHAAR',
                store_id: 'x123',
                store_type: 'UFH',
                status: 'approval_pending',
              },
              {
                document_id: 'SampleDocId2',
                document_master_id: 'SampleDocMasterId2',
                document_name: 'DL',
                store_id: 'UFH',
                store_type: 'file_aabbcc',
                status: 'approval_pending',
              },
            ],
          },
          {
            application_id: 'sampleAppId123',
            entity_id: 'sampleEntityId123',
            entity_type: 'Applicant',
            DocumentList: [
              {
                document_id: 'SampleDocId31',
                document_master_id: 'SampleDocMasterId1',
                document_name: 'AADHAAR',
                store_id: 'UFH',
                store_type: 'file_dllddl',
                status: 'approval_pending',
              },
              {
                document_id: 'SampleDocId32',
                document_master_id: 'SampleDocMasterId13',
                document_name: 'PASSPORT',
                store_id: 'UFH',
                store_type: 'file_ppppoo',
                status: 'approval_pending',
              },
            ],
          },
        ],
      });
    }, 2000);
  });
};

export const mockDocumentAction = data => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        documents: [
          {
            application_id: 'sampleAppId123',
            entity_id: 'sampleEntityId123',
            entity_type: 'Business',
            DocumentList: [
              {
                document_id: 'SampleDocId1',
                document_master_id: 'SampleDocMasterId1',
                document_name: 'AADHAAR',
                store_id: 'x123',
                store_type: 'UFH',
                status: 'approved',
              },
              {
                document_id: 'SampleDocId2',
                document_master_id: 'SampleDocMasterId2',
                document_name: 'DL',
                store_id: 'UFH',
                store_type: 'file_aabbcc',
                status: 'approval_pending',
              },
            ],
          },
          {
            application_id: 'sampleAppId123',
            entity_id: 'sampleEntityId123',
            entity_type: 'Applicant',
            DocumentList: [
              {
                document_id: 'SampleDocId31',
                document_master_id: 'SampleDocMasterId1',
                document_name: 'AADHAAR',
                store_id: 'UFH',
                store_type: 'file_dllddl',
                status: 'approved',
              },
              {
                document_id: 'SampleDocId32',
                document_master_id: 'SampleDocMasterId13',
                document_name: 'PASSPORT',
                store_id: 'UFH',
                store_type: 'file_ppppoo',
                status: 'approval_pending',
              },
            ],
          },
        ],
      });
    }, 2000);
  });
};

export const mockFetchDocumentGroups = () => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        documents: [
          {
            application_id: 'sampleAppId123',
            entity_id: 'sampleEntityId123',
            entity_type: 'Business',
            DocumentList: [
              {
                document_master_id: 'SampleDocMasterId1',
                document_name: 'AADHAAR',
                status: 'approval_pending',
              },
              {
                document_id: 'SampleDocId2',
                document_master_id: 'SampleDocMasterId2',
                document_name: 'DL',
                store_id: 'UFH',
                store_type: 'file_aabbcc',
                status: 'approval_pending',
              },
            ],
          },
          {
            application_id: 'sampleAppId123',
            entity_id: 'sampleEntityId123',
            entity_type: 'Applicant',
            DocumentList: [
              {
                document_master_id: 'SampleDocMasterId1',
                document_name: 'AADHAAR',
                status: 'approval_pending',
              },
              {
                document_id: 'SampleDocId32',
                document_master_id: 'SampleDocMasterId13',
                document_name: 'PASSPORT',
                store_id: 'UFH',
                store_type: 'file_ppppoo',
                status: 'approval_pending',
              },
            ],
          },
        ],
      });
    }, 2000);
  });
};

export const mockCreateBureauDetails = data => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        d2c_bureau_detail: {
          id: 'd2cbd_EhGCOuZVu8SN0n',
          first_name: 'john',
          last_name: 'doe',
          date_of_birth: '1995-06-27',
          contact_mobile: '8002198483',
          email: 'test@razorpay.com',
          address: 'h no 12, near blah station',
          city: 'bangalore',
          state: 'KA',
          pincode: '560030',
          pan: 'HHNPS2908L',
          created_at: 1587499286,
        },
      });
    }, 2000);
  });
};

export const mockAttachApplicationDetailsToBusiness = data => {
  const businessId = data.business.id;
  const businessData = JSON.parse(localStorage.getItem(businessId));
  console.log({ businessData, data });
  businessData['applicant_ids'] = data.applicant_ids;
  localStorage.setItem(businessId, JSON.stringify(businessData));

  return new Promise(resolve => {
    setTimeout(() => {
      resolve(businessData);
    }, 2000);
  });
};

export const mockSaveCreditOffer = data => {
  const creditOfferId = data.id || `credit-offer-${Date.now()}`;
  const offersData = {
    credit_offer: {
      ...data,
      id: creditOfferId,
    },
    accepted: false,
  };
  const offers = localStorage.getItem('credit-offers')
    ? JSON.parse(localStorage.getItem('credit-offers'))
    : [];
  localStorage.setItem(
    'credit-offers',
    JSON.stringify([...offers, offersData])
  );
  return new Promise(resolve => {
    setTimeout(() => {
      resolve(offersData);
    }, 2000);
  });
};

export const mockFetchCreditOffers = applicationDetails => {
  const offers = localStorage.getItem('credit-offers')
    ? JSON.parse(localStorage.getItem('credit-offers'))
    : [];
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        offers,
      });
    }, 2000);
  });
};

export const mockGetOtpToken = data => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        token: Date.now(),
      });
    }, 2000);
  });
};

export const mockAcceptLoanOffer = ({ credit_offer_id }) => {
  const offers = JSON.parse(localStorage.getItem('credit-offers'));
  const offersAfterAccepted = offers.map(offer => {
    if (offer.credit_offer.id === credit_offer_id) {
      return {
        ...offer,
        accepted: true,
      };
    }
    return offer;
  });
  localStorage.setItem('credit-offers', JSON.stringify(offersAfterAccepted));

  return new Promise(resolve => {
    setTimeout(() => {
      resolve(offersAfterAccepted);
    }, 2000);
  });
};

export const mockGetNach = () => {
  return mockCreateNach();
};

export const mockCreateNach = payload => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        nach: {
          id: 'EjtR2jD91coIR1',
          entity_id: 'testingEntityID1',
          entity_type: 'Applicant1',
          application_id: 'testingID1',
          token: {
            nach: {
              prefilled_form: 'https://rzp.io/i/cKpjgej',
              upload_form_url: 'https://rzp.io/i/AFc9e5o',
            },
            method: 'nach',
          },
          papernach_customer_id: 'cust_EhaGjOfIqQR8t0',
          order_id: 'order_EjtQztTvIvANgr',
        },
      });
    }, 2000);
  });
};

export const mockUploadNach = data => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        nach: {
          id: 'EhU1IRXgfwY5Q2',
          entity_id: 'testingEntityID1',
          entity_type: 'Applicant1',
          application_id: 'testingID1',
          token: {
            nach: {
              prefilled_form: 'www.goo',
              upload_form_url: 'www.goo2',
            },
            method: 'nach',
          },
          amount: 45323,
          papernach_customer_id: 'cust_EfXqncUKLX76Vl',
          order_id: 'orderid',
          file_store_id: 'ufh12314',
        },
      });
    }, 2000);
  });
};

export const mockGetESignUrl = () => {
  return new Promise(resolve => {
    setTimeout(() => {
      const url = 'https://dosign.io/doc/xg7n301';
      resolve({
        sign_url: null,
      });
    }, 2000);
  });
};

export const mockGetInvitationDetails = (_, isCreated) => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        invitations: [
          {
            applicant_id: '10000000000000',
            sign_url:
              'https://sandbox.leegality.com/sign/5dd48ae2-46d8-4abd-83a6-2c24e8bb780c',
            sign_status: 'SIGN_PENDING',
          },
        ],
        sign_status: 'SIGN_PENDING',
        expiry_time: '1588777198',
      });
    }, 2000);
  });
};

export const mockGetAgreementDocumentDetails = () => {
  return new Promise(resolve => {
    setTimeout(() => {
      resolve({
        ufhIds: {
          agreement: 'EjqzSjIyvIwpX9',
          audit_trail: 'EjqzSjLaTQaNnL',
        },
        sign_status: 'SIGNED',
      });
    }, 2000);
  });
};
