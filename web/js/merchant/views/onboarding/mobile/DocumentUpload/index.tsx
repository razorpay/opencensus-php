import React, { useEffect, useState, useRef } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import * as Yup from 'yup';
import { Formik, Form } from 'formik';
import Link from '@razorpay/commander-shield/src/shared/Link';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import { Select, Option } from 'common/components/Select';
import { FileUpload } from 'common/components/FileUpload';
import ESignVerification from '../ESignVerification';
import Card from 'common/components/Card';
import { FormSection, Field } from '../Form';
import { useActivationFormState, isVisible } from '../context/store';
import {
  ADDRESS_PROOF_TYPES,
  BANK_PROOF_TYPE_DOC,
  ACCEPTED_DOCUMENT,
  BUSINESS_PROOF_TYPE_DOCS,
  ADDITIONAL_DOCS_LABEL_VALUE_MAP,
  BUSINESS_PROOF_CERTIFICATE_TYPES,
  PROPRIETORSHIP,
  PARTNERSHIP,
  PRIVATE,
  PUBLIC,
  LLP,
  NOT_REGISTERED,
} from '../Constants/OnboardingConstants';
import useActivation from '../hooks/useActivation';
import {
  getAdditionalDocCount,
  getBizCatSubCatPair,
  isDocumentTabComplete,
  getDefaultSelectedDocs,
  getDocumentTitle,
} from '../services/utils';
import { analyticsTrack } from 'common/services/tracking/segment';
import ShopEstablishmentNumber from './ShopEstablishmentNumber';
import { useApp } from 'common/context/App';
import GstinAutoPopulate from '../Fields/GstinAutoPopulate';
import useGstin from '../hooks/useGstin';
import useConfigDetails from '../hooks/useConfigDetails';
import EmailVerify from '../EmailVerify';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

const Dot = styled(View)`
  height: 4px;
  width: 4px;
  background-color: ${({ theme }) => getColor(theme, 'shade.960')};
  border-radius: 50%;
`;

interface IDocumentUploadProps {
  isFormLocked?: boolean;
}

const DocumentUpload = ({ isFormLocked }: IDocumentUploadProps): React.ReactElement => {
  const { data, documentUpload, documentDelete, postData } = useActivation();
  const trackEvents = useTrackEvents();
  const {
    user,
    experiments: {
      isGstinMandatory,
      isGstinAutoPopulate,
      isSyncBankVerificationEnabled,
      isUpdatedLiteOnboarding,
      isEmailNonMandatoryOnL2Form,
      isMsmeDisabled,
      isAdharEkycRequired,
    },
  } = useApp();
  const { gstinDetails } = useGstin();
  const { data: configData } = useConfigDetails('onboarding');
  const documents = data.documents;
  const businessDetails = data.business_details;

  const hasGSTIN = useActivationFormState((state) => state.has_gstin);
  const bizCatSubCatPair = getBizCatSubCatPair(data).join('-');
  const hasNonMandatoryEmail = useActivationFormState((state) => state.has_non_mandatory_email);
  const setHasNonMandatoryEmail = useActivationFormState((state) => state.setHasNonMandatoryEmail);

  const defaultAddressDoc = getDefaultSelectedDocs(data, 'address');
  const defaultBusinessDoc = getDefaultSelectedDocs(data, 'business');
  const defaultBankDoc = getDefaultSelectedDocs(data, 'bank');
  const defaultAdditionalDoc = getDefaultSelectedDocs(data, 'additional');

  const [addressDoc, setAddressDoc] = useState<string>(defaultAddressDoc);
  const [businessDoc, setBusinessDoc] = useState<string>(defaultBusinessDoc);
  const [bankDoc, setBankDoc] = useState<string>(defaultBankDoc);
  const [additionalDoc, setAdditionalDoc] = useState<string>(defaultAdditionalDoc);
  const [progress, setProgress] = useState<number>(0);
  const [isEkycDownTime, setIsEkycDownTime] = useState<boolean>(false);
  const emailVerifyRef = useRef<HTMLDivElement>(null);
  const isEmailVerified = user?.user?.confirmed;

  const setDocumentUploadCompleted = useActivationFormState(
    (state) => state.setDocumentUploadCompleted,
  );

  const onChange = async (e: any, docType: string, formikProps) => {
    const documentType = docType.split('_').join(' '); // segment breaks if actionName has underscore
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'document upload',
      screen: 'home page',
      user,
      eventAction: 'initiated',
      properties: {
        document_type: documentType,
      },
    });
    const formData = new FormData();
    formData.append('file', e.target.files[0]);
    formData.append('document_type', docType);

    const onUploadProgress = (progressEvent) => {
      setProgress(Math.round((100 * progressEvent.loaded) / progressEvent.total));
    };

    const response = await documentUpload({ formData, progressTracker: onUploadProgress });

    if (!response) {
      formikProps.setFieldError(docType, 'something went wrong');
      analyticsTrack({
        objectName: 'SignUp',
        actionName: 'document upload',
        screen: 'home page',
        user,
        eventAction: 'failure',
        properties: {
          document_type: documentType,
        },
      });
    } else {
      analyticsTrack({
        objectName: 'SignUp',
        actionName: 'document upload',
        screen: 'home page',
        user,
        eventAction: 'success',
        properties: {
          document_type: documentType,
        },
      });
    }

    const isComplete = isDocumentTabComplete(
      {
        ...data,
        documents: { ...documents, ...response?.documents },
        addressDoc,
        businessDoc,
        bankDoc,
        additionalDoc,
        hasNonMandatoryEmail,
      },
      isGstinMandatory,
      isUpdatedLiteOnboarding,
    );
    setDocumentUploadCompleted(isComplete);
  };

  const onDeleteFile = async (fileName: string) => {
    const file = documents[fileName].value;

    if (file && file.length) {
      const curDoc = file[file.length - 1];
      const response = await documentDelete(curDoc);
      setProgress(0);
      const isComplete = isDocumentTabComplete(
        {
          ...data,
          documents: { ...documents, ...response?.documents },
          addressDoc,
          businessDoc,
          bankDoc,
          additionalDoc,
          hasNonMandatoryEmail,
        },
        isGstinMandatory,
        isUpdatedLiteOnboarding,
      );
      setDocumentUploadCompleted(isComplete);
      analyticsTrack({
        objectName: 'SignUp',
        actionName: `${file} delete`,
        screen: 'home page',
        user,
        eventAction: 'success',
        properties: {
          document_type: file,
        },
      });
    }
  };

  const getFormikInitialValues = (document) => {
    const lastDocumentId = document.value ? document.value[document.value.length - 1].id : '';
    return lastDocumentId;
  };

  const getFieldError = (formikError, key: string) => {
    const error = Object.keys(formikError).length ? formikError[key] : '';
    return error;
  };

  // update document tab complete checkbox whenever state change
  useEffect(() => {
    const isComplete = isDocumentTabComplete(
      {
        ...data,
        addressDoc,
        businessDoc,
        bankDoc,
        additionalDoc,
        hasNonMandatoryEmail,
      },
      isGstinMandatory,
      isUpdatedLiteOnboarding,
    );
    setDocumentUploadCompleted(isComplete);
  }, [addressDoc, businessDoc, bankDoc, additionalDoc, data, hasNonMandatoryEmail]);

  useEffect(() => {
    trackEvents({
      objectName: 'Page',
      actionName: 'Viewed',
      screen: 'home page',
      properties: {
        pageTitle: 'Document Upload',
      },
    });
  }, []);

  const hasBankVerificationFailed =
    data?.bank_details_verification_status &&
    !['initiated', 'verified'].includes(data?.bank_details_verification_status);

  useEffect(() => {
    if (isSyncBankVerificationEnabled) {
      if (hasBankVerificationFailed) {
        analyticsTrack({
          objectName: 'insync',
          actionName: 'karza bank verification',
          screen: 'home page',
          eventAction: 'failed',
          user,
          properties: {
            bvs_attempt_count: configData?.bank_account_verification_attempt_count,
          },
        });
      } else if (data?.bank_details_verification_status === 'verified') {
        analyticsTrack({
          objectName: 'insync',
          actionName: 'karza bank verification',
          screen: 'home page',
          eventAction: 'success',
          user,
          properties: {
            bvs_attempt_count: configData?.bank_account_verification_attempt_count,
          },
        });
      }
    }
  }, [hasBankVerificationFailed]);

  useEffect(() => {
    if (
      emailVerifyRef.current &&
      data &&
      !isEmailVerified &&
      isEmailNonMandatoryOnL2Form &&
      !user.user?.signup_via_email
    ) {
      const thresholdToScroll = 950;
      const top = emailVerifyRef.current.getBoundingClientRect().top;
      setTimeout(() => {
        if (emailVerifyRef.current && top < thresholdToScroll) {
          emailVerifyRef.current.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'end',
          });
        }
      }, 800);
    }
  }, [data]);

  const getMsmeDownloadLinksView = (header, cerificates) => {
    return (
      <View>
        <Text size="xxsmall" color="shade.960">
          {header}
        </Text>
        <Flex flexDirection="row" alignItems="center">
          <View>
            {cerificates.map(({ url, label, analyticsActionName }) => (
              <>
                <Dot />
                <Space margin={[0, 0.75, 0, 0.5]}>
                  <Link
                    size="xxsmall"
                    href={url}
                    target="_blank"
                    rel="noreferrer noopener"
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: analyticsActionName,
                        screen: 'Document Upload Tab',
                        eventAction: 'clicked',
                        user,
                      });
                    }}
                  >
                    {label}
                  </Link>
                </Space>
              </>
            ))}
          </View>
        </Flex>
      </View>
    );
  };

  const ekycRequiredforBusinessType = [PROPRIETORSHIP, NOT_REGISTERED, PARTNERSHIP];

  if (isAdharEkycRequired) {
    ekycRequiredforBusinessType.push(LLP, PRIVATE, PUBLIC);
  }

  const shouldShowEsignFlow = ekycRequiredforBusinessType.includes(
    parseInt(data.business_type, 10),
  );

  const shouldShowAddressProofField = !(
    shouldShowEsignFlow &&
    data.stakeholder &&
    data.stakeholder.aadhaar_linked
  );
  const isAnyDocumentNeeded = data.business_type !== '11' || shouldShowAddressProofField;
  const canShowEmailVerification =
    isEmailNonMandatoryOnL2Form &&
    !user.user?.signup_via_email &&
    (data?.activation_form_milestone === 'L1' || (data?.submitted && isEmailVerified));

  const fileUploadSegmentEvent = (properties = {}) => {
    trackEvents({
      objectName: 'Form Details',
      actionName: 'Filled',
      screen: 'home page',
      eventAction: 'success',
      user,
      properties: {
        ...properties,
      },
    });
  };

  return (
    <>
      {shouldShowEsignFlow && (
        <ESignVerification
          disabled={isFormLocked}
          showAddressProofDoc={() => setIsEkycDownTime(true)}
        />
      )}
      {isAnyDocumentNeeded ? (
        <Card padding={[2]} margin={[0, 0, 2, 0]}>
          <Flex>
            <View>
              <Space margin={[0, 1, 0, 0]}>
                <View>
                  <Icon name="info" fill="shade.800" size="small" />
                </View>
              </Space>
              <Text size="small" color="shade.940">
                JPG, PNG or PDF of max size 2 MB. Make sure to upload all the pages of the documents
              </Text>
            </View>
          </Flex>
        </Card>
      ) : null}
      <Formik
        initialValues={{
          gstin: businessDetails.gstin.value,
          aadhar_front: getFormikInitialValues(documents.aadhar_front),
          aadhar_back: getFormikInitialValues(documents.aadhar_back),
          passport_front: getFormikInitialValues(documents.passport_front),
          passport_back: getFormikInitialValues(documents.passport_back),
          voter_id_front: getFormikInitialValues(documents.voter_id_front),
          voter_id_back: getFormikInitialValues(documents.voter_id_back),
          gst_certificate: getFormikInitialValues(documents.gst_certificate),
          msme_certificate: getFormikInitialValues(documents.msme_certificate),
          shop_establishment_certificate: getFormikInitialValues(
            documents.shop_establishment_certificate,
          ),
          cancelled_cheque: getFormikInitialValues(documents.cancelled_cheque),
          bank_statement: getFormikInitialValues(documents.bank_statement),
          business_proof_url: getFormikInitialValues(documents.business_proof_url),
          business_pan_url: getFormikInitialValues(documents.business_pan_url),
          personal_pan: getFormikInitialValues(documents.personal_pan),
          form_12a_url: getFormikInitialValues(documents.form_12a_url),
          form_80g_url: getFormikInitialValues(documents.form_80g_url),
          amfi_certificate: getFormikInitialValues(documents.amfi_certificate),
          sla_amfi_certificate: getFormikInitialValues(documents.sla_amfi_certificate),
          nbfc_registration_certificate: getFormikInitialValues(
            documents.nbfc_registration_certificate,
          ),
          sla_nbfc_registration_certificate: getFormikInitialValues(
            documents.sla_nbfc_registration_certificate,
          ),
          irdai_registration_certificate: getFormikInitialValues(
            documents.irdai_registration_certificate,
          ),
          sla_irdai_registration_certificate: getFormikInitialValues(
            documents.sla_irdai_registration_certificate,
          ),
          ffmc_license: getFormikInitialValues(documents.ffmc_license),
          sla_ffmc_license: getFormikInitialValues(documents.sla_ffmc_license),
          sebi_registration_certificate: getFormikInitialValues(
            documents.sebi_registration_certificate,
          ),
          sla_sebi_registration_certificate: getFormikInitialValues(
            documents.sla_sebi_registration_certificate,
          ),
          iata_certificate: getFormikInitialValues(documents.iata_certificate),
          sla_iata_certificate: getFormikInitialValues(documents.sla_iata_certificate),
          affiliation_certificate: getFormikInitialValues(documents.affiliation_certificate),
          contact_email: isEmailNonMandatoryOnL2Form ? documents.contact_email.value : '',
        }}
        validationSchema={() => {
          return Yup.object().shape({
            gstin: Yup.string().when('hasGstin', {
              is: hasGSTIN,
              then: Yup.string()
                .trim()
                .length(15, 'Please provide valid GSTIN')
                .required('GSTIN is a required field')
                .nullable(),
              otherwise: Yup.string().trim().nullable(),
            }),
            contact_email: Yup.string()
              .email('Please enter a valid email id.')
              .required('Contact Email is a required field.')
              .nullable(),
          });
        }}
        enableReinitialize
        onSubmit={() => console.log('onSubmit')}
      >
        {(formikProps) => (
          <Form onSubmit={(e) => e.preventDefault()}>
            {(isVisible('address_proof', data) || isEkycDownTime) && (
              <FormSection title="Authorised Signatory's Address Proof">
                <Field>
                  <Select
                    label="Choose Proof Type"
                    placeholder="SELECT PROOF TYPE"
                    searchable={false}
                    onChange={(value) => {
                      setAddressDoc(value);
                      trackEvents({
                        objectName: 'Accordian',
                        actionName: 'Opened',
                        screen: 'home page',
                        properties: {
                          'Accordian Label': 'Choose Proof Type',
                        },
                      });
                      analyticsTrack({
                        objectName: 'Form Field',
                        actionName: 'Filled',
                        screen: 'home page',
                        eventAction: 'success',
                        user,
                        properties: {
                          'Card Title': "Authorised Signatory's Address Proof",
                          'Element Type': 'Form',
                          'Field Name': 'SELECT PROOF TYPE',
                          'Field Type': 'Text',
                          'Tab Title': 'Document Upload',
                          Mandatory: 'Yes',
                        },
                      });
                    }}
                    value={addressDoc}
                    disabled={isFormLocked}
                  >
                    {/*eslint-disable dot-notation*/}
                    {Object.keys(ADDRESS_PROOF_TYPES).map((address_proof_type) => (
                      <Option
                        key={address_proof_type}
                        value={ADDRESS_PROOF_TYPES[address_proof_type]['value']}
                        label={ADDRESS_PROOF_TYPES[address_proof_type]['label']}
                      >
                        {ADDRESS_PROOF_TYPES[address_proof_type]['label']}
                      </Option>
                    ))}
                  </Select>
                </Field>
                <Field>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, `${addressDoc}_front`, formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': "Authorised Signatory's Address Proof",
                        'Element Type': 'Form',
                        'Field Name': 'Front Side',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={`${addressDoc}_front`}
                    value={formikProps.values[`${addressDoc}_front`]}
                    error={getFieldError(formikProps.errors, `${addressDoc}_front`)}
                    disabled={isFormLocked}
                  />
                  <Text color="shade.950" size="xsmall">
                    Front Side
                  </Text>
                </Field>
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, `${addressDoc}_back`, formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': "Authorised Signatory's Address Proof",
                        'Element Type': 'Form',
                        'Field Name': 'Back Side',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={`${addressDoc}_back`}
                    value={formikProps.values[`${addressDoc}_back`]}
                    error={getFieldError(formikProps.errors, `${addressDoc}_back`)}
                    disabled={isFormLocked}
                  />
                  <Text color="shade.950" size="xsmall">
                    Back Side
                  </Text>
                </Field>
              </FormSection>
            )}
            {isVisible('business_proof_url', data) && (
              <FormSection title={getDocumentTitle(data)} disabled={isFormLocked}>
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, 'business_proof_url', formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': getDocumentTitle(data),
                        'Element Type': 'Form',
                        'Field Name': 'business proof url',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="business_proof_url"
                    value={formikProps.values.business_proof_url}
                    error={getFieldError(formikProps.errors, 'business_proof_url')}
                    disabled={isFormLocked}
                  />
                  <Text color="shade.950" size="xsmall">
                    You can visit pdf merger.com to combine all the pages into one file
                  </Text>
                  <Space margin={[2, 0, 0, 0]}>
                    <View>
                      <Link
                        href="https://www.ilovepdf.com/merge_pdf"
                        target="_blank"
                        rel="noreferrer noopener"
                      >
                        Go to PDF Merger.com
                      </Link>
                    </View>
                  </Space>
                </Field>
              </FormSection>
            )}
            {isVisible('business_proof', data) && (
              <FormSection title="Business Registration Proof">
                <Field>
                  <Select
                    label="Choose Proof Type"
                    placeholder="SELECT REGISTRATION PROOF TYPE"
                    searchable={false}
                    onChange={(value) => {
                      setBusinessDoc(value);
                      analyticsTrack({
                        objectName: 'Form Details',
                        actionName: 'Filled',
                        screen: 'home page',
                        eventAction: 'success',
                        user,
                        properties: {
                          'Card Title': 'Choose Proof Type',
                          'Element Type': 'Form',
                          'Field Name': 'SELECT PROOF TYPE',
                          'Field Type': 'Text',
                          'Tab Title': 'Document Upload',
                          Mandatory: 'Yes',
                        },
                      });
                    }}
                    value={businessDoc}
                    disabled={isFormLocked}
                  >
                    {/*eslint-disable dot-notation*/}
                    {Object.keys(BUSINESS_PROOF_TYPE_DOCS).map((business_proof_type) => {
                      if (
                        business_proof_type === BUSINESS_PROOF_CERTIFICATE_TYPES.MSME_CERTIFICATE &&
                        businessDoc !== 'msme_certificate' &&
                        !formikProps.values.msme_certificate &&
                        isMsmeDisabled
                      ) {
                        return null;
                      }
                      return (
                        <Option
                          key={business_proof_type}
                          value={business_proof_type}
                          label={BUSINESS_PROOF_TYPE_DOCS[business_proof_type]}
                        >
                          {BUSINESS_PROOF_TYPE_DOCS[business_proof_type]}
                        </Option>
                      );
                    })}
                  </Select>
                </Field>
                {isVisible('shop_establishment_number', data) &&
                  businessDoc === 'shop_establishment_certificate' && <ShopEstablishmentNumber />}
                <Field last={businessDoc !== BUSINESS_PROOF_CERTIFICATE_TYPES.GST_CERTIFICATE}>
                  <FileUpload
                    onFileUpload={(e) => {
                      fileUploadSegmentEvent({
                        'Card Title': 'Business Registration Proof',
                        'Element Type': 'Form',
                        'Field Name': businessDoc,
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                      onChange(e, businessDoc, formikProps);
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={businessDoc}
                    value={formikProps.values[businessDoc]}
                    error={getFieldError(formikProps.errors, businessDoc)}
                    disabled={isFormLocked}
                  />
                </Field>
                {businessDoc === 'gst_certificate' && (
                  <Field last>
                    {isGstinAutoPopulate && gstinDetails?.gstinList ? (
                      <GstinAutoPopulate
                        gstin={formikProps.values.gstin}
                        gstinDetails={gstinDetails}
                        errorText={formikProps.touched.gstin && formikProps.errors.gstin}
                        updateGstin={(value) => {
                          postData({ gstin: value });
                          analyticsTrack({
                            objectName: 'Form Field',
                            actionName: 'Filled',
                            screen: 'home page',
                            eventAction: 'success',
                            user,
                            properties: {
                              'Card Title': 'Choose Proof Type',
                              'Element Type': 'Form',
                              'Field Name': 'gst certificate',
                              'Field Type': 'Text',
                              'Tab Title': 'Document Upload',
                              Mandatory: 'Yes',
                            },
                          });
                        }}
                        hasGSTIN={false}
                        disabled={isFormLocked}
                        location="Document Upload Tab"
                      />
                    ) : (
                      <TextInput
                        width="auto"
                        name="gstin"
                        label="GST Identification Number (GSTIN)"
                        helpText="Enter GSTIN & get reviewed faster. Should match your business address."
                        value={formikProps.values.gstin}
                        errorText={formikProps.touched.gstin && formikProps.errors.gstin}
                        onBlur={(value) => {
                          postData({ gstin: value });
                          analyticsTrack({
                            objectName: 'SignUp',
                            actionName: 'Gst Identification Number',
                            screen: 'home page',
                            eventAction: 'initiated',
                            user,
                          });
                          analyticsTrack({
                            objectName: 'Form Details',
                            actionName: 'Filled',
                            screen: 'home page',
                            eventAction: 'success',
                            user,
                            properties: {
                              'Card Title': 'Choose Proof Type',
                              'Element Type': 'Form',
                              'Field Name': 'GST Identification Number (GSTIN)',
                              'Field Type': 'Text',
                              'Tab Title': 'Document Upload',
                              Mandatory: 'Yes',
                            },
                          });
                        }}
                      />
                    )}
                  </Field>
                )}
                {businessDoc === 'msme_certificate' && (
                  <View>
                    <Space margin={[1.5, 0, 1, 0]}>
                      {getMsmeDownloadLinksView(
                        'What is Udyog Aadhar/Udyam Cerificate? View Sample :',
                        [
                          {
                            url:
                              'http://www.msmeudyogaadhaar.org/msme-ssi-udyog-certificate-sample/',
                            label: 'Udyog Aadhar Certificate',
                            analyticsActionName: 'Udyog Aadhar Certificate',
                          },
                          {
                            url: 'https://www.udyogaadhar.co.in/sample-certificate',
                            label: 'Udyam Certificate',
                            analyticsActionName: 'Udyam Certificate',
                          },
                        ],
                      )}
                    </Space>
                    {getMsmeDownloadLinksView('Don’t have it right now? Download here :', [
                      {
                        url: 'https://udyamregistration.gov.in/UA/PrintAcknowledgement_Pub.aspx',
                        label: 'Udyog Aadhar Certificate',
                        analyticsActionName: 'Download Udyog Aadhar Certificate',
                      },
                      {
                        url: 'https://udyamregistration.gov.in/PrintUdyamCertificate.aspx',
                        label: 'Udyam Certificate',
                        analyticsActionName: 'Download Udyam Certificate',
                      },
                    ])}
                  </View>
                )}
              </FormSection>
            )}
            {isVisible('business_pan_url', data) && (
              <FormSection title="Business Pan">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, 'business_pan_url', formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': 'Business Pan',
                        'Element Type': 'Form',
                        'Field Name': 'business pan url',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="business_pan_url"
                    value={formikProps.values.business_pan_url}
                    error={getFieldError(formikProps.errors, 'business_pan_url')}
                    disabled={isFormLocked}
                  />
                  <Text color="shade.950" size="xsmall">
                    PAN details should be of the mentioned business only
                  </Text>
                </Field>
              </FormSection>
            )}
            {isVisible('personal_pan', data) && (
              <FormSection title="Personal Pan">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, 'personal_pan', formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': 'Personal Pan',
                        'Element Type': 'Form',
                        'Field Name': 'Personal pan',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="personal_pan"
                    value={formikProps.values.personal_pan}
                    error={getFieldError(formikProps.errors, 'personal_pan')}
                    disabled={isFormLocked}
                  />
                  <Text color="shade.950" size="xsmall">
                    Upload scanned copy of personal PAN Card
                  </Text>
                </Field>
              </FormSection>
            )}
            {isVisible('form_12a_url', { ...data, isUpdatedLiteOnboarding }) && (
              <FormSection title="Form 12A Allotment Letter">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, 'form_12a_url', formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': 'Form 12A Allotment Letter',
                        'Element Type': 'Form',
                        'Field Name': 'Form 12A url',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="form_12a_url"
                    value={formikProps.values.form_12a_url}
                    error={getFieldError(formikProps.errors, 'form_12a_url')}
                    disabled={isFormLocked}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('form_80g_url', { ...data, isUpdatedLiteOnboarding }) && (
              <FormSection title="Form 80G Allotment Letter">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, 'form_80g_url', formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': 'Form 80G Allotment Letter',
                        'Element Type': 'Form',
                        'Field Name': 'Form 80g url',
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="form_80g_url"
                    value={formikProps.values.form_80g_url}
                    error={getFieldError(formikProps.errors, 'form_80g_url')}
                    disabled={isFormLocked}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('bank_prrof', data) && (
              <FormSection title="Bank Account Proof">
                <Field>
                  <Select
                    label="Choose Proof Type"
                    placeholder="SELECT PROOF TYPE"
                    searchable={false}
                    onChange={(value) => {
                      setBankDoc(value);
                      analyticsTrack({
                        objectName: 'Form Field',
                        actionName: 'Filled',
                        screen: 'home page',
                        eventAction: 'success',
                        user,
                        properties: {
                          'Card Title': 'Bank Account Proof',
                          'Element Type': 'Form',
                          'Field Name': 'SELECT PROOF TYPE',
                          'Field Type': 'Text',
                          'Tab Title': 'Document Upload',
                          Mandatory: 'Yes',
                        },
                      });
                    }}
                    value={bankDoc}
                    disabled={isFormLocked}
                  >
                    {/*eslint-disable dot-notation*/}
                    {Object.keys(BANK_PROOF_TYPE_DOC).map((bank_proof_type) => (
                      <Option
                        key={bank_proof_type}
                        value={BANK_PROOF_TYPE_DOC[bank_proof_type]['value']}
                        label={BANK_PROOF_TYPE_DOC[bank_proof_type]['label']}
                      >
                        {BANK_PROOF_TYPE_DOC[bank_proof_type]['label']}
                      </Option>
                    ))}
                  </Select>
                </Field>
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, bankDoc, formikProps);
                      fileUploadSegmentEvent({
                        'Card Title': 'Bank Account Proof',
                        'Element Type': 'Form',
                        'Field Name': bankDoc,
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={bankDoc}
                    value={formikProps.values[bankDoc]}
                    error={getFieldError(formikProps.errors, bankDoc)}
                    disabled={isFormLocked}
                  />
                  <Text color="shade.950" size="xsmall">
                    Please ensure the Business Name, Account Number & Branch IFSC are clearly
                    visible on the document
                  </Text>
                </Field>
              </FormSection>
            )}
            {isVisible('additional_doc', data) && (
              <FormSection
                title={
                  getAdditionalDocCount(data) > 1
                    ? 'Additional Document'
                    : 'Affiliation Certificate'
                }
                last
              >
                {getAdditionalDocCount(data) > 1 && (
                  <Field>
                    <Select
                      label="Choose Proof Type"
                      placeholder="SELECT PROOF TYPE"
                      searchable={false}
                      onChange={(value) => {
                        setAdditionalDoc(value);
                        analyticsTrack({
                          objectName: 'Form Details',
                          actionName: 'Filled',
                          screen: 'home page',
                          eventAction: 'success',
                          user,
                          properties: {
                            'Card Title':
                              getAdditionalDocCount(data) > 1
                                ? 'Additional Document'
                                : 'Affiliation Certificate',
                            'Element Type': 'Form',
                            'Field Name': 'SELECT PROOF TYPE',
                            'Field Type': 'Text',
                            'Tab Title': 'Document Upload',
                            Mandatory: 'Yes',
                          },
                        });
                      }}
                      value={additionalDoc}
                      disabled={isFormLocked}
                    >
                      {/*eslint-disable dot-notation*/}
                      {Object.keys(ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair]).map(
                        (additional_doc) => (
                          <Option
                            key={additional_doc}
                            value={
                              ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair][additional_doc]
                                .value
                            }
                            label={
                              ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair][additional_doc]
                                .label
                            }
                          >
                            {
                              ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair][additional_doc]
                                .label
                            }
                          </Option>
                        ),
                      )}
                    </Select>
                  </Field>
                )}
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => {
                      onChange(e, additionalDoc, formikProps);
                      fileUploadSegmentEvent({
                        'Card Title':
                          getAdditionalDocCount(data) > 1
                            ? 'Additional Document'
                            : 'Affiliation Certificate',
                        'Element Type': 'Form',
                        'Field Name': additionalDoc,
                        'Field Type': 'Document upload',
                        'Tab Title': 'Document Upload',
                        Mandatory: 'Yes',
                      });
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={additionalDoc}
                    value={additionalDoc ? formikProps.values[additionalDoc] : ''}
                    error={additionalDoc ? getFieldError(formikProps.errors, additionalDoc) : ''}
                    disabled={isFormLocked}
                  />
                </Field>
              </FormSection>
            )}

            <FormSection title="" last visible={canShowEmailVerification} padding={[0.5, 2, 2]}>
              <View ref={emailVerifyRef}>
                <Field last>
                  <Space margin={[1, 0, 0, 3]}>
                    <Checkbox
                      title="Send all important communication and account updates on email"
                      size="medium"
                      onChange={(checked) => {
                        setHasNonMandatoryEmail(checked);
                        setDocumentUploadCompleted(!checked);
                        trackEvents({
                          objectName: 'Checkbox',
                          actionName: 'Clicked',
                          screen: 'home page',
                          eventAction: 'success',
                          user,
                          properties: {
                            'Checkbox Label':
                              'Send all important communication and account updates on email',
                            'Option Selected': checked,
                            'Element Type': 'Form',
                            Mandatory: 'Yes',
                          },
                        });
                      }}
                      checked={hasNonMandatoryEmail || isEmailVerified}
                    />
                  </Space>
                  {(hasNonMandatoryEmail || isEmailVerified) && <EmailVerify />}
                </Field>
              </View>
            </FormSection>

            <Space margin={[2, 0, 1.5, 0]}>
              <StyledSeparator />
            </Space>
            {data.bank_details_verification_status &&
              !['initiated', 'verified'].includes(data?.bank_details_verification_status) &&
              isSyncBankVerificationEnabled && (
                <Text color="negative.900" size="xsmall" align="center">
                  {configData?.bank_account_verification_attempt_count == 10
                    ? 'You have reached maximum limit to changed the bank account details'
                    : 'Your bank details need to be reviewed again. Please check Bank Account tab and enter correct details.'}
                </Text>
              )}
            <Text size="xsmall" align="center">
              By submitting these details you agree to our{' '}
              <Link
                href="https://razorpay.com/terms/"
                target="_blank"
                size="xsmall"
                rel="noreferrer noopener"
              >
                terms and conditions
              </Link>
            </Text>
          </Form>
        )}
      </Formik>
    </>
  );
};

export default DocumentUpload;
