import React, { useState, useCallback } from 'react';
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
    TextInput,
    FilterIcon
} from '@razorpay/blade/components';
import { TreeSelect } from 'merchant/components/TreeSelect';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { useMobile } from 'common/hooks/useMobile';
import { withRouter } from 'common/deprecated/withRouter';
import { ALL_VALUE } from 'merchant/views/Transactions/v2/common/constants';
import { trackMethodFilter } from 'merchant/views/Transactions/v2/common/tracking';
import { closeModal } from 'merchant_common/reducers/modals';
import { getDefaultValuesAndOptions, getOptions } from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/utils';
import { useLocation } from 'react-router-dom';
import { useHierarchyStore } from 'merchant/views/Transactions/v2/common/stores/useHierarchyStore';
import { useSplitzService } from 'common/splitz';
import { isOmniHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';

export interface FilterState {
    method: string;
    deviceId?: string;
    channel?: string;
    storeId?: string[];
}

export interface ExtraFiltersModalProps {
    handleSearch: (params: Record<string, any>) => void;
    closeModal: () => void;
}

const ExtraFiltersModal = ({ handleSearch, closeModal }: ExtraFiltersModalProps): JSX.Element => {
    const { pathname } = useLocation();
    const stores = useHierarchyStore((state) => state.stores);
    const isMobile = useMobile();
    const splitz = useSplitzService();

    const isStoreHierarchyEnabled = isOmniHomepageEnabled(splitz?.abExperiments);
    const { defaultMethodValue, defaultChannelValue, defaultDeviceIdValue, defaultStoreIdValue } = getDefaultValuesAndOptions();
    const { paymentMethodOptions: methodOptions, paymentChannelOptions: channelOptions } = getOptions({
        isMobile,
        isOrgCurlec: false,
        isJnKOmniEnabled: false,
    });

    const [isOpen, setIsOpen] = useState(true);
    const [filters, setFilters] = useState<FilterState>({
        method: defaultMethodValue,
        channel: defaultChannelValue,
        deviceId: defaultDeviceIdValue,
        storeId: defaultStoreIdValue
    });
    const [storeIdCount, setStoreIdCount] = useState(0);

    const dismissModal = useCallback(() => {
        setIsOpen(false);
        closeModal();
    }, [closeModal]);

    const handleFilterChange = useCallback((key: keyof FilterState, value: any, shouldTrack = false) => {
        if (shouldTrack && key === 'method') {
            trackMethodFilter({ paymentMethodSelected: value, pathname });
        }

        const processedValue = typeof value === 'string' && value === ALL_VALUE ? '' : value;
        setFilters(prev => ({ ...prev, [key]: processedValue }));
    }, [pathname]);

    const applyFilters = useCallback(() => {
        const searchParams = Object.entries(filters).reduce((acc, [key, value]) => {
            if (value && (Array.isArray(value) ? value.length > 0 : true)) {
                if (key === 'storeId') {
                    acc['store_ids[]'] = value;
                } else {
                    acc[key === 'deviceId' ? 'device_id' : key === 'channel' ? 'source_channel' : key] = value;
                }
            }
            return acc;
        }, {});
        const keysToCheck = ['method', 'source_channel'];
        keysToCheck.forEach((key) => {
            if (searchParams[key]?.includes('all')) {
                searchParams[key] = '';
            }
        });
        Promise.resolve(handleSearch(searchParams)).finally(dismissModal);
    }, [filters, handleSearch, dismissModal]);

    return (
        <Modal isOpen={isOpen} onDismiss={dismissModal} size="small" accessibilityLabel="All Filters">
            <ModalHeader title="All Filters" leading={<FilterIcon />} />
            <ModalBody>
                <Box width="100%" marginTop="spacing.0">
                    <Dropdown selectionType="single">
                        <SelectInput
                            label="Payment Method"
                            name="payment_method"
                            placeholder="Select Payment Method"
                            defaultValue={filters.method}
                            onChange={({ values }) => handleFilterChange('method', values, true)}
                        />
                        <DropdownOverlay>
                            <ActionList>
                                {methodOptions?.map(({ title, value }) => (
                                    <ActionListItem key={value} title={title} value={value} />
                                ))}
                            </ActionList>
                        </DropdownOverlay>
                    </Dropdown>
                </Box>

                <Box width="100%" marginTop="spacing.6">
                    <Dropdown marginTop="spacing.3" selectionType="single">
                        <SelectInput
                            label="Channel"
                            name="source_channel"
                            placeholder="Select Source Channel"
                            defaultValue={filters.channel}
                            onChange={({ values }) => handleFilterChange('channel', values)}
                        />
                        <DropdownOverlay>
                            <ActionList>
                                {channelOptions?.map(({ title, value }) => (
                                    <ActionListItem key={value} title={title} value={value} />
                                ))}
                            </ActionList>
                        </DropdownOverlay>
                    </Dropdown>
                </Box>

                {isStoreHierarchyEnabled ? (
                    <Box>
                        <Box width="100%" marginTop="spacing.6">
                            <TextInput
                                placeholder="Search"
                                size="medium"
                                label="Device Serial Number"
                                marginTop="spacing.3"
                                value={filters.deviceId}
                                onChange={({ value }) => handleFilterChange('deviceId', value)}
                            />
                        </Box>

                        <Box width="100%" marginTop="spacing.6">
                            <TreeSelect
                                value={filters.storeId}
                                onChange={(selectedValues) => {
                                    setStoreIdCount(selectedValues.length);
                                    handleFilterChange('storeId', selectedValues)}
                                }
                                treeData={stores}
                                label="Hierarchy Level"
                                maxCount={storeIdCount > 10 ? 0 : 10}
                                placeholder="Search"
                                labelSize="small"
                                labelColor="surface.text.gray.muted"
                                showTooltip={true}
                                tooltipText={"Choose a hierarchy level to view your data by, down to individual stores."}
                                showFooterText={true}
                            />
                        </Box>
                    </Box>
                ) : null}
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
