import {
  Box,
  Spinner,
  Text,
  ProgressBar,
  Heading,
  AlertTriangleIcon,
} from '@razorpay/blade/components';
import FileUploaded from 'assets/reconciliations/file-uploaded.svg';
import FileUploading from 'assets/reconciliations/file-uploading.svg';
import styled from 'styled-components';

export const Loader = () => (
  <Box display="flex" justifyContent="center" alignItems="center" height="200px">
    <Spinner size="large" />
  </Box>
);

export const FileUploadStatus = ({ fileData }) =>
  fileData?.isUploading || fileData?.isUploaded ? (
    <>
      <Box
        paddingX="spacing.6"
        paddingY="spacing.4"
        display="flex"
        alignItems="center"
        marginTop="spacing.4"
        elevation="midRaised"
        borderWidth="thick"
        borderColor="surface.border.gray.subtle"
        borderRadius="medium"
      >
        <img src={fileData?.isUploaded ? FileUploaded : FileUploading} alt="File Uploading" />
        <Text marginLeft="spacing.4">{fileData?.fileName || ''}</Text>
        {fileData.error ? <Text>Error Occured</Text> : null}
      </Box>
      {fileData?.isUploading ? <ProgressBar isIndeterminate label="" /> : null}
    </>
  ) : null;

export const SomethingWrong = () => (
  <Box textAlign="center" marginY="spacing.10">
    <AlertTriangleIcon size="2xlarge" color="feedback.icon.negative.intense" />
    <Heading size="large" color="danger">
      Something went wrong
    </Heading>
    <Text size="h4" color="danger">
      Please try again after some time
    </Text>
  </Box>
);

// To fix UI issue by blade https://github.com/razorpay/blade/issues/2086
export const BladeDropdownWrapper = styled.div`
  margin-top: -10px;
  min-width: 280px;
`;

export const RenderErrorLoadingOrChild = ({ isError, isLoading, children }) => {
  if (isError) {
    return <SomethingWrong />;
  } else if (isLoading) {
    return <Loader />;
  } else {
    return children || null;
  }
};
