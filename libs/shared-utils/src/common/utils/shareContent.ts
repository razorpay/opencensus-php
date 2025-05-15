import { copyToClipboard } from './copyToClipboard';

/**
 * Share content using Web Share API if available, or fall back to copy to clipboard.
 *
 * @param {Object} options - Share options
 * @param {string} options.url - URL to share
 * @param {string} options.title - Title of the content to share
 * @param {string} options.text - Description text for the shared content
 */
export const shareContent = async ({
  url,
  title,
  text,
}: {
  url: string;
  title?: string;
  text?: string;
}): Promise<void> => {
  // Check if Web Share API is available
  if (navigator.share && typeof navigator.share === 'function') {
    try {
      await navigator.share({
        title,
        text,
        url,
      });
    } catch (error) {
      copyToClipboard(url);
    }
  } else {
    // Web Share API not available, use clipboard fallback
    copyToClipboard(url);
  }
};
