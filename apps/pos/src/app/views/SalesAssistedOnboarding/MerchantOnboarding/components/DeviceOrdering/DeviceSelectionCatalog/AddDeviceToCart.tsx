import {
  Box,
  Button,
  CheckCircleIcon,
  ChevronRightIcon,
  Divider,
  Drawer,
  DrawerBody,
  DrawerHeader,
  EditIcon,
  Heading,
  Link,
  useToast,
} from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import AddDeviceToCartHeader from './AddDeviceToCartHeader';
import DeviceFee from './DeviceFee';
import OptionalFeatures from './OptionalFeatures';
import PlanSelectionCard from './PlanSelectionCard';
import {
  DeviceFees,
  DeviceOptionalFeatures,
  MODULAR_FLAGS,
  RentalChargeFrequencyLabels,
} from 'apps/pos/src/app/constants/DeviceSelection';
import {
  EditDeviceInCartForm,
  MODULAR_DEVICE_FIELDS,
} from 'apps/pos/src/app/types/DeviceSelection';
import { DeviceConfig, ModularPayload, PlanConfig } from 'apps/pos/src/app/types/modular';
import { DeviceModel } from 'apps/pos/src/app/utils/deviceSelection';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import { processFormDataForModularSubmit } from 'apps/pos/src/app/utils/modularConfig';
import { analyticsTypes, trackEvent } from 'apps/pos/src/services/analytics';

interface AddDeviceToCartProps {
  deviceConfig: DeviceConfig;
  defaultValues?: EditDeviceInCartForm;
  isEditFlow?: boolean;
  isDisabled?: boolean;
  isDeviceAlreadyAdded?: boolean;
  isUpdateModularLoading?: boolean;
  isPosEkycAgent?: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const AddDeviceToCart = ({
  deviceConfig,
  defaultValues,
  isEditFlow,
  isDisabled,
  isUpdateModularLoading,
  isDeviceAlreadyAdded,
  isPosEkycAgent,
  handleModularUpdate,
}: AddDeviceToCartProps): JSX.Element => {
  const [isDetailsOpen, setIsDetailsOpen] = useState<boolean>(false);
  const toast = useToast();

  const methods = useForm({
    defaultValues: defaultValues ?? {
      ...deviceConfig.defaultValues,
      [MODULAR_DEVICE_FIELDS.DEVICE_NAME]: deviceConfig.title as string,
    },
  });

  const { handleSubmit, getValues, watch, reset } = methods;

  const onModularUpdate = () => {
    setIsDetailsOpen(false);
    if (!isEditFlow) reset();
    toast.show({
      color: 'positive',
      content: `Successfully ${isEditFlow ? 'edited' : 'added'} ${deviceConfig.title} to cart`,
      leading: CheckCircleIcon,
      autoDismiss: true,
    });
  };

  const { isMobile } = useScreen();

  const { rateConfig } = deviceConfig;
  const latestRateConfig = rateConfig?.find((config) => !!config?.active);

  const toggleDetails = (): void => {
    if (isUpdateModularLoading) return;

    // Tracking only add events //
    if (!isEditFlow) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Add Device',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EXPLORATION,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_DESCRIPTION,
          section: 'Device EXploration',
          subSection: deviceConfig?.title,
        },
      });
    }

    setIsDetailsOpen((prevState) => !prevState);
  };

  const handleAddToCartFormSubmit = (form): void => {
    const processedFormData = processFormDataForModularSubmit(form);
    const payload: ModularPayload = {
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: onModularUpdate,
      ...processedFormData,
    };

    // Track add to cart //
    if (!isEditFlow) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Add to cart',
          section: 'Device Editing',
          subSection: 'POS Product Editing',
          pageType: analyticsTypes.PAGE_TYPES.DEVICE_EDITING,
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_EDITING,
        },
      });
    }

    handleModularUpdate(payload);
  };

  const onCancelClick = (): void => {
    if (isUpdateModularLoading) return;

    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Cancel',
        section: 'Device Editing',
        subSection: 'POS Product Editing',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_EDITING,
      },
    });

    setIsDetailsOpen((prevState) => !prevState);
  };

  const onDrawerDismissClick = (): void => {
    if (isUpdateModularLoading) return;

    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Close Icon',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_EDITING,
      },
    });

    setIsDetailsOpen((prevState) => !prevState);
  };

  const addDeviceText = isDeviceAlreadyAdded ? 'Add Another Device' : 'Add Device';

  const handleProductDelete = (): void => {
    const payload: ModularPayload = {
      [MODULAR_DEVICE_FIELDS.DEVICE_CART_ID_FIELD]: getValues(MODULAR_DEVICE_FIELDS.DEVICE_ID),
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: onModularUpdate,
      ...MODULAR_FLAGS.DELETE_CART_ITEM,
    };

    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Delete Icon',
        section: 'Device Editing',
        subSection: 'POS Product Editing',
        pageType: analyticsTypes.PAGE_TYPES.DEVICE_EDITING,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_EDITING,
      },
    });
    handleModularUpdate(payload);
  };

  const handleDrawerOpen = () => {
    if (isPosEkycAgent) {
      const payload: ModularPayload = {
        [MODULAR_DEVICE_FIELDS.DEVICE_NAME]: deviceConfig.title,
        [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: toggleDetails,
      };
      handleModularUpdate(payload);
    } else {
      toggleDetails();
    }
  };

  const devicePlan = watch(MODULAR_DEVICE_FIELDS.DEVICE_PLAN);
  const selectionPlanRate = latestRateConfig?.plans?.find((plan) => plan?.planName === devicePlan);

  useEffect(() => {
    // Track whenever the drawer is opened
    if (isDetailsOpen) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          pageType: analyticsTypes.PAGE_TYPES.DEVICE_EDITING,
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAGE_VIEW,
        },
      });
    }
  }, [isDetailsOpen]);

  const shouldShowOptionalFeatures = () => {
    if (!isPosEkycAgent) {
      //sales flow
      if (selectionPlanRate?.rentalCharge) return true;
    } else if (deviceConfig.title === DeviceModel.SOUNDBOX_KIT && selectionPlanRate?.rentalCharge) {
      //partner flow
      return true;
    }
    return false;
  };

  return (
    <React.Fragment>
      {isMobile || isEditFlow ? (
        <Link
          variant="button"
          icon={isEditFlow ? EditIcon : ChevronRightIcon}
          onClick={handleDrawerOpen}
          iconPosition={isEditFlow ? 'left' : 'right'}
          isDisabled={isDisabled}
          size="small"
        >
          {isEditFlow ? 'Edit' : addDeviceText}
        </Link>
      ) : (
        <Button variant="secondary" onClick={handleDrawerOpen} isDisabled={isDisabled} isFullWidth>
          {isEditFlow ? 'Edit' : addDeviceText}
        </Button>
      )}
      <Drawer isOpen={isDetailsOpen} onDismiss={onDrawerDismissClick}>
        <DrawerHeader title="Device Selection" />
        <DrawerBody>
          <FormProvider {...methods}>
            <form onSubmit={handleSubmit(handleAddToCartFormSubmit)}>
              <AddDeviceToCartHeader
                deviceConfig={deviceConfig}
                isEditFlow={!!isEditFlow}
                onDelete={handleProductDelete}
                isLoading={isUpdateModularLoading}
              />
              <Divider orientation="horizontal" width="100%" marginBottom="spacing.5" />
              <Heading weight="semibold" marginBottom="spacing.5">
                Plan Selection
              </Heading>
              <PlanSelectionCard plans={latestRateConfig?.plans as PlanConfig[]} />
              <Divider orientation="horizontal" width="100%" marginBottom="spacing.5" />
              {!isPosEkycAgent ? (
                <Box testID="device-fees-container">
                  {DeviceFees.map((deviceFee, index) =>
                    deviceFee?.isHidden?.(selectionPlanRate as PlanConfig) ? null : (
                      <React.Fragment key={deviceFee.field}>
                        <DeviceFee deviceFee={deviceFee} />
                        {index !== DeviceFees.length - 1 && selectionPlanRate?.rentalCharge ? (
                          <Divider orientation="horizontal" width="100%" marginBottom="spacing.5" />
                        ) : null}
                      </React.Fragment>
                    ),
                  )}
                </Box>
              ) : null}
              {shouldShowOptionalFeatures()
                ? DeviceOptionalFeatures.map((optionalFeature) => {
                    let updatedOptionalFeature = {
                      ...optionalFeature,
                      title: RentalChargeFrequencyLabels[devicePlan] ?? '',
                    };
                    return (
                      <OptionalFeatures
                        key={optionalFeature.field}
                        optionalFeature={updatedOptionalFeature}
                      />
                    );
                  })
                : null}
              <Box marginBottom="spacing.6" display="flex" alignItems="center">
                <Button
                  variant="tertiary"
                  marginRight="spacing.5"
                  onClick={onCancelClick}
                  isFullWidth
                >
                  Cancel
                </Button>

                <Button
                  type="submit"
                  isLoading={isUpdateModularLoading}
                  isDisabled={!latestRateConfig?.plans?.length}
                  isFullWidth
                >
                  {isEditFlow ? 'Update Cart' : 'Add to Cart'}
                </Button>
              </Box>
            </form>
          </FormProvider>
        </DrawerBody>
      </Drawer>
    </React.Fragment>
  );
};

export default AddDeviceToCart;
