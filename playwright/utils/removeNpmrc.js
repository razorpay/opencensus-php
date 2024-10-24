const fs = require('fs');
const path = require('path');

const { parseYaml } = require('../../utils/parseYaml');

try {
  const workspacePath = path.join(process.cwd(), 'pnpm-workspace.yaml');
  const yamlContent = fs.readFileSync(workspacePath, 'utf8');
  const parsedData = parseYaml(yamlContent);
  if (!('packages' in parsedData)) {
    console.error('No packages found in the workspace');
    process.exit(1);
  }
  const pathsContaingNpmrcFiles = ['', ...parsedData.packages];
  console.log('Paths containing .npmrc files:', pathsContaingNpmrcFiles);

  pathsContaingNpmrcFiles.forEach((filePath) => {
    const npmrcPath = path.join(process.cwd(), filePath, '.npmrc');
    if (fs.existsSync(npmrcPath)) {
      fs.unlinkSync(npmrcPath);
      console.log(`Removed .npmrc file at ${npmrcPath}`);
    } else {
      console.log(`No .npmrc file found at ${npmrcPath}`);
    }
  });
} catch (error) {
  console.error('Error reading or parsing YAML:', error);
}
