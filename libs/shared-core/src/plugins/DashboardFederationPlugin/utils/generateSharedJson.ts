import { resolve } from 'path';
import { readFileSync } from 'fs';
import { DASHBOARD_ROOT } from '../../../';

export const generateSharedJson = () => {
  // Resolve paths to the package.json files
  // prettier-ignore
  const appPackagePath = resolve(process.cwd(), "./package.json");
  const rootPackagePath = resolve(DASHBOARD_ROOT, './package.json');

  // Helper function to load and parse JSON files synchronously
  const loadPackageJsonSync = (path: string) => {
    try {
      const data = readFileSync(path, 'utf-8');
      return JSON.parse(data);
    } catch (error) {
      console.error(`Failed to load ${path}:`, error);
      return { dependencies: {}, devDependencies: {} };
    }
  };

  // Load dependencies and devDependencies from app and root package.json files
  const { dependencies: appDependencies = {}, devDependencies: appDevDependencies = {} } =
    loadPackageJsonSync(appPackagePath);

  const { dependencies: rootDependencies = {}, devDependencies: rootDevDependencies = {} } =
    loadPackageJsonSync(rootPackagePath);

  // Merge dependencies and devDependencies
  const appDeps = { ...appDependencies, ...appDevDependencies };
  const rootDeps = { ...rootDependencies, ...rootDevDependencies };

  return {
    ...Object.keys(appDeps).reduce((dependencies, depPkgName) => {
      const depPkgVersion = appDeps[depPkgName];
      // Ignore libs
      if (['@libs/shared-core'].includes(depPkgName)) {
        return dependencies;
      }

      // Singleton Libs
      if (
        [
          '@razorpay/blade-old',
          '@razorpay/blade-old-for-new-auth',
          '@razorpay/i18nify-js',
          '@razorpay/i18nify-react',
          '@razorpay/universe-cli',
          '@razorpay/universe-utils',
          '@tanstack/query-core',
          '@tanstack/react-query',
          'history',
          'react',
          'react-dom',
          'react-router-dom',
          'redux',
          'redux-form',
          'styled-components',
          'xlsx',
          'zustand',
          // '@reduxjs/toolkit', // ???
          // '@razorpay/blade', // Allow multiple versions?
          // 'react-redux',  // TODO: 
        ].includes(depPkgName)
      ) {
        if (Boolean(rootDeps[depPkgName])) {
          dependencies[depPkgName] = {
            requiredVersion: rootDeps[depPkgName],
            singleton: true,
            version: rootDeps[depPkgName],
          };
        } else {
          throw new Error(
            `[@libs/shared-core] ${depPkgName} is missing in root package.json.\nThis dep is marked as singleton, should be added to root.`,
          );
        }

        // Rest deps
      } else {
        dependencies[depPkgName] = depPkgVersion;
      }
      return dependencies;
    }, {} as Record<string, any>),
  };
};
