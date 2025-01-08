import { useEffect, useRef } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';

import copyToClipboard from 'common/utils/copyToClipboard';

import { BASE_EXPORT_LINK_URL } from './constants';
import { getExportLink, postExportLink } from './helpers';

export const useExportLinkBanner = () => {
  const retryToCreateExportLink = useRef(0);
  const { data: accountsExportLink, refetch: refetchExportLink } = useQuery({
    queryKey: ['intl_bank_transfer_export_link'],
    queryFn: getExportLink,
    refetchOnWindowFocus: false,
  });

  const { mutate: createExportLink } = useMutation({
    mutationFn: () => postExportLink(),
    onSuccess: () => {
      refetchExportLink();
    },
  });

  useEffect(() => {
    // Create export link if not present
    if (!accountsExportLink && retryToCreateExportLink.current < 3) {
      createExportLink();
      retryToCreateExportLink.current += 1;
    }
  }, [accountsExportLink, createExportLink]);

  const text = `${BASE_EXPORT_LINK_URL}${accountsExportLink}`;
  const url = `https://${text}`;

  const handleCopy = () => {
    copyToClipboard(url);
  };

  return {
    text,
    url,
    isGenerated: !!accountsExportLink,
    handleCopy,
  };
};
