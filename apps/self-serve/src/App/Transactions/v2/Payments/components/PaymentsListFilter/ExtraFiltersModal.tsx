import React, { useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Box,
  Button,
  Dropdown,
  DropdownOverlay,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  SelectInput,
  Text,
} from '@razorpay/blade/components';
import { useLocation } from 'react-router-dom';
import { useStore } from 'shell/commonStore';
import { Option } from '@dashboard/shared-ui/components/Dropdown/types';
import { withRouter } from 'shell/deprecated/withRouter';
import { useMobile } from '@dashboard/shared-ui/hooks';
import { ALL_VALUE } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { trackMethodFilter } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { ExtraFiltersModalProps, PaymentMethodOption } from './types';
import { getDefaultValuesAndOptions, getOptions } from './utils';

const ExtraFiltersModal = ({ handleSearch }: ExtraFiltersModalProps): JSX.Element => {
  const { defaultMethodValue, defaultChannelValue } = getDefaultValuesAndOptions();
  const [method, setMethod] = useState(defaultMethodValue);
  const [channel, setChannel] = useState(defaultChannelValue);
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const isMobile = useMobile();
  const { paymentMethodOptions, paymentChannelOptions } = getOptions(isMobile);
  const { pathname } = useLocation();
  const closeModal = useStore((state) => state.closeModal);

  const dismissModal = () => {
    setIsOpen(false);
    closeModal();
  };

  const onPaymentMethodChange = ({ values }): void => {
    const stringifiedOptionValue = String(values);
    const method = stringifiedOptionValue === ALL_VALUE ? '' : stringifiedOptionValue;
    setMethod(method);
    trackMethodFilter({
      paymentMethodSelected: method,
      pathname,
    });
  };

  const onChannelSelect = ({ values }): void => {
    const stringifiedChipValue = String(values);
    const channel = stringifiedChipValue === ALL_VALUE ? '' : stringifiedChipValue;
    setChannel(channel);
  };

  const applyFilters = () => {
    Promise.resolve(
      handleSearch({
        source_channel: channel,
        method,
      }),
    ).finally(() => {
      dismissModal();
    });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={dismissModal} size="small">
      <ModalHeader title="Filters" />
      <ModalBody>
        <Box width="100%">
          <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
            Payment Method
          </Text>
          <Dropdown marginTop="spacing.3" selectionType="single">
            <SelectInput
              label=""
              name="method"
              placeholder="Select Payment Method"
              defaultValue={method}
              onChange={onPaymentMethodChange}
            />
            <DropdownOverlay>
              <ActionList>
                {(paymentMethodOptions as PaymentMethodOption[]).map(({ title, value }) => (
                  <ActionListItem key={value} title={title} value={value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Box width="100%" marginTop="spacing.6">
          <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
            Channel
          </Text>
          <Dropdown marginTop="spacing.3" selectionType="single">
            <SelectInput
              label=""
              name="method"
              placeholder="Select Source Channel"
              defaultValue={channel}
              onChange={onChannelSelect}
            />
            <DropdownOverlay>
              <ActionList>
                {(paymentChannelOptions as Option[]).map(({ title, value }) => (
                  <ActionListItem key={value} title={title} value={value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button type="button" variant="secondary" marginX="spacing.5" onClick={dismissModal}>
            Cancel
          </Button>
          <Button type="button" variant="primary" onClick={applyFilters}>
            Apply
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default withRouter<any>(ExtraFiltersModal);
