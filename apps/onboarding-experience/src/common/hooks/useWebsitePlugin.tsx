import { graphqlRequestMutation } from '@federated/apps/shell/graphql';
import { useMutation } from '@tanstack/react-query';
import { ADD_MERCHANT_PLUGIN_MUTATION } from '@OnboardingExperienceCommons/queries/website';

type WebsitePluginInput = {
  websiteUrl: string;
  pluginName: string;
};

const useWebsitePlugin = () => {
  return useMutation({
    mutationFn: ({ websiteUrl, pluginName }: WebsitePluginInput) =>
      graphqlRequestMutation({
        document: ADD_MERCHANT_PLUGIN_MUTATION,
        variables: {
          website: websiteUrl,
          pluginName,
        },
      }),
  });
};

export default useWebsitePlugin;
