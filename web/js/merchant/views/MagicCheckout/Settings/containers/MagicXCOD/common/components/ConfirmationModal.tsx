import React, { useState, useContext, createContext } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Text,
  type ButtonProps,
} from '@razorpay/blade/components';

type ButtonPropsWithoutChildren = Omit<ButtonProps, 'children'>;
type CamelCase<S extends string> = S extends `${infer T}-${infer U}`
  ? `${T}${Capitalize<CamelCase<U>>}`
  : S;
type PrefixedConfirmButtonProps = {
  [K in keyof ButtonPropsWithoutChildren as `confirm${Capitalize<
    CamelCase<K>
  >}`]: ButtonPropsWithoutChildren[K];
};
type ConfirmOptions = {
  title: string;
  description: string;
  confirmText: string;
  dismissText: string;
} & PrefixedConfirmButtonProps;

interface ConfirmationModalContextType {
  open: (options: Partial<ConfirmOptions>) => Promise<boolean>;
  close: () => void;
}

const ConfirmationModalContext = createContext<ConfirmationModalContextType | undefined>(undefined);

export const useConfirm = (): ((options: Partial<ConfirmOptions>) => Promise<boolean>) => {
  const context = useContext(ConfirmationModalContext);
  if (!context) {
    throw new Error('useConfirm hook must be used within a ConfirmationModalProvider');
  }
  return context.open;
};

const extractButtonProps: (
  props: Partial<ConfirmOptions>,
  type: 'confirm' | 'dismiss',
) => ButtonPropsWithoutChildren = (props, type) => {
  const buttonProps: ButtonPropsWithoutChildren = {};

  Object.keys(props).forEach((key) => {
    if (key.startsWith(type)) {
      const originalKey = key
        .slice(7)
        .replace(/^[A-Z]/, (firstLetter) =>
          firstLetter.toLowerCase(),
        ) as unknown as keyof ButtonProps;

      buttonProps[originalKey] = props[key];
    }
  });

  return buttonProps;
};

const defaultConfirmOptions: ConfirmOptions = {
  title: '',
  description: '',
  confirmText: 'Confirm',
  dismissText: 'Cancel',
};

export const ConfirmationModalProvider: React.FC = ({ children }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [options, setOptions] = useState<ConfirmOptions>(defaultConfirmOptions);
  const [resolve, setResolve] = useState<(value: boolean) => void>(() => {});

  const open = (options: Partial<ConfirmOptions> = {}) => {
    setOptions({ ...defaultConfirmOptions, ...options });
    setIsOpen(true);

    return new Promise<boolean>((resolve) => {
      setResolve(() => resolve);
    });
  };

  const close = () => {
    setIsOpen(false);
  };

  const handleConfirm = () => {
    resolve(true);
    close();
  };

  const handleDismiss = () => {
    resolve(false);
    close();
  };

  const confirmButtonProps = extractButtonProps(options, 'confirm');
  const dismissButtonProps = extractButtonProps(options, 'dismiss');
  return (
    <ConfirmationModalContext.Provider value={{ open, close }}>
      {children}
      <Modal isOpen={isOpen} onDismiss={handleDismiss} size="small">
        <ModalHeader title={options.title} />
        <ModalBody>
          <Text>{options.description}</Text>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <Button onClick={handleDismiss} variant="tertiary" {...dismissButtonProps}>
              {options.dismissText}
            </Button>
            <Button
              onClick={handleConfirm}
              color={options.confirmColor}
              accessibilityLabel={options.confirmAccessibilityLabel || options.confirmText}
              {...confirmButtonProps}
            >
              {options.confirmText}
            </Button>
          </Box>
        </ModalFooter>
      </Modal>
    </ConfirmationModalContext.Provider>
  );
};
