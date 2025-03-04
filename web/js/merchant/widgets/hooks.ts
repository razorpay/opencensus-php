import { QueryKey, useMutation, useQueryClient } from '@tanstack/react-query';
import { cloneDeep } from 'lodash';

import { fetchUCSData } from 'merchant/containers/Home/RTUX/hooks/useUCSDataQuery';
import { RetryWidgetProps } from './types';

export const useRetryWidget = (queryKey: QueryKey) => {
  const queryClient = useQueryClient();
  const { mutate, isLoading: isRetrying } = useMutation({
    mutationFn: (data: RetryWidgetProps) => {
      const { id, ...params } = data;
      return fetchUCSData({ component_ids: [id], ...params });
    },
    /**
     * Once API is retried update response with new data.
     * The function finds the position of the widget in the cached response and updates it with the new response.
     * Response can also be nested within a widget.
     */
    onSuccess: (retryResponse, variables) => {
      queryClient.setQueryData(queryKey, (cachedResponse: any) => {
        const clonedResponse: any = cloneDeep(cachedResponse);

        // API returns response as an Array, iterate over each item
        retryResponse.components.forEach((response) => {
          const responseWidgetId = response.id;
          const cachedWidgetId = cachedResponse.components.findIndex(
            (component) => component.id === responseWidgetId,
          );
          // handle response update for a widget
          if (cachedWidgetId !== -1) {
            clonedResponse.components[cachedWidgetId] = {
              ...response,
              variables,
            };
          } else {
            // handle response update for a sub widget
            const totalComponents = cachedResponse.components.length;
            for (let i = 0; i < totalComponents; i++) {
              const subWidgetComponents = cachedResponse.components[i].components || [];
              const cachedSubWidgetId = subWidgetComponents.findIndex(
                (component) => component.id === responseWidgetId,
              );
              if (cachedSubWidgetId !== -1) {
                clonedResponse.components[i].components[cachedSubWidgetId] = {
                  ...response,
                  variables,
                };
                break;
              }
            }
          }
        });

        return clonedResponse;
      });
    },
  });

  return [isRetrying, mutate] as const;
};
