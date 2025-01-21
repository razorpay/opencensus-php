import React, { useEffect, useMemo, useState } from 'react';
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  Box,
  Button,
  Card,
  CardBody,
  CheckIcon,
  Heading,
  StepGroup,
  StepItem,
  StepItemIcon,
  useToast,
  Spinner,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Text,
} from '@razorpay/blade/components';
import { Formik, FormikProps } from 'formik';
import { useNavigate, useSearchParams } from 'react-router-dom';

import Breadcrumbs from 'merchant/views/BillMeSettings/common/components/Breadcrumbs';

import StoreBasicDetails from './containers/StoreBasicDetails';
import StoreLinkedProducts from './containers/StoreLinkedProducts';
import useStoreCreateMutation, {
  convertFormToStoreCreatePayload,
  convertStoreResponseToForm,
  convertTerminalResponseToForm,
} from './hooks/useStoreCreateMutation';
import { useMutation } from '@tanstack/react-query';
import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import useStoreUpdateMutation from './hooks/useStoreUpdateMutation';
import { STORE_TERMINALS_STATUS_BULK_UPDATE } from './mutations';
import { StoreCreateSchema } from './schemas';
import { useStoresCreateStore } from './stores/storesCreateFormStore';
import { StoreCreateFormValues, TerminalFormValues } from './types';
import { PAGE_BREADCRUMBS } from './constants';
import useStoreByIdQuery from './hooks/useStoreByIdQuery';
import useStoreTerminalQuery from './hooks/useStoreTerminalQuery';
import DeleteTerminalModal from './components/DeleteTerminalModal';
import useStoreTerminalDeleteMutation from './hooks/useStoreTerminalDeleteMutation';

const StoreCreateOrEdit = () => {
  const navigate = useNavigate();
  const [currentStep, setCurrentStep] = React.useState(0);
  const storeCreateFormRef = React.useRef<FormikProps<StoreCreateFormValues>>(null);
  const storeTerminalFormRef = React.useRef<FormikProps<TerminalFormValues>>(null);
  const { basicInfoForm, setBasicInfoForm, deleteTerminalModal, setDeleteTerminalModal } =
    useStoresCreateStore();
  const [searchParams, setSearchParams] = useSearchParams();
  const [terminalFormData, setTerminalFormData] = useState<TerminalFormValues>({
    billingTerminals: [],
  });
  const [showUnsavedAlertInfo, setShowUnsavedAlertInfo] = useState({ status: false, step: 0 });
  const id = searchParams.get('id');

  const toast = useToast();

  const { storeData, isGetStoreLoading, isGetStoreRefetching } = useStoreByIdQuery({
    id,
    currentStep,
  });

  const { storeTerminalsResponse, isStoreTerminalsResponseFetching } = useStoreTerminalQuery({
    id,
    currentStep,
  });

  const { createStore, isLoading: isCreateStoreLoading } = useStoreCreateMutation({
    onSuccessHandler: (data) => {
      if (data?.storeCreate?.success === false) {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: data?.storeCreate?.message,
        });
        return;
      }
      toast.show({
        type: 'informational',
        color: 'positive',
        content: 'New Store has been added succesfully',
      });
      setSearchParams({ id: data?.storeCreate?.store?.id });
      setCurrentStep(1);
    },
    onErrorHandler: () => {
      toast.show({
        type: 'informational',
        color: 'negative',
        content: 'Failed to create store',
      });
    },
  });

  const { updateStore, isLoading: isUpdateStoreLoading } = useStoreUpdateMutation({
    onSuccessHandler: (data) => {
      if (data?.storeUpdate?.success === false) {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: data?.storeUpdate?.message,
        });
        return;
      }
      toast.show({
        type: 'informational',
        color: 'positive',
        content: 'Store has been updated succesfully',
      });
      setCurrentStep(1);
    },
    onErrorHandler: () => {},
  });

  const { mutate: storeTerminalsBulkUpdateMutate, isLoading: isBulkUpdateTerminalsLoading } =
    useMutation({
      mutationFn: () =>
        graphqlRequestMutation({
          document: STORE_TERMINALS_STATUS_BULK_UPDATE,
          variables: { storeId: id, isActive: false },
        }),
      onSuccess: (data) => {
        toast.show({
          type: 'informational',
          color: 'positive',
          content: data?.storeTerminalsStatusBulkUpdate?.message,
        });
      },
      onError: () => {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: "Failed to update linked product's terminals status",
        });
      },
    });
  const { deleteStoreTerminal, isLoading: isDeleteStoreTerminalLoading } =
    useStoreTerminalDeleteMutation({
      onSuccessHandler: () => {},
      onErrorHandler: () => {},
    });

  useEffect(() => {
    const handleBeforeUnload = (event: BeforeUnloadEvent) => {
      if (storeTerminalFormRef?.current) {
        const isTerminalsEditing =
          storeTerminalFormRef?.current?.values?.billingTerminals?.filter(
            (terminal) => terminal.isEditing,
          )?.length > 0;
        const isStoreInfoEditing = storeCreateFormRef?.current?.dirty;
        if (isStoreInfoEditing || isTerminalsEditing) {
          event.preventDefault();
          // Included for legacy support, e.g. Chrome/Edge < 119
          event.returnValue = '';
        }
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, [storeCreateFormRef, storeTerminalFormRef]);

  useEffect(() => {
    if (storeData?.storeById.dates?.deletedAt) {
      navigate(`/store-settings/stores-list/${id}`);
    } else {
      const formData = convertStoreResponseToForm(storeData?.storeById);
      setBasicInfoForm(formData);
    }
  }, [storeData]);

  const storeTerminals = storeTerminalsResponse?.storeTerminals?.storeTerminals;

  useEffect(() => {
    const terminalData = convertTerminalResponseToForm(storeTerminals);
    setTerminalFormData(terminalData);
  }, [storeTerminals]);

  const isLoading = useMemo(
    () => !!id && (isGetStoreRefetching || isGetStoreLoading),
    [id, isGetStoreRefetching, isGetStoreLoading],
  );

  const onPreviousClick = () => {
    if (storeTerminalFormRef?.current) {
      const isTerminalEditing =
        storeTerminalFormRef.current?.values?.billingTerminals?.filter(
          (terminal) => terminal.isEditing,
        )?.length > 0;
      if (isTerminalEditing) {
        setShowUnsavedAlertInfo({ status: true, step: 0 });
      } else {
        setCurrentStep(0);
      }
    }
  };

  const onNextClick = () => {
    if (storeTerminalFormRef?.current) {
      const isTerminalEditing =
        storeTerminalFormRef.current?.values?.billingTerminals?.filter(
          (terminal) => terminal.isEditing,
        )?.length > 0;
      if (isTerminalEditing) {
        setShowUnsavedAlertInfo({ status: true, step: 1 });
      } else {
        navigate(id ? `/store-settings/stores-list/${id}` : '/store-settings/stores-list');
      }
    }
  };
  const onUnsavedAlertSubmit = () => {
    if (showUnsavedAlertInfo?.step === 0) {
      setCurrentStep(0);
    } else {
      navigate(id ? `/store-settings/stores-list/${id}` : '/store-settings/stores-list');
    }
    setShowUnsavedAlertInfo({ status: false, step: 0 });
  };

  const onBasicDetailsSubmit = (values) => {
    const payload = convertFormToStoreCreatePayload(values);
    if (!payload.storeEmail) {
      payload.storeEmail = undefined;
    }
    if (id) {
      const updateStorePayload = { ...payload, id };
      if (basicInfoForm?.storeCode === updateStorePayload?.storeCode) {
        delete updateStorePayload.storeCode;
      }
      updateStore(updateStorePayload, {
        onSuccess: (data) => {
          if (
            basicInfoForm?.linkedProducts?.includes('DIGITAL_BILLING') &&
            !updateStorePayload?.linkedProducts?.includes('DIGITAL_BILLING')
          ) {
            storeTerminalsBulkUpdateMutate();
          }
          const storeData = data?.storeUpdate?.store;
          if (storeData) {
            const formData = convertStoreResponseToForm(storeData);
            setBasicInfoForm(formData);
          }
        },
      });
      return;
    }
    createStore(
      { ...payload },
      {
        onSuccess: (data) => {
          const storeData = data?.storeCreate?.store;
          if (storeData) {
            const formData = convertStoreResponseToForm(storeData);
            setBasicInfoForm(formData);
          }
        },
      },
    );
  };

  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <Modal
        isOpen={showUnsavedAlertInfo?.status}
        onDismiss={() => setShowUnsavedAlertInfo({ status: false, step: 0 })}
      >
        <ModalHeader title="Alert" />
        <ModalBody>
          <Text>Are you sure? All unsaved changes will be discarded.</Text>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
            <Button
              variant="tertiary"
              onClick={() => setShowUnsavedAlertInfo({ status: false, step: 0 })}
            >
              Cancel
            </Button>
            <Button onClick={onUnsavedAlertSubmit}>Sure</Button>
          </Box>
        </ModalFooter>
      </Modal>
      <DeleteTerminalModal
        modalProps={{
          isOpen: deleteTerminalModal.isOpen,
          onDismiss: () => {
            setDeleteTerminalModal({ isOpen: false });
          },
        }}
        isLoading={isDeleteStoreTerminalLoading}
        onSubmit={() => {
          deleteStoreTerminal(
            { id: deleteTerminalModal.terminalId },
            {
              onSuccess: (data) => {
                if (!data?.storeTerminalDelete?.success) {
                  toast.show({
                    type: 'informational',
                    color: 'negative',
                    content: data?.storeTerminalDelete?.message,
                  });
                  return;
                }
                toast.show({
                  type: 'informational',
                  color: 'positive',
                  content: 'Terminal deleted successfully',
                });
                const billingTerminals = storeTerminalFormRef?.current?.values;
                storeTerminalFormRef?.current?.setFieldValue(
                  'billingTerminals',
                  billingTerminals?.billingTerminals?.filter(
                    (_, index) => index !== deleteTerminalModal.index,
                  ),
                );
                setDeleteTerminalModal({ isOpen: false, terminalId: '', index: 0 });
              },
            },
          );
        }}
      />
      <Card backgroundColor="surface.background.gray.moderate">
        <CardBody>
          <Box
            display="flex"
            justifyContent="space-between"
            alignItems="center"
            flexDirection={{ base: 'column', l: 'row' }}
            gap={{ base: 'spacing.3', l: 'spacing.0' }}
          >
            <Breadcrumbs items={PAGE_BREADCRUMBS} backPath="/store-settings/stores-list" />
            <Box>
              {currentStep === 0 ? (
                <Button
                  variant="secondary"
                  icon={ArrowRightIcon}
                  iconPosition="right"
                  isLoading={
                    isCreateStoreLoading || isUpdateStoreLoading || isBulkUpdateTerminalsLoading
                  }
                  onClick={() => {
                    if (storeCreateFormRef.current) {
                      storeCreateFormRef.current.handleSubmit();
                    }
                  }}
                >
                  Save and Next
                </Button>
              ) : (
                <Box display="flex" gap="spacing.4">
                  <Button
                    icon={ArrowLeftIcon}
                    iconPosition="left"
                    variant="secondary"
                    onClick={onPreviousClick}
                  >
                    Previous
                  </Button>

                  <Button
                    icon={ArrowRightIcon}
                    iconPosition="right"
                    variant="primary"
                    type="submit"
                    color="positive"
                    onClick={onNextClick}
                  >
                    {id ? 'Update Store' : 'Stores List'}
                  </Button>
                </Box>
              )}
            </Box>
          </Box>
        </CardBody>
      </Card>
      <Box display="flex" gap="spacing.4" flexDirection={{ base: 'column', l: 'row' }}>
        <Box width={{ base: '100%', l: '30%' }}>
          <Card backgroundColor="surface.background.gray.moderate">
            <CardBody>
              <Heading size="medium" weight="semibold">
                New Store
              </Heading>
              <StepGroup orientation="vertical">
                <StepItem
                  title="Basic, Location, Contact and Linked Razorpay Products Basic Details"
                  marker={<StepItemIcon icon={CheckIcon} color="primary" />}
                  stepProgress={currentStep > 0 ? 'full' : 'start'}
                  isSelected={currentStep === 0}
                  onClick={() => setCurrentStep(0)}
                />
                <StepItem
                  title="Linked Razorpay Products Additional Details"
                  marker={<StepItemIcon icon={CheckIcon} color="primary" />}
                  stepProgress={currentStep > 1 ? 'full' : 'start'}
                  isSelected={currentStep === 1}
                  onClick={() => setCurrentStep(1)}
                />
              </StepGroup>
            </CardBody>
          </Card>
        </Box>

        <Box flex="1">
          {currentStep === 0 ? (
            <Formik
              enableReinitialize
              validateOnChange={false}
              initialValues={basicInfoForm}
              onSubmit={onBasicDetailsSubmit}
              validationSchema={StoreCreateSchema}
              innerRef={storeCreateFormRef}
            >
              {(props: FormikProps<any>) => (
                <form {...props}>
                  <StoreBasicDetails isLoading={isLoading} />
                </form>
              )}
            </Formik>
          ) : (
            <Formik
              enableReinitialize
              initialValues={terminalFormData}
              innerRef={storeTerminalFormRef}
              onSubmit={() => undefined}
            >
              {(props: FormikProps<any>) => (
                <form {...props}>
                  <StoreLinkedProducts isLoading={isLoading || isStoreTerminalsResponseFetching} />
                </form>
              )}
            </Formik>
          )}
        </Box>
      </Box>
    </Box>
  );
};

export default StoreCreateOrEdit;
