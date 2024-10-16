import React, { memo } from 'react';
import {
  List,
  ListItem,
  ListItemLink,
  Text,
  Modal,
  ModalBody,
  ModalHeader,
  Box,
} from '@razorpay/blade/components';

import { TncModal } from './PricingSubscriptionProps.type';
import { TNC_CONTENT, TNC_DETAILS_CREDIT_LINK } from './constants';

const TncContent = (): JSX.Element => (
  <>
    <Text size="large">Terms & Conditions</Text>
    <Box marginTop="spacing.6">
      <Text size="medium" testID="tncSubHeader">
        By subscribing to the Razorpay Pricing Subscription Plans available on the merchant
        dashboard provided to you by Razorpay (“Pricing Subscription Plan/s”), you agree to the
        below terms and conditions. Your subscription and use of the Amount Credits (as defined{' '}
        <ListItemLink
          href={TNC_DETAILS_CREDIT_LINK}
          rel="noreferrer noopener"
          target="_blank"
          variant="anchor"
        >
          here
        </ListItemLink>
        ) indicate your acceptance of these terms.
      </Text>
    </Box>
    <List variant="ordered">
      {TNC_CONTENT.map(({ text, link: { label = '', url = '' } = {} }, index) => (
        <ListItem key={`${text}_${index}`}>
          {text}
          {label && url ? (
            <ListItemLink href={url} rel="noreferrer noopener" target="_blank" variant="anchor">
              {label}
            </ListItemLink>
          ) : null}
        </ListItem>
      ))}
    </List>
  </>
);
const TncContentMemo = memo(TncContent);
const TncDesktop = ({ isOpenTncModal, toggleTncModal }: TncModal): JSX.Element => {
  return (
    <Modal isOpen={isOpenTncModal} onDismiss={toggleTncModal} size="large">
      <ModalHeader title="" />
      <ModalBody>
        <TncContentMemo />
      </ModalBody>
    </Modal>
  );
};
export { TncContentMemo };
export default TncDesktop;
