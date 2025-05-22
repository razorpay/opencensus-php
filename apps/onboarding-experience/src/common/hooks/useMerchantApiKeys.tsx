import { useMutation } from '@tanstack/react-query';
import { dashboardFetch } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
import {
  ApiKeyDelay,
  RegenerateKeysApiResponse,
  RevealKeysApiResponse,
  UseMerchantApiKeysReturnType,
} from '@OnboardingExperienceCommons/types/apiKeys';
import {
  transformRegenerateApiKeyResponse,
  transformRevealApiKeyResponse,
} from '@OnboardingExperienceCommons/utils/apiKeys';

const useMerchantApiKeys = (): UseMerchantApiKeysReturnType => {
  const { mode } = useStore((state) => state.session);

  const { mutateAsync: generateApiKeyMutation } = useMutation<
    ReturnType<typeof transformRevealApiKeyResponse>,
    Error,
    undefined
  >({
    mutationFn: async () => {
      const response: RevealKeysApiResponse = await dashboardFetch({
        url: `keys`,
        method: 'POST',
        data: {},
        mode: mode,
      });

      return transformRevealApiKeyResponse(response);
    },
  });

  const { mutateAsync: regenerateApiKeyMutation } = useMutation<
    ReturnType<typeof transformRegenerateApiKeyResponse>,
    Error,
    {
      keyRollDelay: ApiKeyDelay;
      oldApiKeyId: string;
    }
  >({
    mutationFn: async ({ keyRollDelay, oldApiKeyId }) => {
      const response: RegenerateKeysApiResponse = await dashboardFetch({
        url: `keys/${oldApiKeyId}`,
        method: 'PUT',
        data: {
          delay_roll: keyRollDelay === ApiKeyDelay.DELAY ? '1' : '0',
        },
        mode: mode,
      });

      return transformRegenerateApiKeyResponse(response);
    },
  });

  return {
    generateApiKeyMutation,
    regenerateApiKeyMutation,
  };
};

export default useMerchantApiKeys;
