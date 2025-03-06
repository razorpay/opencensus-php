import {
  ArrowLeftIcon,
  Box,
  Button,
  Heading,
  IconButton,
  StepGroup,
  StepItem,
  Text,
  Modal,
  ModalHeader,
  ModalFooter,
  StepItemIcon,
  CheckIcon,
  StepItemIndicator,
  AlertOnlyIcon,
  useToast,
  CheckCircleIcon,
  ZapIcon,
  Divider,
  ToastContainer,
  ModalBody,
} from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useForm, useFieldArray, FormProvider } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import moment from 'moment';
import TriggersAndActions from './TriggersAndActions';
import BurnRules from './BurnRules';
import CampaignSettings from './CampaignSettings';
import ReviewAndPublish from './ReviewAndPublish';
import { formSchema } from './formSchema';
import { CampaignType, CampaignTypeEnum, FormData, Wallet } from './types';
import { createProgramForCampaign, createCampaign } from '../queries';
import { combineEpochAndTime, generateRuleString, createUsageLimits } from './utils';
import { CONSTANT_ACTION_CONFIGS, DURATION_PRESETS } from './constants';

const FORM_STEPS = {
  0: {
    component: TriggersAndActions,
    title: 'Trigger & Action',
    fields: [
      'triggerEvent',
      'triggerAttributes',
      'triggerAction',
      'selectedWallet',
      'creditType',
      'creditAmount',
      'triggerActionAttribute',
      'noMaxLimit',
      'maxCredit',
    ],
  },
  1: {
    component: BurnRules,
    title: 'Burn Rules',
    fields: ['expiryDuration', 'expiryDurationPreset', 'minOrderValue'],
  },
  2: {
    component: CampaignSettings,
    title: 'Campaign Settings',
    fields: [
      'startDate',
      'startTime',
      'startImmediately',
      'endDate',
      'endTime',
      'noEndDate',
      'campaignLimitAmountEnabled',
      'campaignLimitAmount',
      'campaignLimitAmountPeriod',
      'campaignLimitActionsEnabled',
      'campaignLimitActions',
      'campaignLimitActionsPeriod',
      'userLimitAmountEnabled',
      'userLimitAmount',
      'userLimitAmountPeriod',
      'userLimitActionsEnabled',
      'userLimitActions',
      'userLimitActionsPeriod',
    ],
  },
  3: {
    component: ReviewAndPublish,
    title: 'Review & Publish',
    fields: [],
  },
};

const CreateNewCampaign = ({ mode, merchant_id }) => {
  const [selectedStep, setSelectedStep] = useState(0);
  const [stepProgress, setStepProgress] = useState({
    0: { validation: 'invalid', touched: false },
    1: { validation: 'invalid', touched: false },
    2: { validation: 'invalid', touched: false },
    3: { validation: 'invalid', touched: false },
  });
  const [campaignConfirmationModalVisible, setCampaignConfirmationModalVisible] = useState(false);
  const [campaignCreating, setCampaignCreating] = useState(false);

  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const campaignName = searchParams.get('campaignName');
  const campaignType = searchParams.get('campaignType') as CampaignType | null;

  const queryClient = useQueryClient();
  const { show } = useToast();

  const methods = useForm<FormData>({
    defaultValues: {
      triggerEvent: '',
      triggerAttributes: [],
      triggerAction: '',
      selectedWallet: '',
      creditType: 'flat',
      creditAmount: '',
      triggerActionAttribute: '',
      noMaxLimit: false,
      maxCredit: '',
      expiryDuration: '',
      expiryDurationPreset: DURATION_PRESETS[0].value, //default to days
      minOrderValue: '',
      startDate: null,
      startTime: '',
      startImmediately: false,
      endDate: null,
      endTime: '',
      noEndDate: false,
      campaignLimitAmountEnabled: false,
      campaignLimitAmount: '',
      campaignLimitAmountPeriod: '',
      campaignLimitActionsEnabled: false,
      campaignLimitActions: '',
      campaignLimitActionsPeriod: '',
      userLimitAmountEnabled: false,
      userLimitAmount: '',
      userLimitAmountPeriod: '',
      userLimitActionsEnabled: false,
      userLimitActions: '',
      userLimitActionsPeriod: '',
    },
    resolver: yupResolver(formSchema),
  });

  const { control, trigger, handleSubmit, getFieldState } = methods;

  const { fields, append, remove, update } = useFieldArray({
    control,
    name: 'triggerAttributes',
  });

  const StepComponent = FORM_STEPS[selectedStep].component;

  const updateStepState = (forceTouchAllSteps: boolean) => {
    const stepUpdates = {
      ...stepProgress,
    };

    // Mark the step as invalid if any of the fields in the step have errors
    Object.keys(FORM_STEPS).forEach((step) => {
      if (FORM_STEPS[selectedStep].fields.some((field) => getFieldState(field).error)) {
        stepUpdates[selectedStep].validation = 'invalid';
      } else {
        stepUpdates[selectedStep].validation = 'valid';
      }
    });

    stepUpdates[selectedStep].touched = true;

    // Set all previous steps as touched when publish campaign submit button is clicked in the last step
    if (forceTouchAllSteps) {
      Object.values(stepUpdates).forEach((step) => {
        step.touched = true;
      });
    }

    // If any step is invalid, keep the last step also invalid
    if (Object.values(stepUpdates).some((step) => step.validation === 'invalid')) {
      stepUpdates[3].validation = 'invalid';
    }

    setStepProgress(stepUpdates);
  };

  const handleStepClick = (event, step) => {
    setSelectedStep(step);
  };

  const handleBackClick = () => {
    setSelectedStep((currentStep) => currentStep - 1);
  };

  const handleNextClick = async () => {
    try {
      if (selectedStep === 3) {
        const isValid = await trigger();
        updateStepState(true);
        if (isValid) {
          setCampaignConfirmationModalVisible(true);

          //Modal is rendered outside of form through portal, so need to attach form to submit button(which is inside the modal)
          setTimeout(() => {
            const modalSubmitButton = document.querySelector(
              '[data-testid="campaign-submit-button"]',
            );
            if (modalSubmitButton) {
              modalSubmitButton.setAttribute('form', 'campaign-creation-form');
            }
          }, 100);
        }
        return;
      }

      const isStepValid = await trigger(FORM_STEPS[selectedStep].fields);
      updateStepState(false);
      if (isStepValid) {
        setSelectedStep((currentStep) => currentStep + 1);
      }
    } catch (error) {
      /**
       * Do nothing
       */
    }
  };

  const onSubmit = async (data) => {
    try {
      setCampaignCreating(true);
      const wallets: Array<Wallet> =
        queryClient.getQueryData(['wallet:campaign:availableWallets', mode]) || [];

      const eventConfig: Array<any> =
        queryClient.getQueryData(['wallet:campaign:triggerEvents', mode]) || [];

      const selectedWalletName = wallets?.find((wallet) => wallet.id === data.selectedWallet)
        ?.name as string;

      const selectedEventName = eventConfig?.find((event) => event.id === data.triggerEvent).name;

      let daysToExpiry = data.expiryDuration;

      if (data.expiryDurationPreset === 'months') {
        daysToExpiry = daysToExpiry * 30; // 30 days in a month, as per backend requirements
      } else if (data.expiryDurationPreset === 'years') {
        daysToExpiry = daysToExpiry * 360; // 12 months * 30 days, as per backend requirements
      }

      const programCreationPayload = {
        points_expiry: daysToExpiry,
        minimum_order_value: data.minOrderValue * 100,
        wallet_name: selectedWalletName,
      };

      const programCreatedData = await createProgram({
        mode,
        payload: programCreationPayload,
      });

      const campaignCreationPayload = {
        campaign_name: campaignName,
        event_config_id: data.triggerEvent,
        campaign_starts_at: data.startImmediately
          ? moment().unix()
          : combineEpochAndTime(data.startDate, data.startTime),
        campaign_ends_at: data.noEndDate ? null : combineEpochAndTime(data.endDate, data.endTime),
        rules: [
          {
            rule: {
              type: 'DIRECT_EVALUATION',
              main_rule:
                data.triggerAttributes.length > 0
                  ? generateRuleString(data.triggerAttributes)
                  : 'true', //If there are no attributes, then send this as default rule
              has_filter: false,
              filter_rule: '()',
            },
            actions: [
              {
                type: data.triggerAction,
                max_points: data.noMaxLimit ? null : data.maxCredit * 100,
                config: {
                  amount: {
                    type: data.creditType === 'flat' ? 'constant' : 'expression',
                    value:
                      data.creditType === 'flat'
                        ? data.creditAmount * 100
                        : `${data.creditAmount}*${data.triggerActionAttribute}/100`,
                    is_points_field: true,
                  },
                  ...(selectedEventName === 'order_placed'
                    ? {
                        ...CONSTANT_ACTION_CONFIGS.ORDER_PLACED,
                        program_id: {
                          type: 'constant',
                          value: programCreatedData.id,
                        },
                        merchant_id: {
                          type: 'constant',
                          value: merchant_id,
                        },
                      }
                    : selectedEventName === 'wallet_credit'
                    ? {
                        program_id: {
                          type: 'constant',
                          value: programCreatedData.id,
                        },
                        merchant_id: {
                          type: 'constant',
                          value: merchant_id,
                        },
                        ...CONSTANT_ACTION_CONFIGS.CREDIT_WALLET,
                      }
                    : {}),
                },
              },
            ],
          },
        ],
        //There are 4 types of limits: Campaign Limit Amount, Campaign Limit Actions, User Limit Amount, User Limit Actions. If any of these limits are enabled, then only send usage_limits object in payload else don't send it. So if no limits are enabled, then don't send usage_limits object in payload.
        ...createUsageLimits({
          campaignLimitAmountEnabled: data.campaignLimitAmountEnabled,
          campaignLimitAmount: data.campaignLimitAmount,
          campaignLimitAmountPeriod: data.campaignLimitAmountPeriod,
          campaignLimitActionsEnabled: data.campaignLimitActionsEnabled,
          campaignLimitActions: data.campaignLimitActions,
          campaignLimitActionsPeriod: data.campaignLimitActionsPeriod,
          userLimitAmountEnabled: data.userLimitAmountEnabled,
          userLimitAmount: data.userLimitAmount,
          userLimitAmountPeriod: data.userLimitAmountPeriod,
          userLimitActionsEnabled: data.userLimitActionsEnabled,
          userLimitActions: data.userLimitActions,
          userLimitActionsPeriod: data.userLimitActionsPeriod,
        }),
      };

      await initiateCampaignCreation({
        mode,
        payload: campaignCreationPayload,
      });
      setCampaignCreating(false);
      show({
        type: 'informational',
        content: 'Campaign Created!',
        color: 'positive',
        leading: CheckCircleIcon,
      });
      navigate('/wallet/campaigns?created=true', { replace: true });
    } catch (error) {
      show({
        type: 'informational',
        content: 'Error in creating campaign, please try again.',
        color: 'negative',
      });
      setCampaignCreating(false);
    }
  };

  const onError = (errors) => {};

  const { mutateAsync: createProgram } = useMutation({
    mutationFn: createProgramForCampaign,
  });

  const { mutateAsync: initiateCampaignCreation } = useMutation({
    mutationFn: createCampaign,
  });

  //Blade step-item component uses button which has type="submit" by default if used inside a form which causes form submission on click of step-item. This useEffect is to change the type to button.
  useEffect(() => {
    document.querySelectorAll('div[data-blade-component="step-item"] button').forEach((item) => {
      item.setAttribute('type', 'button');
    });

    if (!campaignName) {
      navigate('/wallet/campaigns', { replace: true });
    }
  }, []);

  const getStepMarker = (step: number) => {
    if (selectedStep === step) {
      return <StepItemIndicator color="primary" />;
    }

    if (stepProgress[step].validation === 'valid') {
      return <StepItemIcon icon={CheckIcon} color="positive" />;
    }

    if (stepProgress[step].validation === 'invalid' && stepProgress[step].touched) {
      return <StepItemIcon icon={AlertOnlyIcon} color="negative" />;
    }

    return <StepItemIndicator color="neutral" />;
  };

  return (
    <FormProvider {...methods}>
      <form onSubmit={handleSubmit(onSubmit, onError)} id="campaign-creation-form">
        <Box
          display="flex"
          flexDirection="column"
          flex={1}
          minHeight="100vh"
          backgroundColor="surface.background.gray.intense"
        >
          <Box
            height="80px"
            display="flex"
            alignItems="center"
            paddingLeft="spacing.11"
            backgroundColor="surface.background.cloud.intense"
          >
            <IconButton
              icon={ArrowLeftIcon}
              accessibilityLabel="back"
              onClick={() => navigate('/wallet/campaigns', { replace: true })}
              emphasis="subtle"
              size="large"
            />
            <Heading
              size="xlarge"
              weight="semibold"
              marginLeft="spacing.3"
              color="interactive.text.staticWhite.normal"
            >
              {campaignName}
            </Heading>
            {/**
             * Currenlty support only trigger based campaigns
             */}
            {campaignType === CampaignTypeEnum.TRIGGER ? (
              <Box
                paddingX="spacing.3"
                paddingY="spacing.2"
                display="flex"
                alignItems="center"
                marginLeft="spacing.3"
                backgroundColor="surface.background.gray.intense"
                borderRadius="large"
              >
                <ZapIcon color="surface.icon.primary.normal" marginRight="spacing.2" />
                <Text size="small" color="surface.text.gray.normal">
                  Trigger Based
                </Text>
              </Box>
            ) : null}
          </Box>
          <Box display="flex" flex={1} width="100%" paddingX="spacing.11" justifyContent="center">
            <Box display="flex" flex={1} maxWidth="1024px">
              <Box paddingTop="spacing.6">
                <Text
                  variant="body"
                  size="small"
                  weight="semibold"
                  color="surface.text.gray.muted"
                  marginLeft="spacing.3"
                >
                  Steps
                </Text>
                <StepGroup>
                  {Object.keys(FORM_STEPS).map((step) => {
                    return (
                      <StepItem
                        key={step}
                        isSelected={selectedStep === Number(step)}
                        title={FORM_STEPS[step].title}
                        onClick={(event) => handleStepClick(event, Number(step))}
                        marker={getStepMarker(Number(step))}
                      />
                    );
                  })}
                </StepGroup>
              </Box>
              <Box
                borderLeftWidth="thin"
                borderLeftColor="surface.border.gray.muted"
                paddingLeft="spacing.6"
                paddingY="spacing.6"
                paddingRight="spacing.11"
                display="flex"
                flex={1}
              >
                <StepComponent
                  append={append}
                  fields={fields}
                  remove={remove}
                  update={update}
                  mode={mode}
                />
              </Box>
            </Box>
          </Box>
          <Box
            display="flex"
            width="100%"
            position="sticky"
            bottom="spacing.0"
            backgroundColor="surface.background.gray.intense"
            borderTopWidth="thin"
            borderTopColor="surface.border.gray.muted"
            justifyContent="center"
          >
            <Box
              display="flex"
              justifyContent="flex-end"
              maxWidth="1024px"
              width="100%"
              paddingY="spacing.6"
              paddingX="spacing.11"
            >
              {selectedStep === 0 ? null : (
                <Button
                  type="button"
                  variant="tertiary"
                  marginRight="spacing.5"
                  onClick={handleBackClick}
                >
                  Back
                </Button>
              )}
              <Button type="button" onClick={handleNextClick}>
                {selectedStep === 3 ? 'Publish Campaign' : 'Next'}
              </Button>
            </Box>
          </Box>
        </Box>
        <Modal
          isOpen={campaignConfirmationModalVisible}
          onDismiss={() => {
            setCampaignConfirmationModalVisible(false);
          }}
          size="small"
        >
          <ModalBody>
            <Text size="large" weight="semibold">
              Please review your campaign rules carefully before publishing it.
            </Text>
            <Text color="surface.text.gray.subtle" marginTop="spacing.3">
              Once published, your customers will start earning and redeeming points in their
              wallets.
            </Text>
          </ModalBody>
          <ModalFooter>
            <Box display="flex" justifyContent="flex-end">
              <Button
                type="button"
                variant="tertiary"
                marginRight="spacing.5"
                onClick={() => {
                  setCampaignConfirmationModalVisible(false);
                }}
                isDisabled={campaignCreating}
              >
                Go Back
              </Button>
              <Button type="submit" isLoading={campaignCreating} testID="campaign-submit-button">
                Publish Campaign
              </Button>
            </Box>
          </ModalFooter>
        </Modal>
        <ToastContainer />
      </form>
    </FormProvider>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchant_id: state.session?.user.current,
}))(CreateNewCampaign);
