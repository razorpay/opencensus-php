import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import Icon from '@razorpay/blade/src/atoms/Icon';
import { Formik } from 'formik';
import Link from '@commander/shield/src/shared/Link';
import { getColor } from '@razorpay/blade/src/_helpers/theme';
import { Select, Option } from 'v2/components/Select';
import { FileUpload } from 'v2/components/FileUpload';
import Card from '../../../../components/Card';
import { FormSection, Field } from '../Form';
import { useActivationFormState, isVisible } from '../context/store';
import {
  ADDRESS_PROOF_TYPES,
  BANK_PROOF_TYPE_DOC,
  ACCEPTED_DOCUMENT,
  BUSINESS_PROOF_TYPE_DOCS,
  ADDITIONAL_DOCS_LABEL_VALUE_MAP,
} from '../Constants/OnboardingConstants';
import useActivation from '../hooks/useActivation';
import {
  getAdditionalDocCount,
  getBizCatSubCatPair,
  isDocmentTabComplete,
  getDefaultSelectedDocs,
} from '../services/utils';
import ShopEstablishmentNumber from './ShopEstablishmentNumber';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

const DocumentUpload: React.FC = () => {
  const { data, documentUpload, documentDelete } = useActivation();
  const documents = data.documents;

  const bizCatSubCatPair = getBizCatSubCatPair(data).join('-');

  const defaultAddressDoc = getDefaultSelectedDocs(data, 'address');
  const defaultBusinessDoc = getDefaultSelectedDocs(data, 'business');
  const defaultBankDoc = getDefaultSelectedDocs(data, 'bank');
  const defaultAdditionalDoc = getDefaultSelectedDocs(data, 'additional');

  const [addressDoc, setAddressDoc] = useState<string>(defaultAddressDoc);
  const [businessDoc, setBusinessDoc] = useState<string>(defaultBusinessDoc);
  const [bankDoc, setBankDoc] = useState<string>(defaultBankDoc);
  const [additionalDoc, setAdditionalDoc] = useState<string>(defaultAdditionalDoc);
  const [progress, setProgress] = useState<number>(0);

  const setDocumentUploadCompleted = useActivationFormState(
    (state) => state.setDocumentUploadCompleted,
  );

  const onChange = async (e: any, docType: string, formikProps) => {
    const formData = new FormData();
    formData.append('file', e.target.files[0]);
    formData.append('document_type', docType);

    const onUploadProgress = (progressEvent) => {
      setProgress(Math.round((100 * progressEvent.loaded) / progressEvent.total));
    };

    const response = await documentUpload({ formData, progressTracker: onUploadProgress });

    if (!response) formikProps.setFieldError(docType, 'something went wrong');

    const isComplete = isDocmentTabComplete({
      ...data,
      documents: { ...documents, ...response.documents },
      addressDoc,
      businessDoc,
      bankDoc,
      additionalDoc,
    });
    setDocumentUploadCompleted(isComplete);
  };

  const onDeleteFile = async (fileName: string) => {
    const file = documents[fileName].value;

    if (file && file.length) {
      const curDoc = file[file.length - 1];
      const response = await documentDelete(curDoc);

      const isComplete = isDocmentTabComplete({
        ...data,
        documents: { ...documents, ...response.documents },
        addressDoc,
        businessDoc,
        bankDoc,
        additionalDoc,
      });
      setDocumentUploadCompleted(isComplete);
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
    const isComplete = isDocmentTabComplete({
      ...data,
      addressDoc,
      businessDoc,
      bankDoc,
      additionalDoc,
    });
    setDocumentUploadCompleted(isComplete);
  }, [addressDoc, businessDoc, bankDoc, additionalDoc]);

  return (
    <>
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
      <Formik
        initialValues={{
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
        }}
        enableReinitialize
        onSubmit={() => console.log('onSubmit')}
      >
        {(formikProps) => (
          <form onChange={formikProps.handleChange}>
            {isVisible('address_proof', data) && (
              <FormSection title="Authorised Signatory's Address Proof">
                <Field>
                  <Select
                    label="Choose Proof Type"
                    placeholder="SELECT PROOF TYPE"
                    searchable={false}
                    onChange={(value) => setAddressDoc(value)}
                    value={addressDoc}
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
                    onFileUpload={async (e) => onChange(e, `${addressDoc}_front`, formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={`${addressDoc}_front`}
                    value={formikProps.values[`${addressDoc}_front`]}
                    error={getFieldError(formikProps.errors, `${addressDoc}_front`)}
                  />
                  <Text color="shade.950" size="xsmall">
                    Front Side
                  </Text>
                </Field>
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => onChange(e, `${addressDoc}_back`, formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={`${addressDoc}_back`}
                    value={formikProps.values[`${addressDoc}_back`]}
                    error={getFieldError(formikProps.errors, `${addressDoc}_back`)}
                  />
                  <Text color="shade.950" size="xsmall">
                    Back Side
                  </Text>
                </Field>
              </FormSection>
            )}
            {isVisible('business_proof_url', data) && (
              <FormSection title="Certificate of Incorporation">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => onChange(e, 'business_proof_url', formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="business_proof_url"
                    value={formikProps.values.business_proof_url}
                    error={getFieldError(formikProps.errors, 'business_proof_url')}
                  />
                  <Text color="shade.950" size="xsmall">
                    You can visit pdf merger.com to combine all the pages into one file
                  </Text>
                  <Space margin={[2, 0, 0, 0]}>
                    <View>
                      <Link href="https://www.ilovepdf.com/merge_pdf" target="_blank">
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
                    placeholder="SELECT PROOF TYPE"
                    searchable={false}
                    onChange={(value) => setBusinessDoc(value)}
                    value={businessDoc}
                  >
                    {/*eslint-disable dot-notation*/}
                    {Object.keys(BUSINESS_PROOF_TYPE_DOCS).map((business_proof_type) => (
                      <Option
                        key={business_proof_type}
                        value={business_proof_type}
                        label={BUSINESS_PROOF_TYPE_DOCS[business_proof_type]}
                      >
                        {BUSINESS_PROOF_TYPE_DOCS[business_proof_type]}
                      </Option>
                    ))}
                  </Select>
                </Field>
                {isVisible('shop_establishment_number', data) &&
                  businessDoc === 'shop_establishment_certificate' && <ShopEstablishmentNumber />}
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => onChange(e, businessDoc, formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={businessDoc}
                    value={formikProps.values[businessDoc]}
                    error={getFieldError(formikProps.errors, businessDoc)}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('business_pan_url', data) && (
              <FormSection title="Business Pan">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => onChange(e, 'business_pan_url', formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="business_pan_url"
                    value={formikProps.values.business_pan_url}
                    error={getFieldError(formikProps.errors, 'business_pan_url')}
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
                    onFileUpload={(e) => onChange(e, 'personal_pan', formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="personal_pan"
                    value={formikProps.values.personal_pan}
                    error={getFieldError(formikProps.errors, 'personal_pan')}
                  />
                  <Text color="shade.950" size="xsmall">
                    Upload scanned copy of personal PAN Card
                  </Text>
                </Field>
              </FormSection>
            )}
            {isVisible('form_12a_url', data) && (
              <FormSection title="Form 12A Allotment Letter">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => onChange(e, 'form_12a_url', formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="form_12a_url"
                    value={formikProps.values.form_12a_url}
                    error={getFieldError(formikProps.errors, 'form_12a_url')}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('form_80g_url', data) && (
              <FormSection title="Form 80G Allotment Letter">
                <Field last>
                  <FileUpload
                    onFileUpload={(e) => onChange(e, 'form_80g_url', formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="form_80g_url"
                    value={formikProps.values.form_80g_url}
                    error={getFieldError(formikProps.errors, 'form_80g_url')}
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
                    onChange={(value) => setBankDoc(value)}
                    value={bankDoc}
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
                    onFileUpload={(e) => onChange(e, bankDoc, formikProps)}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={bankDoc}
                    value={formikProps.values[bankDoc]}
                    error={getFieldError(formikProps.errors, bankDoc)}
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
                      }}
                      value={additionalDoc}
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
                    }}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={additionalDoc}
                    value={additionalDoc ? formikProps.values[additionalDoc] : ''}
                    error={additionalDoc ? getFieldError(formikProps.errors, additionalDoc) : ''}
                  />
                </Field>
              </FormSection>
            )}

            <Space margin={[2, 0, 1.5, 0]}>
              <StyledSeparator />
            </Space>

            <Text size="xsmall" align="center">
              By submitting these details you agree to our{' '}
              <Link href="https://razorpay.com/terms/" target="_blank" size="xsmall">
                terms and conditions
              </Link>
            </Text>
          </form>
        )}
      </Formik>
    </>
  );
};

export default DocumentUpload;
