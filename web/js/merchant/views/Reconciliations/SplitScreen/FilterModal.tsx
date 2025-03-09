import React from 'react';
import {
  Box,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Button,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  FilterIcon,
  IconButton,
  CloseIcon,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useFormik } from 'formik';
import { FilterModalProps } from 'merchant/views/Reconciliations/SplitScreen/types';
import { useParams } from 'react-router-dom';
import styled from 'styled-components';

import { fetchReconStatusAndRemarkFiltersData } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

const FileteredForm = styled.form``;

const FilterModal: React.FC<FilterModalProps> = ({
  isFilterModalOpen,
  setIsFilterModalOpen,
  reconFilter,
  setReconFilter,
}) => {
  const { processId } = useParams();

  const {
    data: reconAdvanceFilter,
    isLoading: isLoadingForReconAdvanceFilter,
    isError: isErrorForReconAdvanceFilter,
  } = useQuery({
    queryKey: ['reconAdvanceFilter', processId],
    queryFn: () =>
      fetchReconStatusAndRemarkFiltersData({
        processIds: [processId],
      }),
    enabled: !!isFilterModalOpen,
  });

  const formik = useFormik({
    initialValues: {
      recon_statuses: reconFilter.recon_statuses || '',
      recon_remarks: reconFilter.recon_remarks || '',
    },
    onSubmit: (values) => {
      setReconFilter((prevFilterState) => ({
        ...prevFilterState,
        'Recon Status': values.recon_statuses ? values.recon_statuses : '',
        'Recon Remark': values.recon_remarks ? values.recon_remarks : '',
      }));
      setIsFilterModalOpen(false);
    },
  });

  return (
    <Modal
      isOpen={isFilterModalOpen}
      onDismiss={() => {
        formik.handleSubmit();
        setIsFilterModalOpen(false);
      }}
    >
      <ModalHeader
        title="Advance Filters"
        leading={<FilterIcon size="large" color="surface.icon.gray.subtle" />}
      />
      <ModalBody>
        <RenderErrorLoadingOrChild
          isLoading={isLoadingForReconAdvanceFilter}
          isError={isErrorForReconAdvanceFilter}
        >
          <FileteredForm id="filterForm" onSubmit={formik.handleSubmit}>
            <Box display="flex" flexDirection="column" gap="spacing.6">
              <Box
                display="grid"
                gridTemplateColumns="1.9fr 0.1fr"
                gap="spacing.3"
                width="100%"
                alignItems="center"
                justifyContent="center"
              >
                <Dropdown selectionType="single">
                  <SelectInput
                    label="Recon Status"
                    placeholder="Recon Status"
                    name="recon_statuses"
                    value={formik.values.recon_statuses || ''}
                    onChange={({ name, values }) => {
                      if (name) {
                        formik.setFieldValue(name, values[0]);
                      }
                    }}
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {reconAdvanceFilter &&
                      reconAdvanceFilter?.data?.recon_filters?.recon_statuses?.length
                        ? reconAdvanceFilter.data.recon_filters.recon_statuses.map((status) => {
                            return <ActionListItem key={status} title={status} value={status} />;
                          })
                        : null}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
                <Box
                  display="flex"
                  alignItems="center"
                  justifyContent="center"
                  marginTop="27px"
                  height="36px"
                >
                  <IconButton
                    icon={CloseIcon}
                    accessibilityLabel="Clear"
                    onClick={() => formik.setFieldValue('recon_statuses', '')}
                    isDisabled={formik.values.recon_statuses.length === 0}
                  />
                </Box>
              </Box>
              <Box
                display="grid"
                gridTemplateColumns="1.9fr 0.1fr"
                gap="spacing.3"
                width="100%"
                alignItems="center"
              >
                <Dropdown selectionType="single">
                  <SelectInput
                    label="Recon Remarks"
                    placeholder="Recon Remarks"
                    name="recon_remarks"
                    value={formik.values.recon_remarks || ''}
                    onChange={({ name, values }) => {
                      if (name) {
                        formik.setFieldValue(name, values[0]);
                      }
                    }}
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {reconAdvanceFilter &&
                      reconAdvanceFilter?.data?.recon_filters?.recon_remarks?.length
                        ? reconAdvanceFilter.data.recon_filters.recon_remarks.map((remark) => {
                            return <ActionListItem key={remark} title={remark} value={remark} />;
                          })
                        : null}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>

                <Box
                  display="flex"
                  alignItems="center"
                  justifyContent="center"
                  marginTop="27px"
                  height="36px"
                >
                  <IconButton
                    icon={CloseIcon}
                    accessibilityLabel="Clear"
                    onClick={() => formik.setFieldValue('recon_remarks', '')}
                    isDisabled={formik.values.recon_remarks.length === 0}
                  />
                </Box>
              </Box>
            </Box>
          </FileteredForm>
        </RenderErrorLoadingOrChild>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="end" alignItems="center" gap="spacing.4">
          <Button variant="tertiary" onClick={() => setIsFilterModalOpen(false)}>
            Cancel
          </Button>
          <Button variant="primary" type="submit" onClick={() => formik.handleSubmit()}>
            Apply
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default FilterModal;
