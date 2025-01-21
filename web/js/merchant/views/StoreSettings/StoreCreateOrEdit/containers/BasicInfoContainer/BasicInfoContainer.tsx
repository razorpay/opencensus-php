import React, { Fragment } from 'react';
import {
  Box,
  Card,
  CardBody,
  CloseIcon,
  Divider,
  Heading,
  Radio,
  Table,
  TableBody,
  TableHeader,
  TableHeaderCell,
  TableHeaderRow,
  TableCell,
  TableRow,
  Link,
  PlusCircleIcon,
  Text,
  InfoIcon,
  Spinner,
} from '@razorpay/blade/components';
import { FieldArray, useField, useFormikContext } from 'formik';

import { StoreCreateFormValues } from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';
import FormikRadioGroup from 'merchant/views/StoreSettings/common/components/FormFields/FormikRadioGroup';
import FormikTextInputField from 'merchant/views/StoreSettings/common/components/FormFields/FormikTextInputField';
import { ONLINE, OFFLINE } from 'merchant/views/StoreSettings/common/constants';

const CustomFields = () => {
  const { values, touched, errors } = useFormikContext<StoreCreateFormValues>();
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      {values?.customFields?.length > 0 ? (
        <Text size="large" weight="medium">
          Custom Field(s)
        </Text>
      ) : null}
      <FieldArray
        name="customFields"
        render={(arrayHelpers) => (
          <Box display="flex" flexDirection="column" gap="spacing.4">
            {values?.customFields?.length ? (
              <>
                <Table
                  showBorderedCells
                  data={{ nodes: values?.customFields || [] }}
                  gridTemplateColumns="10% 40% 40% 10%"
                  rowDensity="comfortable"
                >
                  {(tableData) => (
                    <Fragment>
                      <TableHeader>
                        <TableHeaderRow>
                          <TableHeaderCell>Sr. No.</TableHeaderCell>
                          <TableHeaderCell>Title</TableHeaderCell>
                          <TableHeaderCell>Value</TableHeaderCell>
                          <TableHeaderCell>{``}</TableHeaderCell>
                        </TableHeaderRow>
                      </TableHeader>
                      <TableBody>
                        {tableData.map((tableItem, index) => (
                          <TableRow key={index} item={tableItem}>
                            <TableCell>{index + 1}</TableCell>
                            <TableCell>
                              <FormikTextInputField
                                label=""
                                name={`customFields.${index}.title`}
                                placeholder={`Title ${index + 1}`}
                                validationState={
                                  touched?.customFields && tableItem?.title?.length === 0
                                    ? 'error'
                                    : 'none'
                                }
                              />
                            </TableCell>
                            <TableCell>
                              <FormikTextInputField
                                label=""
                                name={`customFields.${index}.value`}
                                placeholder={`Value ${index + 1}`}
                                validationState={
                                  touched?.customFields && tableItem?.value?.length === 0
                                    ? 'error'
                                    : 'none'
                                }
                              />
                            </TableCell>
                            <TableCell>
                              <Link
                                color="negative"
                                icon={CloseIcon}
                                onClick={() => {
                                  arrayHelpers.remove(index);
                                }}
                              />
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Fragment>
                  )}
                </Table>
                {touched?.customFields && errors?.customFields ? (
                  <Box display="flex" gap="spacing.3">
                    <InfoIcon color="feedback.icon.negative.intense" />
                    <Text variant="caption" size="small" color="feedback.text.negative.intense">
                      Rows cannot be left empty. Entry relevant data or remove the unused row.
                    </Text>
                  </Box>
                ) : null}
              </>
            ) : null}

            <Link
              icon={PlusCircleIcon}
              iconPosition="left"
              variant="button"
              onClick={() => {
                arrayHelpers.push({ title: '', value: '' });
              }}
            >
              Add Custom Field
            </Link>
          </Box>
        )}
      />
    </Box>
  );
};

type BasicInfoContainerProps = {
  isLoading: boolean;
};

function BasicInfoContainer(props: BasicInfoContainerProps) {
  const { isLoading } = props;
  const [field] = useField('storeType');
  const { setFieldValue } = useFormikContext();
  return (
    <Card>
      <CardBody>
        <Heading>Basic Info</Heading>
        {isLoading ? (
          <Box display="flex" alignItems="center" justifyContent="center" height="100%">
            <Spinner accessibilityLabel="Basic info loading" />
          </Box>
        ) : (
          <Box marginTop="spacing.8" display="flex" gap="spacing.4" flexDirection="column">
            <Box width="70%" display="flex" gap="spacing.6" flexDirection="column">
              <FormikRadioGroup
                name="storeType"
                label="Store Type"
                labelPosition="left"
                isRequired
                necessityIndicator="required"
                onChange={({ value }) => {
                  if (value === OFFLINE) setFieldValue('websiteUrl', '');
                }}
              >
                <Box display="flex" gap="spacing.4">
                  <Radio value={ONLINE}>Online</Radio>
                  <Radio value={OFFLINE}>Offline</Radio>
                </Box>
              </FormikRadioGroup>

              <FormikTextInputField
                name="storeName"
                label="Store Name"
                placeholder="Enter Store Name"
                necessityIndicator="required"
                isRequired
                labelPosition="left"
              />
              <FormikTextInputField
                name="storeCode"
                label="Store Code "
                placeholder="Enter Store Code"
                necessityIndicator="required"
                isRequired
                labelPosition="left"
              />
              {field.value === ONLINE ? (
                <FormikTextInputField
                  name="websiteUrl"
                  label="Website URL"
                  necessityIndicator="required"
                  placeholder="Enter Website URL"
                  isRequired
                  labelPosition="left"
                />
              ) : null}
            </Box>
            <Divider dividerStyle="dashed" marginY="spacing.4" />
            <Box width="70%" display="flex" gap="spacing.4" flexDirection="column">
              <CustomFields />
            </Box>
          </Box>
        )}
      </CardBody>
    </Card>
  );
}

export default BasicInfoContainer;
