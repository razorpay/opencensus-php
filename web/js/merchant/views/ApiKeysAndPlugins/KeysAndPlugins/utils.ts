import { Platform } from './types';
import { NO_PLUGIN_OPTION, PLATFORM_CHECK_MAP } from './constants';
/**
 * Get available platform submitted by the merchant
 * Priority - Website > Android > iOS
 * Default - Website if none of them are present
 * @param {*} user - session user
 * @param {*} showProvidedChannels show platform channels selected and url provided by merchants
 * @return {*} {Platform}
 */
export const getAvailablePlatform = (user, { showProvidedChannels = false } = {}): Platform => {
  const merchantChannels = user?.merchant_business_detail?.website_details;

  for (const platform of Object.values(Platform)) {
    if (
      (!showProvidedChannels && user[platform]) ||
      (showProvidedChannels && user[platform] && merchantChannels?.[PLATFORM_CHECK_MAP[platform]])
    )
      return platform;
  }

  return Platform.WEBSITE;
};

/**
 * Get channels provided by the merchant during onboarding
 * @param {*} user - session user
 * @return {Platform[]} list of provided channels
 */
export const getProvidedChannels = (user): Platform[] => {
  const merchantChannels = user?.merchant_business_detail?.website_details;

  return Object.values(Platform).filter((platform) => {
    return merchantChannels?.[PLATFORM_CHECK_MAP[platform]] && user[platform];
  });
};

interface GetAvailablePluginProps {
  supportedPlugins: Record<string, unknown>;
  merchantSelectedPlugin: string | null;
  suggestedPlugin: string | null;
}
/**
 * Returns the first plugin from plugins array that is present in supportedPlugins map
 * @param {Record<string, any>} supportedPlugins - Hashmap of supported plugins
 * @param {string[]} plugins - list of plugin names
 * @return {*}  {string}
 *
 * @example
 * getAvailablePlugin({'Woocommerce': {...}, 'Shopify': {...}, ['Wix', 'Shopify']})
 * // returns 'Shopify'
 */
export const getAvailablePlugin = ({
  supportedPlugins,
  merchantSelectedPlugin,
  suggestedPlugin,
}: GetAvailablePluginProps): string | null => {
  if (merchantSelectedPlugin === '') return NO_PLUGIN_OPTION.name;
  if (merchantSelectedPlugin && merchantSelectedPlugin in supportedPlugins)
    return merchantSelectedPlugin;

  if (suggestedPlugin && suggestedPlugin in supportedPlugins) return suggestedPlugin;
  return null;
};

/**
 * Get Latest key from the list of keys
 * Comparator is `created_at` attribute
 * @param {*} keys - array of key object
 * @return {*} {key object}
 */
export const getLatestKey = (keys) => {
  return keys && keys.length ? keys?.sort((a, b) => b.created_at - a.created_at)?.[0] : null;
};

/**
 * Get hours offset in milliseconds
 * @param {number} hours - number of hours
 * @return {number} milliseconds
 */
export const getHoursOffset = (hours: number): number => {
  return hours * 60 * 60 * 1000;
};
