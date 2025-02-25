import { QueryClient } from '@tanstack/react-query';

let client: QueryClient;

if (typeof window === 'undefined') {
  // Server
  client = new QueryClient({
    defaultOptions: {
      queries: {
        retry: 0,
        staleTime: 0 * 1000,
        cacheTime: 0 * 1000,
        retryOnMount: false,
      },
    },
  });
} else {
  // client
  client = new QueryClient({
    defaultOptions: {
      queries: {
        retry: 0,
        refetchOnWindowFocus: false,
        staleTime: 0 * 1000,
        cacheTime: 0 * 1000,
        retryOnMount: false,
      },
    },
  });
}

export const queryClient = client;