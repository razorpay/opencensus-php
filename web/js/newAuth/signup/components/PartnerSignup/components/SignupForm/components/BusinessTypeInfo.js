import React from 'react';
import { CloseIcon } from '@razorpay/blade/components';
import {
  StyledBTypeInfoWrap,
  StyledBTypeInfoHeading,
  StyledBTypeHeading,
  StyledBTypeDescription,
  StyledTopRightClose,
} from './styled';

const RegisteredContent = () => (
  <>
    <StyledBTypeHeading>Registered business</StyledBTypeHeading>
    <StyledBTypeDescription>
      If you are a registered business entity, you will have has one of the following documents
      issued by the government:
      <ul>
        <li>Business Pan</li>
        <li>GST Certificate</li>
        <li>Business Registration Certificate</li>
      </ul>
    </StyledBTypeDescription>
  </>
);

const UnregisteredContent = () => (
  <>
    <StyledBTypeHeading>Not Registered Business</StyledBTypeHeading>
    <StyledBTypeDescription>
      If you do not have any of the above documents, choose a relevant option from “not registered”.
    </StyledBTypeDescription>
  </>
);
const BusinessTypeInfo = ({ label, closeModal }) => (
  <StyledBTypeInfoWrap>
    <StyledTopRightClose onClick={closeModal}>
      <CloseIcon color="feedback.icon.neutral.lowContrast" size="medium" />
    </StyledTopRightClose>
    <StyledBTypeInfoHeading>Business Type</StyledBTypeInfoHeading>
    {label === 'reg' ? (
      <>
        <RegisteredContent />
        <UnregisteredContent />
      </>
    ) : (
      <>
        <UnregisteredContent />
        <RegisteredContent />
      </>
    )}
  </StyledBTypeInfoWrap>
);

export default BusinessTypeInfo;
