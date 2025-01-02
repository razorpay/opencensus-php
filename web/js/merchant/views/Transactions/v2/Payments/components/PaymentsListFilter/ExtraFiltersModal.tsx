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
import { connect } from 'react-redux';
import { useLocation } from 'react-router-dom';
import { bindActionCreators, compose } from 'redux';

import { Option } from 'common/components/Dropdown/types';
import { useMobile } from 'common/hooks/useMobile';
import { withRouter } from 'common/deprecated/withRouter';
import { ALL_VALUE } from 'merchant/views/Transactions/v2/common/constants';
import { trackMethodFilter } from 'merchant/views/Transactions/v2/common/tracking';
import { closeModal } from 'merchant_common/reducers/modals';

import { ExtraFiltersModalProps } from './types';
import { getDefaultValuesAndOptions, getOptions } from './utils';

const ExtraFiltersModal = ({ handleSearch, closeModal }: ExtraFiltersModalProps): JSX.Element => {
  const { defaultMethodValue, defaultChannelValue } = getDefaultValuesAndOptions();
  const [method, setMethod] = useState(defaultMethodValue);
  const [channel, setChannel] = useState(defaultChannelValue);
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const isMobile = useMobile();
  const { paymentMethodOptions, paymentChannelOptions } = getOptions({
    isMobile,
    isOrgCurlec: false,
    isJnKOmniEnabled: false,
  });
  const { pathname } = useLocation();

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
                {(paymentMethodOptions as Option[]).map(({ title, value }) => (
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

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default withRouter<any>(compose(connect(null, mapDispatchToProps)(ExtraFiltersModal)));
