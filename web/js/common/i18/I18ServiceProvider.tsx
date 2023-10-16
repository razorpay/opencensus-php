import React, { createContext, useMemo } from 'react';
import { connect } from 'react-redux';
import { useSplitzService } from 'common/splitz';
import { CONFIG_TO_TAG_MAPPING, ConfigTagType } from 'merchant/constants/tags';
import { I18ContextStateType } from './types';

export const I18ServiceContext = createContext({} as I18ContextStateType);

const mapStateToProps = ({ session }) => ({
  session,
});

export const I18ServiceProvider = connect(
  mapStateToProps,
  null,
)(({ children, session }) => {
  const { user } = session;
  const {
    abExperiments: { config_based_tags },
  } = useSplitzService();

  const isConfigTagExperimentEnabled = config_based_tags?.variables?.result === 'on';

  const isConfigTagEnabled = useMemo(() => {
    return (path: ConfigTagType): boolean => {
      if (!path) {
        return false;
      }

      /**
       * Fallback to tags approach if
       * exp is not enabled for merchant.
       */
      if (!isConfigTagExperimentEnabled) {
        const tag = CONFIG_TO_TAG_MAPPING[path];
        if (!tag) {
          return false;
        }
        return user.findTag(tag);
      }

      const pathList = path.split('.');
      let configValue = { ...(user.configTags ?? {}) };

      for (const key of pathList) {
        // removing optional chaining
        const newKey = key.replace('?', '');
        if (configValue[newKey]) {
          configValue = configValue[newKey];
        } else {
          return false;
        }
      }
      return configValue;
    };
  }, [isConfigTagExperimentEnabled, user]);

  return (
    <I18ServiceContext.Provider
      value={{
        isConfigTagEnabled,
      }}
    >
      {children}
    </I18ServiceContext.Provider>
  );
});
