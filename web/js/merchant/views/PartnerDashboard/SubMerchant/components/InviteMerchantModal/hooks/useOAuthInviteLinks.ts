import { useQuery, UseQueryResult } from '@tanstack/react-query';

import { ShowNotificationType } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

interface SettingsData {
  name: string;
  product: string;
  entity_id: string;
  entity_type: string;
  value: string;
  created_at: string;
  updated_at: string;
}
const useOAuthInviteLinks = ({
  selectedApp,
  showNotification,
}: {
  selectedApp: OAuthAppDetailsType;
  showNotification: ShowNotificationType;
}): UseQueryResult<SettingsData> => {
  return useQuery({
    queryKey: ['fetch-oauth-invite-links', selectedApp],
    queryFn: async (): Promise<SettingsData> => {
      const { client_id, application_id, redirect_uri } = selectedApp;
      const { data } = await merchantFetch({
        url: 'partnerships/twirp/rzp.commissions.settings.v1.SettingsAPI/Upsert',
        mode: 'live',
        method: 'post',
        data: {
          entity_type: 'application',
          entity_id: application_id,
          product: 'primary',
          name: 'OAUTH_REFERRAL_LINK',
          meta: {
            client_id,
            application_id,
            redirect_uri,
            scope: 'read_write',
          },
        },
      });
      return data?.settings;
    },
    enabled: true,
    retry: false,
    refetchOnWindowFocus: false,
    refetchOnMount: false,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    onError: (_err) => {
      showNotification?.({
        type: 'error',
        message: 'There was an error fetching the invite link',
      });
    },
  });
};

export default useOAuthInviteLinks;
