import React, { useState } from 'react';
import {
  Box,
  Button,
  Radio,
  RadioGroup,
  TextInput,
  Text,
  Checkbox,
} from '@razorpay/blade/components';
import { useFormikContext } from 'formik';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { createVCipLink as createVCipLinkAction } from 'merchant/reducers/unlockIntlPaymentMethods/actions';
import { FORMIK_FORM_KEYS } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import useFormContext from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useFormContext';
import {
  FormikValues,
  VideoKycProps,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';
import { showNotification } from 'merchant_common/reducers/notifications';

const VideoKyc = ({
  promoterPanName,
  isCreatingLink,
  createVCipLink,
  showNotification,
}: VideoKycProps) => {
  const { setFieldValue, values } = useFormikContext<FormikValues>();
  const { isLoading } = useFormContext();
  const [videoKycLink, setVideoKycLink] = useState<string | null>(null);

  const handleGenerate = async () => {
    try {
      const vKycLink = await createVCipLink(promoterPanName);
      if (vKycLink.error)
        showNotification({
          type: 'error',
          message: vKycLink.error.message as string,
        });
      const webLink = vKycLink?.payload?.details?.weblink;
      if (webLink) {
        setVideoKycLink(webLink);
      }
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Link generation failed! Please try again later.',
      });
    }
  };

  const onCheckboxClick = (e) => {
    const { value, isChecked } = e;
    setFieldValue(value, isChecked);
  };

  return (
    <Box>
      <Box>
        <TextInput
          label="Authorised Signatory"
          placeholder="Authorised Signatory"
          value={promoterPanName}
        />
      </Box>
      <Box marginTop="spacing.7">
        <RadioGroup
          label="I am"
          name={FORMIK_FORM_KEYS.SIGNATORY}
          value={values[FORMIK_FORM_KEYS.SIGNATORY]}
          isDisabled={isCreatingLink || isLoading}
          onChange={({ name, value }) => setFieldValue(name as string, value as string)}
        >
          <Radio value="1">the authorized signatory</Radio>
          <Radio value="0">not the authorized signatory</Radio>
        </RadioGroup>
      </Box>
      {values[FORMIK_FORM_KEYS.SIGNATORY] === '0' && !videoKycLink && (
        <Box marginTop="spacing.7">
          <Button variant="secondary" isLoading={isCreatingLink} onClick={handleGenerate}>
            Get Link
          </Button>

          <Text marginTop="spacing.4">
            Please copy the link generated and share it with the authorized signatory
          </Text>
        </Box>
      )}
      {values[FORMIK_FORM_KEYS.SIGNATORY] === '0' && videoKycLink && (
        <TextInput
          label="Video KYC link"
          placeholder="Video KYC link"
          helpText="Please copy the link generated and share it with the authorised signatory"
          value={videoKycLink}
          marginTop="spacing.7"
        />
      )}
      {values[FORMIK_FORM_KEYS.SIGNATORY] === '1' && (
        <Box marginTop="spacing.7">
          <Text>Please ensure the following before proceeding for your video KYC:</Text>
          <Text>1. 10 minutes of your time</Text>
          <Text>2. Original copy of PAN Card</Text>
          <Text>3. Original copy of Aadhaar Card</Text>
          <Text>4. Away from noisy areas for proper audio</Text>
          <Text>5. Well-lit environment for better visibility during the video call</Text>
        </Box>
      )}
      {values[FORMIK_FORM_KEYS.SIGNATORY] === '1' && (
        <Box marginTop="spacing.8">
          <Checkbox
            value={FORMIK_FORM_KEYS.VKYC_TNC_ACCEPTED}
            defaultChecked={values[FORMIK_FORM_KEYS.VKYC_TNC_ACCEPTED]}
            onChange={onCheckboxClick}
          >
            I confirm that I possess all the documents mentioned above and am ready to proceed for
            video KYC
          </Checkbox>
        </Box>
      )}
    </Box>
  );
};

const mapStateToProps = ({ session, unlockIntlPaymentMethods }) => ({
  promoterPanName: session.user?.promoter_pan_name,
  isCreatingLink: unlockIntlPaymentMethods.isCreatingLink,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      createVCipLink: createVCipLinkAction,
      showNotification,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(VideoKyc);
