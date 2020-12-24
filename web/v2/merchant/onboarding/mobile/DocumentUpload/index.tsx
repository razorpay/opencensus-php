import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import Icon from '@razorpay/blade/src/atoms/Icon';
import { Formik } from 'formik';
import Link from '@commander/shield/src/shared/Link';
import { Select, Option } from 'v2/components/Select';
import { FileUpload } from 'v2/components/FileUpload';
import Card from '../../../../components/Card';
import { FormSection, Field } from '../Form';
import { useActivationFormState, isVisible, isTabComplete } from '../context/store';
import {
  ADDRESS_PROOF_TYPES,
  BANK_PROOF_TYPE_DOC,
  ACCEPTED_DOCUMENT,
  BUSINESS_PROOF_TYPE_DOCS,
  ADDITIONAL_DOCS_LABEL_VALUE_MAP,
} from '../Constants/OnboardingConstants';
import useActivation from '../hooks/useActivation';
import { getAdditionalDocCount, getBizCatSubCatPair } from '../services/utils';
import ShopEstablishmentNumber from './ShopEstablishmentNumber';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: rgba(22, 47, 86, 0.05);
`;

const DocumentUpload: React.FC = () => {
  const { data, documentUpload, documentDelete } = useActivation();
  const documents = data.documents;

  const bizCatSubCatPair = getBizCatSubCatPair(data).join('-');
  const defaultAdditionalDoc = ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair]
    ? Object.keys(ADDITIONAL_DOCS_LABEL_VALUE_MAP[bizCatSubCatPair])[0]
    : '';

  const [addressDoc, setAddressDoc] = useState<string>('aadhar');
  const [businessDoc, setBusinessDoc] = useState<string>('gst_certificate');
  const [bankDoc, setBankDoc] = useState<string>('cancelled_cheque');
  const [additionalDoc, setAdditionalDoc] = useState<string>(defaultAdditionalDoc);
  const [progress, setProgress] = useState<number>(0);
  const [error, setError] = useState<string>('');

  const setDocumentUploadCompleted = useActivationFormState(
    (state) => state.setDocumentUploadCompleted,
  );

  const onChange = async (e: any, docType: string) => {
    const formData = new FormData();
    formData.append('file', e.target.files[0]);
    formData.append('document_type', docType);

    const onUploadProgress = (progressEvent) => {
      setProgress(Math.round((100 * progressEvent.loaded) / progressEvent.total));
    };

    const response = await documentUpload({ formData, progressTracker: onUploadProgress });

    if (!response) setError('something wrong');

    const isComplete = isTabComplete(
      {
        ...data,
        documents: { ...documents, ...response.documents },
      },
      'documents',
    );
    setDocumentUploadCompleted(isComplete);
  };

  const onDeleteFile = async (fileName: string) => {
    const file = documents[fileName].value;

    if (file && file.length) {
      const curDoc = file[file.length - 1];
      const response = await documentDelete(curDoc);
      const isComplete = isTabComplete(
        {
          ...data,
          documents: { ...documents, ...response.documents },
        },
        'documents',
      );
      setDocumentUploadCompleted(isComplete);
    }
  };

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
          aadhar_front: documents.aadhar_front.value,
          aadhar_back: documents.aadhar_back.value,
          passport_front: documents.passport_front.value,
          passport_back: documents.passport_back.value,
          voter_id_front: documents.voter_id_front.value,
          voter_id_back: documents.voter_id_back.value,
          gst_certificate: documents.gst_certificate.value,
          msme_certificate: documents.msme_certificate.value,
          shop_establishment_certificate: documents.shop_establishment_certificate.value,
          cancelled_cheque: documents.cancelled_cheque.value,
          bank_statement: documents.bank_statement.value,
          business_proof_url: documents.business_proof_url.value,
          business_pan_url: documents.business_pan_url.value,
          personal_pan: documents.personal_pan.value,
          form_12a_url: documents.form_12a_url.value,
          form_80g_url: documents.form_80g_url.value,
          amfi_certificate: documents.amfi_certificate.value,
          sla_amfi_certificate: documents.sla_amfi_certificate.value,
          nbfc_registration_certificate: documents.nbfc_registration_certificate.value,
          sla_nbfc_registration_certificate: documents.sla_nbfc_registration_certificate.value,
          irdai_registration_certificate: documents.irdai_registration_certificate.value,
          sla_irdai_registration_certificate: documents.sla_irdai_registration_certificate.value,
          ffmc_license: documents.ffmc_license.value,
          sla_ffmc_license: documents.sla_ffmc_license.value,
          sebi_registration_certificate: documents.sebi_registration_certificate.value,
          sla_sebi_registration_certificate: documents.sla_sebi_registration_certificate.value,
          iata_certificate: documents.iata_certificate.value,
          sla_iata_certificate: documents.sla_iata_certificate.value,
          affiliation_certificate: documents.affiliation_certificate.value,
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
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={addressDoc ? `${addressDoc}_front` : ''}
                    value={
                      formikProps.values[`${addressDoc}_front`]
                        ? formikProps.values[`${addressDoc}_front`][
                            formikProps.values[`${addressDoc}_front`].length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                  <Text color="shade.950" size="xsmall">
                    Front Side
                  </Text>
                </Field>
                <Field last>
                  <FileUpload
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={addressDoc ? `${addressDoc}_back` : ''}
                    value={
                      formikProps.values[`${addressDoc}_back`]
                        ? formikProps.values[`${addressDoc}_back`][
                            formikProps.values[`${addressDoc}_back`].length - 1
                          ].id
                        : ''
                    }
                    error={error}
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
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="business_proof_url"
                    value={
                      formikProps.values.business_proof_url
                        ? formikProps.values.business_proof_url[
                            formikProps.values.business_proof_url.length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                  <Text color="shade.950" size="xsmall">
                    You can visit pdf merger.com to combine all the pages into one file
                  </Text>
                  <Space margin={[2, 0, 0, 0]}>
                    <View>
                      <Link href={''}>Go to PDF Merger.com</Link>
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
                {data.shop_establishment_verifiable_zone &&
                  businessDoc === 'shop_establishment_certificate' && <ShopEstablishmentNumber />}
                <Field last>
                  <FileUpload
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={businessDoc}
                    value={
                      formikProps.values[businessDoc]
                        ? formikProps.values[businessDoc][
                            formikProps.values[businessDoc].length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('business_pan_url', data) && (
              <FormSection title="Business Pan">
                <Field last>
                  <FileUpload
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="business_pan_url"
                    value={
                      formikProps.values.business_pan_url
                        ? formikProps.values.business_pan_url[
                            formikProps.values.business_pan_url.length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('personal_pan', data) && (
              <FormSection title="Personal Pan">
                <Field last>
                  <FileUpload
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="personal_pan"
                    value={
                      formikProps.values.personal_pan
                        ? formikProps.values.personal_pan[
                            formikProps.values.personal_pan.length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('form_12a_url', data) && (
              <FormSection title="Form 12A Allotment Letter">
                <Field last>
                  <FileUpload
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="form_12a_url"
                    value={
                      formikProps.values.form_12a_url
                        ? formikProps.values.form_12a_url[
                            formikProps.values.form_12a_url.length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('form_80g_url', data) && (
              <FormSection title="Form 80G Allotment Letter">
                <Field last>
                  <FileUpload
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name="form_80g_url"
                    value={
                      formikProps.values.form_80g_url
                        ? formikProps.values.form_80g_url[
                            formikProps.values.form_80g_url.length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                </Field>
              </FormSection>
            )}
            {isVisible('bank_prrof', data) && (
              <FormSection title="Bank Account Proof">
                <Field>
                  <Select
                    label="Choose Proof Type"
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
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={bankDoc}
                    value={
                      formikProps.values[bankDoc]
                        ? formikProps.values[bankDoc][formikProps.values[bankDoc].length - 1].id
                        : ''
                    }
                    error={error}
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
                    onFileUpload={onChange}
                    onRemove={onDeleteFile}
                    progress={progress}
                    accept={ACCEPTED_DOCUMENT}
                    name={
                      getAdditionalDocCount(data) > 1 ? additionalDoc : 'affiliation_certificate'
                    }
                    value={
                      getAdditionalDocCount(data) > 1
                        ? formikProps.values[additionalDoc]
                          ? formikProps.values[additionalDoc][
                              formikProps.values[additionalDoc].length - 1
                            ].id
                          : ''
                        : formikProps.values.affiliation_certificate
                        ? formikProps.values.affiliation_certificate[
                            formikProps.values.affiliation_certificate.length - 1
                          ].id
                        : ''
                    }
                    error={error}
                  />
                </Field>
              </FormSection>
            )}

            <Space margin={[2, 0, 1.5, 0]}>
              <StyledSeparator />
            </Space>

            <Text size="xsmall" align="center">
              By submitting these details you agree to our{' '}
              <Link size="xsmall">terms and conditions</Link>
            </Text>
          </form>
        )}
      </Formik>
    </>
  );
};

export default DocumentUpload;
