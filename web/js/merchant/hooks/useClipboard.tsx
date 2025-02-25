import { useEffect, useState } from 'react';

import copyToClipboard from '@libs/web-nexus/common/utils/copyToClipboard';

export interface UseClipboardReturn {
  isCopied: boolean;
  copy: (text: string) => void;
}

/**
 * Copy text to clipboard and track whether the copy action happened recently
 * @param resetTimeout Resets the `isCopied` state after the given time in milliseconds. Set to `Infinity` to never reset.
 * @returns a copy to clipboard function and a boolean indicating whether the copy action happened recently.
 */
export const useClipboard = (resetTimeout: number): UseClipboardReturn => {
  const [isCopied, setIsCopied] = useState(false);

  const copy = (text: string): void => {
    copyToClipboard(text);
    setIsCopied(true);
  };

  useEffect(() => {
    if (!isCopied || resetTimeout === Infinity) return (): void => {};

    const timer = window.setTimeout((): void => {
      setIsCopied(false);
    }, resetTimeout);

    return (): void => clearTimeout(timer);
  }, [isCopied, setIsCopied, resetTimeout]);

  return { isCopied, copy };
};

export default useClipboard;
