import {
  Box,
  Heading,
  Text,
  EditIcon,
  Button,
  TextInput,
  TextArea,
} from '@razorpay/blade/components';

const ProviderDetails = (props) => {
  const {
    isFormEdit,
    selectedProvider,
    provider,
    changeProviderDetails,
    isProviderNameValid,
    hasSeamlessOption,
    validateStep,
    onNextClick,
    onEditClick,
  } = props;

  return (
    <Box
      display="flex"
      flexDirection="column"
      padding="spacing.7"
      gap={isFormEdit ? 'spacing.9' : 'spacing.6'}
      backgroundColor="surface.background.gray.intense"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Heading color="surface.text.gray.subtle" size="large">
            Provider details
          </Heading>
          {isFormEdit && (
            <Text color="surface.text.gray.subtle">Add details of your payment provider</Text>
          )}
        </Box>

        <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="end">
          {selectedProvider && !isFormEdit ? (
            <Button
              icon={EditIcon}
              onClick={() => onEditClick(3)}
              variant="tertiary"
              iconPosition="left"
            >
              Edit provider details
            </Button>
          ) : (
            <Text color="surface.text.gray.muted">
              {hasSeamlessOption ? 'STEP 3 OUT OF 4' : 'STEP 2 OUT OF 3'}
            </Text>
          )}
        </Box>
      </Box>
      <Box display="flex" flexDirection="column" gap="spacing.5">
        <Box display="flex" alignItems="center">
          <Box minWidth="180px">
            <Text>Provider Name</Text>
          </Box>
          <Box minWidth="280px">
            {isFormEdit ? (
              <TextInput
                name="Provider_name"
                placeholder="Max 26 characters"
                value={provider.Provider_name}
                validationState={isProviderNameValid ? 'none' : 'error'}
                errorText="Name already exists. Please select a different name."
                onChange={changeProviderDetails}
                testID="provider-name"
              />
            ) : (
              <Text weight="semibold">{provider.Provider_name}</Text>
            )}
          </Box>
        </Box>

        <Box display="flex" alignItems="center">
          <Box minWidth="180px">
            <Text>Description</Text>
          </Box>
          <Box minWidth="280px">
            {isFormEdit ? (
              <TextArea
                name="Description"
                placeholder="Details about the added provider"
                value={provider.Description}
                maxCharacters={150}
                onChange={changeProviderDetails}
                testID="provider-description"
              />
            ) : (
              <Text weight="semibold" truncateAfterLines={3}>
                {provider.Description}
              </Text>
            )}
          </Box>
        </Box>
      </Box>
      {isFormEdit && (
        <Box display="flex" justifyContent="end">
          <Box display="flex" alignItems="center" gap="spacing.7">
            <Button isDisabled={validateStep(3)} onClick={onNextClick}>
              Next
            </Button>
          </Box>
        </Box>
      )}
    </Box>
  );
};

export default ProviderDetails;
