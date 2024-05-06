const fs = require('fs');
const path = require('path');
const rootPath = process.cwd();

const packageJsonPathsToUpdate = ['', 'web', 'apps/self-serve'];

const WhiteListedPackages = [
  '@razorpay/universe-utils',
  '@razorpay/universe-cli',
  '@razorpay/universe-test',
  '@razorpay/i18nify-js',
  'moment',
  'nx',
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
