/**
 * Fetches the Youtube video ID from a Youtube URL for a video.
 * 
 * @param {string} url - The Youtube URL for the video.
 * @returns {string | false} - The Youtube video ID if the parsing is successful, otherwise `false`.
 * 
 * @example
 * const videoUrl = "https://www.youtube.com/watch?v=abc123xyz";
 * const videoID = getYoutubeVideoID(videoUrl);
 * console.log(videoID); // Output: "abc123xyz"
 */
export const getYoutubeVideoID = (url: string = ''): string | false => {
  /** Regex used from {@link https://stackoverflow.com/a/8260383 Stack Overflow} */
  const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
  const match = url.match(regExp);
  return match && match[7].length === 11 ? match[7] : false;
};
