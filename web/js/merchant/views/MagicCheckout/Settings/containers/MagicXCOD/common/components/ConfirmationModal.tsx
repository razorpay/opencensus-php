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
} & PrefixedConfirmButtonProps & {
    onSuccess?: (res: unknown) => void;
    onError?: (res: unknown) => void;
  };

interface ConfirmationModalContextType {
  open: (options: Partial<ConfirmOptions>) => Promise<boolean>;
  promise: <T extends unknown = unknown>(
    p: Promise<T> | (() => Promise<T>),
    options: Partial<ConfirmOptions> & {
      onSuccess?: (res: T) => void;
      onError?: (res: unknown) => void;
    },
  ) => Promise<boolean>;
  close: () => void;
}

const ConfirmationModalContext = createContext<ConfirmationModalContextType | undefined>(undefined);

type ConfirmHookReturnValue = {
  (options: Partial<ConfirmOptions>): Promise<boolean>;
} & {
  promise: (
    p: Promise<unknown> | (() => Promise<unknown>),
    options: Partial<ConfirmOptions>,
  ) => Promise<boolean>;
};
export const useConfirm = (): ConfirmHookReturnValue => {
  const context = useContext(ConfirmationModalContext);
  if (!context) {
    throw new Error('useConfirm hook must be used within a ConfirmationModalProvider');
  }

  function open(options: Partial<ConfirmOptions>): Promise<boolean> {
    return context?.open(options) || Promise.resolve(false);
  }
  open.promise = context.promise;

  return open;
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
  const [isLoading, setIsLoading] = useState(false);
  const [promiseOrFunction, setPromiseOrFunction] = useState<unknown>();
  const [options, setOptions] = useState<ConfirmOptions>(defaultConfirmOptions);
  const [resolve, setResolve] = useState<(value: boolean) => void>(() => {});

  const open = (options: Partial<ConfirmOptions> = {}) => {
    setOptions({ ...defaultConfirmOptions, ...options });
    setIsOpen(true);

    return new Promise<boolean>((resolve) => {
      setResolve(() => resolve);
    });
  };

  const promise = async <T extends unknown = unknown>(
    p: Promise<T> | (() => Promise<T>),
    options: Partial<ConfirmOptions> & {
      onSuccess?: (res: T) => void;
      onError?: (res: unknown) => void;
    } = {
      onSuccess: () => {},
      onError: () => {},
    },
  ): Promise<boolean> => {
    setOptions({ ...defaultConfirmOptions, ...options });
    setIsOpen(true);
    setPromiseOrFunction(() => p);

    return new Promise<boolean>((resolve) => {
      setResolve(() => resolve);
    });
  };

  const consumePromiseOrFunction = async () => {
    setIsLoading(true);
    const p = typeof promiseOrFunction === 'function' ? promiseOrFunction() : promiseOrFunction;
    let hasSuccessfulResponse = false;
    let response = null;

    await p
      .then((res) => {
        response = res;
        hasSuccessfulResponse = true;
      })
      .catch((err: unknown) => {
        if (options.onError) {
          options.onError(err);
        }
      })
      .finally(() => {
        setIsLoading(false);

        if (hasSuccessfulResponse) {
          if (options.onSuccess) {
            options.onSuccess(response);
          }
          setIsOpen(false);
        }

        resolve(hasSuccessfulResponse);
      });
  };

  const close = () => {
    setIsOpen(false);
  };

  const handleConfirm = () => {
    if (!promiseOrFunction) {
      resolve(true);
      close();
    } else {
      consumePromiseOrFunction();
    }
  };

  const handleDismiss = () => {
    resolve(false);
    close();
  };

  const confirmButtonProps = extractButtonProps(options, 'confirm');
  const dismissButtonProps = extractButtonProps(options, 'dismiss');
  return (
    <ConfirmationModalContext.Provider value={{ open, close, promise }}>
      {children}
      <Modal isOpen={isOpen} onDismiss={handleDismiss} size="small" zIndex={1500}>
        <ModalHeader title={options.title} />
        <ModalBody>
          <Text>{options.description}</Text>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
            <Button
              onClick={handleDismiss}
              variant="tertiary"
              isDisabled={isLoading}
              {...dismissButtonProps}
            >
              {options.dismissText}
            </Button>
            <Button
              onClick={handleConfirm}
              color={options.confirmColor}
              accessibilityLabel={options.confirmAccessibilityLabel || options.confirmText}
              isDisabled={isLoading}
              isLoading={isLoading}
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
