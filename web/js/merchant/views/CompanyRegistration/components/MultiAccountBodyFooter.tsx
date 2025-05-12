import React from 'react';
import { Box, Text, CardBody, Card, Button, UserPlusIcon } from '@razorpay/blade/components';
import { getUser } from 'merchant/store';
import { getFirstUppercaseChar } from 'merchant/views/CompanyRegistration/utils';
import { trackEventOnCreateAccountFormField } from '../analytics';
import { AccountT } from '../types';

export const AccountType = Object.freeze({
  primary: 'primary',
  secondary: 'secondary',
} as const);

export const MultiAccountBody = ({
  setSelected,
  selected,
}: {
  selected: string;
  setSelected: (value: AccountT) => void;
}) => {
  const { user } = getUser();
  return (
    <Box>
      <Card
        elevation="none"
        display="flex"
        borderRadius="medium"
        marginBottom="spacing.5"
        as="label"
        isSelected={selected === AccountType.primary}
        onClick={() => {
          setSelected(AccountType.primary);
          trackEventOnCreateAccountFormField(false);
        }}
      >
        <CardBody>
          <Box display="flex" alignItems="center">
            <Box
              width="spacing.8"
              height="spacing.8"
              borderRadius="round"
              display="flex"
              justifyContent="center"
              alignItems="center"
              backgroundColor="surface.background.primary.subtle"
              marginRight="spacing.4"
            >
              {getFirstUppercaseChar(user?.name)}
            </Box>
            <Box>
              {user?.contact_mobile ? (
                <Text size="medium" weight="semibold">
                  {user.contact_mobile}
                </Text>
              ) : null}
              {user?.email ? <Text color="surface.text.gray.muted">{user.email}</Text> : null}
            </Box>
          </Box>
        </CardBody>
      </Card>
      <Card
        elevation="none"
        display="flex"
        borderRadius="medium"
        marginBottom="spacing.5"
        as="label"
        isSelected={selected === AccountType.secondary}
        onClick={() => {
          setSelected(AccountType.secondary);
          trackEventOnCreateAccountFormField(true);
        }}
      >
        <CardBody>
          <Box display="flex" justifyContent="flex-start" alignItems="center">
            <Box
              width="spacing.8"
              height="spacing.8"
              borderRadius="max"
              display="flex"
              justifyContent="center"
              alignItems="center"
              backgroundColor="surface.background.primary.subtle"
              marginRight="spacing.4"
            >
              <UserPlusIcon color="surface.icon.gray.normal" size="medium" />
            </Box>
            <Text size="medium" weight="semibold">
              Use new login credentials
            </Text>
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};
export const MultiAccountFooter = ({
  isLoading,
  handleUserAction,
}: {
  isLoading: boolean;
  handleUserAction: () => void;
}) => {
  return (
    <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
      <Button isFullWidth isLoading={isLoading} onClick={handleUserAction}>
        Proceed
      </Button>
    </Box>
  );
};