const fs = require('fs');
const path = require('path');

const { parseYaml } = require('../../utils/parseYaml');
// const yaml = require('js-yaml');

const rootPath = process.cwd();

// The package.json file in the root directory is the default package.json file
const packageJsonPathsToUpdate = [''];
// add root package json by default
// const packageJsonPathsToUpdate = [''];
// try {
//   const workspaces = yaml.load(fs.readFileSync(path.join(rootPath, 'pnpm-workspace.yaml'), 'utf8'));
//   packageJsonPathsToUpdate.push(...workspaces.packages);
//   console.log('packageJsonPathsToUpdate', packageJsonPathsToUpdate);
// } catch (e) {
//   console.log('Error parsing pnpm-workspace.yaml', e);
//   process.exit(1);
// }

try {
  const workspacePath = path.join(rootPath, 'pnpm-workspace.yaml');
  const yamlContent = fs.readFileSync(workspacePath, 'utf8');
  const parsedData = parseYaml(yamlContent);
  if (!('packages' in parsedData)) {
    console.error('No packages found in the pnpm-workspace.yaml');
    process.exit(1);
  }
  packageJsonPathsToUpdate.push(...parsedData.packages);
  console.log('package.json paths to update:', packageJsonPathsToUpdate);
} catch (error) {
  console.error('Error reading or parsing YAML:', error);
}

const WhiteListedPackages = [
  'moment',
  'nx',
  '@playwright/test',
  'husky',
  '@reportportal/agent-js-playwright',
  '@razorpay/i18nify-js',
  'msw',
  'playwright-msw',
];

const getPackageDependecies = (packageJson, type) =>
  Object.keys(packageJson[type])
    .filter((dependency) => WhiteListedPackages.includes(dependency))
    .reduce((accumulator, dependency) => {
      accumulator[dependency] = packageJson[type][dependency];
      return accumulator;
    }, {});

packageJsonPathsToUpdate.forEach((eachPath) => {
  const packageJsonPath = path.join(rootPath, eachPath, 'package.json');
  const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));

  // Replace the dependencies in the package.json with white listed packages
  packageJson.dependencies = getPackageDependecies(packageJson, 'dependencies');
  packageJson.devDependencies = getPackageDependecies(packageJson, 'devDependencies');

  // Write the result back to the package.json
  fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2));
});

console.log('Dependencies updated successfully!');
