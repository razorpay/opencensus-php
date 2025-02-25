import { DASHBOARD_FEDERATED_MODULES, DASHBOARD_FEDERATED_MODULE_TYPE } from '../constants';

const getRemotePathForImport = (nxProjectName: string, type: DASHBOARD_FEDERATED_MODULE_TYPE) => {
  const DASHBOARD_TYPE_MODULE_NAME_SUFFIX = '-dashboard';
  switch (type) {
    case DASHBOARD_FEDERATED_MODULE_TYPE.APP:
      return `${type}/${nxProjectName}`;
    case DASHBOARD_FEDERATED_MODULE_TYPE.DASHBOARD:
      if (!nxProjectName.includes(DASHBOARD_TYPE_MODULE_NAME_SUFFIX)) {
        throw new Error('Dashboard type modules should have "_dashboard" as suffix.');
      }
      return `${type}/${nxProjectName.replace(DASHBOARD_TYPE_MODULE_NAME_SUFFIX, '')}`;
    default:
      throw new Error('Invalid Module Type Passed.');
  }
};

export const generateBaseMfeBaseMeta = ({
  moduleType,
  moduleName,
}: {
  moduleName: DASHBOARD_FEDERATED_MODULES;
  moduleType: DASHBOARD_FEDERATED_MODULE_TYPE;
}) => {
  const nxProjectName = moduleName.split('_').join('-');
  const fileName = `${moduleName}.remoteEntry.js`;
  return {
    consumerRemoteImportName: getRemotePathForImport(nxProjectName, moduleType),
    nxProjectName,
    moduleName,
    fileName,
  };
};
