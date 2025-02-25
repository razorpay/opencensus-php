/**
 * Loads an image from a given source and handles onLoad and onError events.
 *
 * @param {string} src - The source URL of the image.
 * @param {() => void} onLoad - Callback function to be executed when the image loads successfully.
 * @param {() => void} onError - Callback function to be executed when the image fails to load.
 */
export const loadImage = (src: string, onLoad?: () => void, onError?: () => void): void => {
  if (!src || typeof Image === 'undefined') {
    return;
  }

  const image = new Image();

  if (onLoad) {
    image.onload = onLoad;
  }

  if (onError) {
    image.onerror = onError;
  }

  image.src = src;
};
