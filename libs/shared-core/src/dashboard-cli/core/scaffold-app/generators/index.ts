import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Compute __dirname in ESM
const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Replace all placeholders (in the form {{ key }}) in a template string
 * with their corresponding values from data.
 */
function replacePlaceholders(template: string, data: Record<string, string>): string {
  let output = template;
  for (const key of Object.keys(data)) {
    const pattern = new RegExp(`{{\s*${key}\s*}}`, 'g');
    output = output.replace(pattern, data[key]);
  }
  return output;
}

/**
 * Reads a template file, substitutes placeholders, and writes to the destination.
 *
 * @param templatePath - Path to the template file.
 * @param destinationPath - Path where the output should be written.
 * @param data - An object of key/value pairs for substitution.
 */
async function generateFileFromTemplate(templatePath: string, destinationPath: string, data: Record<string, string>): Promise<void> {
  try {
    const template = await fs.promises.readFile(templatePath, 'utf-8');
    const content = replacePlaceholders(template, data);
    await fs.promises.writeFile(destinationPath, content, 'utf-8');
  } catch (error) {
    console.error(`Error processing template ${templatePath}:`, error);
    throw error;
  }
}

/**
 * User input structure for generating the project.
 */
interface ProjectAnswers {
  appName: string;
  indexExt: string;
  reactVersion: string;
  language: 'JavaScript' | 'TypeScript';
}

/**
 * Represents a template file mapping.
 */
interface TemplateFile {
  tpl: string;
  out: string;
}

/**
 * Generates the project structure and files based on user input.
 *
 * @param answers - User input including appName, language, etc.
 */
export async function generateProject(answers: ProjectAnswers): Promise<void> {
  const { appName, indexExt, reactVersion, language } = answers;
  // For jest configuration: add TypeScript extensions if needed.
  const transformExt = language === 'TypeScript' ? '|ts|tsx' : '';

  // Data object for placeholder replacement.
  const data: Record<string, string> = {
    appName,
    indexExt,
    reactVersion: reactVersion === 'latest' ? '^18.2.0' : `^${reactVersion}`,
    language,
    transformExt,
  };

  // Create the project and src directories (using recursive mkdir for robustness)
  const projectDir = path.join(process.cwd(), appName);
  await fs.promises.mkdir(projectDir, { recursive: true });
  const srcDir = path.join(projectDir, 'src');
  await fs.promises.mkdir(srcDir, { recursive: true });

  // Define the path to the template files
  const templatesDir = path.join(__dirname, '..', 'templates');

  // List of root-level files to generate from templates.
  const filesToGenerate: TemplateFile[] = [
    { tpl: 'webpack.config.js.tpl', out: 'webpack.config.js' },
    { tpl: 'rspack.config.js.tpl', out: 'rspack.config.js' },
    { tpl: 'jest.config.js.tpl', out: 'jest.config.js' },
    { tpl: 'playwright.config.js.tpl', out: 'playwright.config.js' },
    { tpl: 'package.json.tpl', out: 'package.json' },
  ];

  // Include tsconfig.json if TypeScript is selected.
  if (language === 'TypeScript') {
    filesToGenerate.push({ tpl: 'tsconfig.json.tpl', out: 'tsconfig.json' });
  }

  // Process each root-level template.
  for (const file of filesToGenerate) {
    const templatePath = path.join(templatesDir, file.tpl);
    const outputPath = path.join(projectDir, file.out);
    await generateFileFromTemplate(templatePath, outputPath, data);
  }

  // Generate the entry file inside src.
  const srcTplName = language === 'TypeScript' ? 'src/index.tsx.tpl' : 'src/index.jsx.tpl';
  const srcTplPath = path.join(templatesDir, srcTplName);
  const srcOutputPath = path.join(srcDir, `index.${indexExt}`);
  await generateFileFromTemplate(srcTplPath, srcOutputPath, data);
}
