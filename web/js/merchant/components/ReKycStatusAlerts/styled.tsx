import React from 'react';
import { Text, Button, ButtonProps } from '@razorpay/blade/components';

interface StrongProps {
  children: React.ReactNode;
}

export const Strong = ({ children }: StrongProps) => {
  return (
    <Text as="span" weight="semibold" color="currentColor">
      {children}
    </Text>
  );
};

interface ActionButtonProps {
  onClick: () => Promise<void> | void;
  children: NonNullable<ButtonProps['children']>;
}

export const ActionButton = ({ children, onClick }: ActionButtonProps) => {
  const [isLoading, setIsLoading] = React.useState(false);

  const handleClick = async () => {
    if (isLoading) return;
    setIsLoading(true);
    await onClick();
    setIsLoading(false);
  };

  return (
    <Button isLoading={isLoading} onClick={handleClick}>
      {children}
    </Button>
  );
};
